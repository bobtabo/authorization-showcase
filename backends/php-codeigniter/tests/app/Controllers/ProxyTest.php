<?php

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Integration tests for the Proxy controller.
 *
 * AUTH_SERVER_URL を CI 環境の ngrok URL に向けてテストを実行します。
 */
class ProxyTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    const BEARER_TOKEN = 'Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369';
    const IDENTIFIER   = 'alpha-tech';
    const MEMBER       = 'M000001';

    // ------------------------------------------------------------------
    // Health — 認可サーバー不要
    // ------------------------------------------------------------------

    public function testHealth(): void
    {
        $result = $this->get('health');

        $result->assertStatus(200);
        $this->assertStringContainsString('"status":"ok"', (string) $result->response()->getBody());
    }

    // ------------------------------------------------------------------
    // /clients
    // ------------------------------------------------------------------

    public function testClientsReturnsNonEmptyList(): void
    {
        $result = $this->withHeaders(['Authorization' => self::BEARER_TOKEN])
                       ->get('clients?statuses[]=2');

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertIsArray($body['data']);
        $this->assertGreaterThan(0, count($body['data']));
    }

    // ------------------------------------------------------------------
    // /gate/issue
    // ------------------------------------------------------------------

    public function testGateIssueReturnsToken(): void
    {
        $result = $this->withHeaders(['Authorization' => self::BEARER_TOKEN])
                       ->get('gate/issue?member=' . self::MEMBER);

        $result->assertStatus(200);
        $body = json_decode((string) $result->response()->getBody(), true);
        $this->assertArrayHasKey('token', $body);
        $this->assertNotEmpty($body['token']);
    }

    // ------------------------------------------------------------------
    // /gate/client/(:any)/verify
    // ------------------------------------------------------------------

    public function testGateVerifyReturnsPayload(): void
    {
        // JWT 発行
        $issueResult = $this->withHeaders(['Authorization' => self::BEARER_TOKEN])
                            ->get('gate/issue?member=' . self::MEMBER);
        $issueResult->assertStatus(200);
        $jwt = json_decode((string) $issueResult->response()->getBody(), true)['token'];

        // JWT 検証
        $result = $this->withHeaders(['Authorization' => self::BEARER_TOKEN])
                       ->get('gate/client/' . self::IDENTIFIER . '/verify?token=' . $jwt);

        $result->assertStatus(200);
    }

    // ------------------------------------------------------------------
    // 502 on upstream failure
    // ------------------------------------------------------------------

    public function testProxyReturns502OnFailure(): void
    {
        // CI4 の env() は $_ENV → $_SERVER → getenv() の順に参照するため全て上書きする
        $orig = $_ENV['AUTH_SERVER_URL'] ?? getenv('AUTH_SERVER_URL') ?: null;
        $_ENV['AUTH_SERVER_URL']    = 'http://127.0.0.1:1';
        $_SERVER['AUTH_SERVER_URL'] = 'http://127.0.0.1:1';
        putenv('AUTH_SERVER_URL=http://127.0.0.1:1');

        try {
            $result = $this->withHeaders(['Authorization' => self::BEARER_TOKEN])
                           ->get('clients');

            $result->assertStatus(502);
            $this->assertStringContainsString('error', (string) $result->response()->getBody());
        } finally {
            if ($orig !== null) {
                $_ENV['AUTH_SERVER_URL']    = $orig;
                $_SERVER['AUTH_SERVER_URL'] = $orig;
                putenv('AUTH_SERVER_URL=' . $orig);
            } else {
                unset($_ENV['AUTH_SERVER_URL'], $_SERVER['AUTH_SERVER_URL']);
                putenv('AUTH_SERVER_URL');
            }
        }
    }
}
