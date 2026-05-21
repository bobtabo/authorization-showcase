<?php
declare(strict_types=1);

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Integration tests for ProxyController.
 *
 * AUTH_SERVER_URL を CI 環境の ngrok URL に向けてテストを実行します。
 */
class ProxyControllerTest extends TestCase
{
    use IntegrationTestTrait;

    const BEARER_TOKEN = 'Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369';
    const IDENTIFIER   = 'alpha-tech';
    const MEMBER       = 'M000001';

    // ------------------------------------------------------------------
    // Health — 認可サーバー不要
    // ------------------------------------------------------------------

    public function testHealth(): void
    {
        $this->get('/health');

        $this->assertResponseCode(200);
        $this->assertResponseContains('"status":"ok"');
    }

    // ------------------------------------------------------------------
    // /clients
    // ------------------------------------------------------------------

    public function testClientsReturnsNonEmptyList(): void
    {
        $this->configRequest(['headers' => ['Authorization' => self::BEARER_TOKEN]]);
        $this->get('/clients?statuses[]=2');

        $this->assertResponseCode(200);
        $body = json_decode((string) $this->_response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertGreaterThan(0, count($body));
    }

    // ------------------------------------------------------------------
    // /gate/issue
    // ------------------------------------------------------------------

    public function testGateIssueReturnsToken(): void
    {
        $this->configRequest(['headers' => ['Authorization' => self::BEARER_TOKEN]]);
        $this->get('/gate/issue?member=' . self::MEMBER);

        $this->assertResponseCode(200);
        $body = json_decode((string) $this->_response->getBody(), true);
        $this->assertArrayHasKey('token', $body);
        $this->assertNotEmpty($body['token']);
    }

    // ------------------------------------------------------------------
    // /gate/client/{identifier}/verify
    // ------------------------------------------------------------------

    public function testGateVerifyReturnsPayload(): void
    {
        // JWT 発行
        $this->configRequest(['headers' => ['Authorization' => self::BEARER_TOKEN]]);
        $this->get('/gate/issue?member=' . self::MEMBER);
        $this->assertResponseCode(200);
        $issued = json_decode((string) $this->_response->getBody(), true);
        $jwt = $issued['token'];

        // JWT 検証
        $this->configRequest(['headers' => ['Authorization' => self::BEARER_TOKEN]]);
        $this->get('/gate/client/' . self::IDENTIFIER . '/verify?token=' . $jwt);

        $this->assertResponseCode(200);
    }

    // ------------------------------------------------------------------
    // 502 on upstream failure
    // ------------------------------------------------------------------

    public function testProxyReturns502OnFailure(): void
    {
        // CakePHP の env() は $_ENV → $_SERVER → getenv() の順に参照するため全て上書きする
        $orig = $_ENV['AUTH_SERVER_URL'] ?? getenv('AUTH_SERVER_URL') ?: null;
        $_ENV['AUTH_SERVER_URL']    = 'http://127.0.0.1:1';
        $_SERVER['AUTH_SERVER_URL'] = 'http://127.0.0.1:1';
        putenv('AUTH_SERVER_URL=http://127.0.0.1:1');

        try {
            $this->configRequest(['headers' => ['Authorization' => self::BEARER_TOKEN]]);
            $this->get('/clients');

            $this->assertResponseCode(502);
            $this->assertResponseContains('error');
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
