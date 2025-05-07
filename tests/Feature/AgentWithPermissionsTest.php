<?php

namespace Tests\Feature;

use App\Services\TourCMSService;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class AgentWithPermissionsTest extends FeatureTestCase
{
    public const BOOKING_UUID = "46c7cf4f-25e7-4abc-ba32-2ba2aa3f3ed2";
    protected TourCMSService|MockObject $tourCMSService;
    protected SimpleXMLElement $showChannelXML;

    public function setUp(): void
    {
        parent::setUp();

        $this->showChannelXML = simplexml_load_file('tests/TourCMSResponses/showChannel.xml');
        $this->showChannelXML->channel->connection_permission = 2;

        $this->tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $this->instance(TourCMSService::class, $this->tourCMSService);
    } 

    public function test_whenAgentDontHaveLevel3Permissions_thenTheyCanNotAccessBookingsEndpoints(): void
    {
        $response = $this->get('/bookings/' . self::BOOKING_UUID, [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertStatus(403);
    }
}
