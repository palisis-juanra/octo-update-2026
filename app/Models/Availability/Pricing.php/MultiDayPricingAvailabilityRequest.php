<?php

namespace App\Models\Availability\Pricing;

use App\Models\Availability;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\AvailabilityService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use Illuminate\Support\Facades\Log;

class MultiDayPricingAvailabilityRequest implements BaseAvailabilityRequest
{
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected string $currency;

    public function __construct(string $tourId, string $optionId, $localDateStart, string $localDateEnd, string $currency)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateEnd;
        $this->currency = $currency;
    }

    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $components = $this->fetchComponentsFromTourCMS($tourCMSService);
        $availabilities = $this->getAvailabilitiesFromComponents($components);

        return $availabilities;
    }

    protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService): array
    {
        $params = "date={$this->localDateStart}&r1=1";
        $response = $tourCMSService->checkAvailability($params, $this->tourId);
        Log::info($response->asXML());

        if (empty($response->available_components)) {
            return [];
        }

        $availableComponents = $tourCMSService->getArrayFromXmlNode($response->available_components, 'component');

        return $availableComponents;
    }

    protected function getAvailabilitiesFromComponents(array $components)
    {   
        $availabilities = [];
        foreach ($components as $component) {
            Log::info('component added');
            Log::info($component->asXML());
            
            $availability = new Availability(
                (string) $component->date_id,
                (string) $component->start_time_utcseconds,
                (string) $component->end_time_utcseconds,
                false,
                $component->spaces_remaining > 0,
                $this->getOctoStatusFromTourCMSStatus(''),
                (int) $component->spaces_remaining,
                (int) $component->spaces_total,
                999,
                'CUTOFF',
                '?',
                '?'                
            );
            $availabilities[] = $availability;
            
        } 

        return $availabilities;
    }

    public function getTourId(): string
    {
        return $this->tourId;
    }

    public function getOptionId(): string
    {
        return $this->optionId;
    }

    public function getLocalDateStart(): string
    {
        return $this->localDateStart;
    }

    public function getLocalDateEnd(): string
    {
        return $this->localDateStart;
    }

    public function getOctoStatusFromTourCMSStatus(string $tourCMSStatus): string
    {
        return 'AVAILABLE';
    }
}