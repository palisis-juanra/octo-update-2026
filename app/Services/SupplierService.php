<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\FailSignatureException;
use App\Models\Media;
use App\Models\Supplier;
use App\Services\TourCMSService;
use App\Transformers\BaseTransformer;
use App\Transformers\SupplierTransformer;
use Illuminate\Support\Facades\Log;
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
    public function getSupplierData(string $channelId, string $octoCapabilities): array
    {
        $showChannelResponse = $this->tourCMSService->showChannel($channelId);
        $channelData = $showChannelResponse->channel;
        $supplier = $this->createSupplierFromChannelData($channelData, $octoCapabilities);
        return $this->supplierTransformer->transform($supplier);
    }

    public function createSupplierFromChannelData(SimpleXMLElement $channelData, string $octoCapabilities): Supplier
    {
        $src = empty($channelData->logo_url) ? null : (string) $channelData->logo_url;
        $fileType = Media::getFileType($src);
        $media = new Media(
            $src,
            $fileType,
            !empty($src) ? Media::LOGO_REL : null
        );
        $shortDesc = empty($channelData->short_desc) ? null : (string) $channelData->short_desc;

        return new Supplier(
            (string) $channelData->channel_id,
            (string) $channelData->channel_name,
            $this->getOctoBaseUrl(),
            (string) $channelData->home_url,
            (string) $channelData->commercial_email_private,
            (string) $channelData->phone_customer,
            (string) $channelData->address_1,
            (string) $channelData->address_2,
            (string) $channelData->address_city,
            (string) $channelData->address_state,
            (string) $channelData->address_postcode,
            (string) $channelData->address_country,
            (string) $shortDesc,
            (array) [$media],
            (string) $octoCapabilities
        );
    }

    protected function getOctoBaseUrl(): string
    {
        return env('OCTO_BASE_URL', 'https://octo.tourcms.com/');
    }

}