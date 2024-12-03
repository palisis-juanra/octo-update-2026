<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use stdClass;
use TourCMS\Utils\TourCMS as TourCMS;

class TourCMSMulti extends TourCMS
{
    const NO_REQUEST_TO_PROCESS = 'NO_REQUEST_TO_PROCESS';

    /**
     * __construct
     *
     * @author Juan Ramón González Morales
     *
     * @param $mp Marketplace ID
     * @param $k API Private Key
     * @param $res Result type, defaults to raw
     * @param $to Timeout, default 0
     */
    public function __construct($mp, $k, $res = 'raw', $to = 0)
    {
        $this->marketp_id = $mp;
        $this->private_key = $k;
        $this->result_type = $res;
        $this->timeout = $to;
    }

    /**
     * request function
     * In this case, this function is going to request a request object with some info
     * Info as: url, verb, headers, post_data and the curl handler of the call
     *
     * @author Juan Ramón González Morales
     *
     * @param $path API path to call
     * @param $channel Channel ID, defaults to zero
     * @param $verb HTTP Verb, defaults to GET
     */
    public function request($path, $channel = 0, $verb = 'GET', $post_data = null): object
    {
        // Prepare the URL we are sending to
        $url = $this->base_url.$path;
        //Log::info('URL REQUEST: '.$url);
        // We need a signature for the header

        $outbound_time = time();
        $signature = $this->generate_signature($path, $verb, $channel, $outbound_time);

        // Build headers
        $headers = ['Content-type: text/xml;charset="utf-8"',
            'Date: '.gmdate('D, d M Y H:i:s \G\M\T', $outbound_time),
            "Authorization: TourCMS $channel:$this->marketp_id:$signature"];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (is_int($this->timeout) && $this->timeout > 50) ? $this->timeout : 50);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // $verbose = fopen('log/tcms.log/', 'w+');
        // curl_setopt($ch, CURLOPT_VERBOSE, true);
        // curl_setopt($ch, CURLOPT_STDERR, $verbose);
        /*
            Windows users having trouble connecting via SSL?
            Download the CA bundle from: http://curl.haxx.se/docs/caextract.html
            Finally uncomment the following line and point it to the downloaded file
        */
        // curl_setopt($ch, CURLOPT_CAINFO, "c:/path/to/ca-bundle.crt");

        $requestObject = new stdClass;

        if ($verb == 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            if (! is_null($post_data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data->asXML());
                //Log::info('POST DATA: '.$post_data->asXML());
                $requestObject->post_data = $post_data;
            } else {
                $requestObject->post_data = null;
                //Log::info('NO POST DATA: ');
            }
        }

        $requestObject->url = $url;
        $requestObject->verb = $verb;
        $requestObject->headers = $headers;
        $requestObject->curl_handler = $ch;

        return $requestObject;
    }

    /**
     * Function to process a request handler, do curl calls asynchronously and return a request handler object with responses
     *
     * @param  stdClass  $requestHandler Object with a requestArray var which includes curl handler to do calls asynchronously
     * @return stdClass $requestHandler Received requestHandler object with responses includes from the calls
     */
    public function proccessRequests(stdClass $requestHandler): stdClass
    {
        $requestHandler->error = 'OK';
        $requestHandler->responses = [];

        if (empty($requestHandler->requestArray)) {
            $requestHandler->error = self::NO_REQUEST_TO_PROCESS;
            return $requestHandler;
        }

        $requestHandler->start_time = microtime(true);

        $mh = curl_multi_init();
        foreach ($requestHandler->requestArray as $key => $requestObject) {
            curl_multi_add_handle($mh, $requestObject->curl_handler);
        }

        // Execute all queries simultaneously, and continue when all are complete.
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        // Close request handler and get response.
        foreach ($requestHandler->requestArray as $key => $requestObject) {
            curl_multi_remove_handle($mh, $requestObject->curl_handler);
            $response_curl = curl_multi_getcontent($requestObject->curl_handler);

            $requestObject->http_code = curl_getinfo($requestObject->curl_handler, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($requestObject->curl_handler, CURLINFO_HEADER_SIZE);
            $header = substr($response_curl, 0, $headerSize);
            $result = substr($response_curl, $headerSize);

            // Raw response.
            $requestObject->response = $result;

            // SimpleXMLElement.
            $resultXML = simplexml_load_string($result);
            if (! isset($resultXML) || empty($resultXML) || ! $resultXML) {
                $requestHandler->responses[(string) $key] = null;
            } else {
                $requestHandler->responses[(string) $key] = $resultXML;
            }
        }

        // Close the curl_multi handler.
        curl_multi_close($mh);

        $requestHandler->endTime = microtime(true);
        $requestHandler->totalTime = round($requestHandler->endTime - $requestHandler->start_time, 3);
        //Log::info('REQUEST TOTAL TIME: '.$requestHandler->totalTime);
       

        return $requestHandler;
    }
}
