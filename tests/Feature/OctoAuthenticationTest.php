<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;

class OctoAuthenticationTest extends FeatureTestCase
{
    const AUTH_HEADER_NAME = 'Authorization';

    const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';

    const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|1|aaa';

    public function test_when_we_dont_send_bearer_auth_header_then_we_get401_http_status_code(): void
    {
        $response = $this->get('/auth');

        $response->assertStatus(401);
    }

    public function test_when_we_send_invalid_credentials_then_we_get403_http_status_code(): void
    {
        $response = $this->get('/auth', [self::AUTH_HEADER_NAME => self::OCTO_INVALID_PATTERN_CREDENTIALS]);

        $response->assertStatus(403);
    }

    public function test_when_we_send_credentials_with_correct_format_then_we_get200_http_status_code(): void
    {
        $response = $this->get('/auth', [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertStatus(200);
    }
}
