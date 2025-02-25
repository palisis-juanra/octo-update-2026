<?php

namespace Tests\Unit;


use App\Features\Availability\AvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use SimpleXMLElement;
use Tests\UnitTestCase;

class AvailabilityRequestTest extends UnitTestCase
{
    public function test_isDepartureAvailable_departuresHaveNoSpaces_availabilityIsNotAvailable(): void
    {
        $availabilityRequest = $this->getMockBuilder(AvailabilityRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUnits', 'getMinBookingSize', 'getMaxBookingSize'])
            ->getMock();

        $availabilityRequest->method('getUnits')->willReturn([]);
        $availabilityRequest->method('getMinBookingSize')->willReturn(1);
        $availabilityRequest->method('getMaxBookingSize')->willReturn(2);

        $departure = new SimpleXMLElement('<departure />');
        $departure->addChild('spaces_remaining', 0);
        $departure->addChild('status', BaseAvailabilityRequest::TCMS_STATUS_OPEN);

        $method = $this->getProtectedMethod($availabilityRequest, 'isDepartureAvailable');
        $available = $method->invokeArgs($availabilityRequest, [$departure]);

        $this->assertFalse($available);
    }

    public function test_isDepartureAvailable_departuresHaveSpacesButIsClosed_availabilityIsNotAvailable(): void
    {
        $availabilityRequest = $this->getMockBuilder(AvailabilityRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUnits', 'getMinBookingSize', 'getMaxBookingSize'])
            ->getMock();

        $availabilityRequest->method('getUnits')->willReturn([]);
        $availabilityRequest->method('getMinBookingSize')->willReturn(1);
        $availabilityRequest->method('getMaxBookingSize')->willReturn(2);

        $departure = new SimpleXMLElement('<departure />');
        $departure->addChild('spaces_remaining', 2);
        $departure->addChild('status', BaseAvailabilityRequest::TCMS_STATUS_CLOSED);
        $method = $this->getProtectedMethod($availabilityRequest, 'isDepartureAvailable');
        $available = $method->invokeArgs($availabilityRequest, [$departure]);

        $this->assertFalse($available);
    }

    public function test_isDepartureAvailable_departuresHaveNoSufficientSpaces_availabilityIsNotAvailable(): void
    {
        $availabilityRequest = $this->getMockBuilder(AvailabilityRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUnits', 'getMinBookingSize', 'getMaxBookingSize', 'howManySpacesAreRequested'])
            ->getMock();

        $availabilityRequest->method('getUnits')->willReturn([]);
        $availabilityRequest->method('getMinBookingSize')->willReturn(2);
        $availabilityRequest->method('getMaxBookingSize')->willReturn(4);
        $availabilityRequest->method('howManySpacesAreRequested')->willReturn(3);

        $departure = new SimpleXMLElement('<departure />');
        $departure->addChild('spaces_remaining', 2);
        $departure->addChild('status', BaseAvailabilityRequest::TCMS_STATUS_OPEN);
        $method = $this->getProtectedMethod($availabilityRequest, 'isDepartureAvailable');
        $available = $method->invokeArgs($availabilityRequest, [$departure]);

        $this->assertFalse($available);
    }
}