<?php

namespace Tests\Unit;

use App\Models\Supplier;
use App\Services\SupplierService;
use App\Services\TourCMSService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class SupplierServiceTest extends UnitTestCase
{
    public string $showChannelString;
    public SimpleXMLElement $showChannelXML;

    public function setUp(): void
    {
        parent::setUp();
        $this->showChannelString = file_get_contents('./tests/TourCMSResponses/showChannel.xml');
        $this->showChannelXML = simplexml_load_string($this->showChannelString);
    }

    
    public function test_whenCallGetSupplierDataWithNoCapabilities_thenWeGetValidStructure()
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
        $capabilities = '';
        // When
        $channelId = (string) $this->showChannelXML->channel->channel_id;

        $supplier = $supplierServiceMock->createSupplierFromChannelData($this->showChannelXML->channel, $capabilities);
        $supplierData = $supplierServiceMock->getSupplierData($channelId, $capabilities);

        //Then
        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertIsArray($supplierData);
        $this->assertNotEmpty($supplierData);
    }

    public function test_whenCallGetSupplierDataWithCapabilities_thenWeGetValidStructure()
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
        $capabilities = 'content';
        // When
        $channelId = (string) $this->showChannelXML->channel->channel_id;

        $supplier = $supplierServiceMock->createSupplierFromChannelData($this->showChannelXML->channel, $capabilities);
        $supplierData = $supplierServiceMock->getSupplierData($channelId, $capabilities);

        //Then
        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertIsArray($supplierData);
        $this->assertNotEmpty($supplierData);
    }

    public function test_whenCallGetSupplierData_thenGetRightCompleteAddress()
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

        $baseAddress = $this->showChannelXML->channel->address_1 . ', '. $this->showChannelXML->channel->address_postcode . ', ' . $this->showChannelXML->channel->address_city . ' ('.$this->showChannelXML->channel->address_country.')';

        // When
        $channelId = (string) $this->showChannelXML->channel->channel_id;

        $supplier = $supplierServiceMock->createSupplierFromChannelData($this->showChannelXML->channel, '');
        $supplierData = $supplierServiceMock->getSupplierData($channelId, '');
        $fullAddress = $supplier->getFullAddress();
        //Then
        $this->assertNotEmpty($fullAddress);
        $this->assertEquals($baseAddress, $supplierData['contact']['address']);
        $this->assertEquals($baseAddress, $fullAddress);
    }
}
