<?php

namespace Tests\Feature;

use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class OctoAuthenticationTest extends TestCase
{
    const AUTH_HEADER_NAME = 'Authorization';
    const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';
    const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|1|aaa';


    public function test_whenWeDontSendBearerAuthHeader_thenWeGet401HttpStatusCode(): void
    {
        $response = $this->get('/auth');

        $response->assertStatus(401);
    }

    public function test_whenWeSendInvalidCredentials_thenWeGet403HttpStatusCode(): void
    {
        $response = $this->get('/auth', [self::AUTH_HEADER_NAME => self::OCTO_INVALID_PATTERN_CREDENTIALS]);

        $response->assertStatus(403);
    }

    public function test_whenWeSendCredentialsWithCorrectFormat_thenWeGet200HttpStatusCode(): void
    {
        $response = $this->get('/auth', [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertStatus(200);
    }
}
