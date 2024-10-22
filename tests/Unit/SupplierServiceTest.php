<?php

namespace Tests\Unit;

use App\Models\Supplier;
use App\Services\SupplierService;
use App\Services\TourCMSService;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class SupplierServiceTest extends TestCase
{
    public string $showChannelString;
    public SimpleXMLElement $showChannelXML;

    public function setUp(): void
    {
        parent::setUp();
        $this->showChannelString = file_get_contents('./tests/TourCMSResponses/showChannel.xml');
        $this->showChannelXML = simplexml_load_string($this->showChannelString);
    }

    
    public function test_whenCallGetSupplierData_thenWeGetValidStructure()
    {
        //Given
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showChannel'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showChannel')->willReturn($this->showChannelXML);

        $supplierServiceMock = $this->getMockBuilder(SupplierService::class)
            ->onlyMethods([])
            ->setConstructorArgs([$tourCMSService])
            ->getMock();

        // When
        $channelId = (string) $this->showChannelXML->channel->channel_id;

        $supplier = $supplierServiceMock->createSupplierFromChannelData($this->showChannelXML);
        $supplierData = $supplierServiceMock->getSupplierData($channelId);

        //Then
        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertIsArray($supplierData);
        $this->assertNotEmpty($supplierData);
    }
}
