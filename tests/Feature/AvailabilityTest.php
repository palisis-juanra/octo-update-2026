<?php

namespace Tests\Feature;

use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    const AUTH_HEADER_NAME = 'Authorization';
    const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';
    const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|1|aaa';


    public function test_whenWeDontSendProductId_thenWeGet400HttpCode(): void
    {
        $response = $this->post('/availability', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        
        $response->assertBadRequest();
    }

    public function test_whenWeSendInvalidProductId_thenWeGet400HttpCode(): void
    {
        $response = $this->post('/availability', ["productId" => 'a'], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        
        $response->assertBadRequest();
    }

    public function test_whenWeDontSendOptionId_thenWeGet400HttpCode(): void
    {
        $response = $this->post('/availability', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertBadRequest();
    }

    public function test_whenWeSendInvalidOptionId_thenWeGet400HttpCode(): void
    {
        $response = $this->post('/availability', ['optionId' => 'a'], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertBadRequest();
    }

    public function test_whenWeSendCorrectProductIdOptionId_thenWeGet400HttpCode(): void
    {
        $response = $this->post(
            '/availability', 
            ['tourId' => 'TE_1_67' , 'optionId' => 'START_TIME|13:00'], 
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertBadRequest();
    }


}
