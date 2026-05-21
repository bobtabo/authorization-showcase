<?php

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

/**
 * Unit tests for Controller_Proxy.
 *
 * AUTH_SERVER_URL を CI 環境の ngrok URL に向けてテストを実行します。
 * コントローラーを直接インスタンス化してアクションメソッドを呼び出します。
 *
 * @group App
 * @group Controller
 */
class Test_Controller_Proxy extends TestCase
{
    const BEARER_TOKEN = 'Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369';
    const IDENTIFIER   = 'alpha-tech';
    const MEMBER       = 'M000001';

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['QUERY_STRING']       = '';
        $_SERVER['HTTP_AUTHORIZATION'] = '';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        parent::tearDown();
    }

    private function newController(): \Controller_Proxy
    {
        return new \Controller_Proxy($this->createStub(\Request::class));
    }

    // ------------------------------------------------------------------
    // /clients
    // ------------------------------------------------------------------

    public function test_clients_returns_non_empty_list(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = self::BEARER_TOKEN;
        $_SERVER['QUERY_STRING']       = 'statuses[]=2';

        $controller = $this->newController();
        $response   = $controller->action_clients();

        $this->assertInstanceOf(\Response::class, $response);
        $this->assertSame(200, $response->status);
        $body = json_decode((string) $response->body, true);
        $this->assertIsArray($body);
        $this->assertGreaterThan(0, count($body));
    }

    // ------------------------------------------------------------------
    // /gate/issue
    // ------------------------------------------------------------------

    public function test_gate_issue_returns_token(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = self::BEARER_TOKEN;
        $_SERVER['QUERY_STRING']       = 'member=' . self::MEMBER;

        $controller = $this->newController();
        $response   = $controller->action_gate_issue();

        $this->assertSame(200, $response->status);
        $body = json_decode((string) $response->body, true);
        $this->assertArrayHasKey('token', $body);
        $this->assertNotEmpty($body['token']);
    }

    // ------------------------------------------------------------------
    // /gate/client/:identifier/verify
    // ------------------------------------------------------------------

    public function test_gate_verify_returns_payload(): void
    {
        // JWT 発行
        $_SERVER['HTTP_AUTHORIZATION'] = self::BEARER_TOKEN;
        $_SERVER['QUERY_STRING']       = 'member=' . self::MEMBER;
        $issueResp = $this->newController()->action_gate_issue();
        $this->assertSame(200, $issueResp->status);
        $jwt = json_decode((string) $issueResp->body, true)['token'];

        // JWT 検証
        $_SERVER['HTTP_AUTHORIZATION'] = self::BEARER_TOKEN;
        $_SERVER['QUERY_STRING']       = 'token=' . $jwt;
        $response = $this->newController()->action_gate_verify(self::IDENTIFIER);

        $this->assertSame(200, $response->status);
    }

    // ------------------------------------------------------------------
    // 502 on upstream failure
    // ------------------------------------------------------------------

    public function test_proxy_returns_502_on_failure(): void
    {
        $orig = getenv('AUTH_SERVER_URL');
        putenv('AUTH_SERVER_URL=http://127.0.0.1:1');

        try {
            $_SERVER['HTTP_AUTHORIZATION'] = self::BEARER_TOKEN;
            $response = $this->newController()->action_clients();

            $this->assertSame(502, $response->status);
            $this->assertStringContainsString('error', (string) $response->body);
        } finally {
            putenv($orig !== false ? 'AUTH_SERVER_URL=' . $orig : 'AUTH_SERVER_URL');
        }
    }
}
