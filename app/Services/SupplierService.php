<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\FailSignatureException;
use App\Models\Supplier;
use App\Services\TourCMSService;
use App\Transformers\BaseTransformer;
use App\Transformers\SupplierTransformer;
use SimpleXMLElement;

class SupplierService
{
    public TourCMSService $tourCMSService;
    public SupplierTransformer $supplierTransformer;

    public function __construct(TourCMSService $tourCMSService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->supplierTransformer = new SupplierTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    /**
     * Get the supplier data for a certain channel ID
     * @param string $channelId
     * @throws FailSignatureException
     * @throws APICallNotOKException
     * @return array
     */
    public function getSupplierData(string $channelId): array
    {
        $showChannelResponse = $this->tourCMSService->tourCMS->show_channel($channelId);
        
        if ((string) $showChannelResponse->error == TourCMSService::ERROR_FAIL_SIG) {
            throw new FailSignatureException();
        }
        
        if ((string) $showChannelResponse->error !== TourCMSService::ERROR_OK) {
            throw new APICallNotOKException();
        }

        
        $channelData = $showChannelResponse->channel;
        $supplier = $this->createSupplierFromChannelData($channelData);

        return $this->supplierTransformer->transform($supplier);
    }

    protected function createSupplierFromChannelData(SimpleXMLElement $channelData): Supplier
    {
        return new Supplier(
            (string) $channelData->channel_id,
            (string) $channelData->channel_name,
            $this->getOctoBaseUrl(),
            (string) $channelData->home_url,
            (string) $channelData->commercial_email_private,
            (string) $channelData->phone_customer,
            (string) $channelData-> address_1
        );
    }

    protected function getOctoBaseUrl(): string
    {
        return env('OCTO_BASE_URL', 'https://octo.tourcms.com/');
    }

}