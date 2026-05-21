<?php

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

/**
 * Stream wrapper that intercepts http:// requests made by file_get_contents
 * inside Controller_Proxy::proxyGet().  Each test registers this wrapper
 * under the "mockhttp" scheme, points AUTH_SERVER_URL at it, and configures
 * the static properties to control what the controller sees.
 */
class MockHttpStreamFuel
{
    /** @var string Body text returned to file_get_contents callers. */
    public static string $responseBody = '{}';

    /** @var int HTTP status code embedded in the synthetic response header. */
    public static int $responseStatus = 200;

    /**
     * Set to true to simulate a connection failure (file_get_contents returns false).
     * @var bool
     */
    public static bool $fail = false;

    /** @var string The last URL that was opened through this wrapper. */
    public static string $lastUrl = '';

    /** @var string[] The request headers sent by the caller. */
    public static array $lastRequestHeaders = [];

    /** @var int Current read position within $responseBody. */
    private int $position = 0;

    // ------------------------------------------------------------------
    // Registration helpers
    // ------------------------------------------------------------------

    public static function register(): void
    {
        if (in_array('mockhttp', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('mockhttp');
        }
        stream_wrapper_register('mockhttp', self::class);
    }

    public static function unregister(): void
    {
        if (in_array('mockhttp', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('mockhttp');
        }
    }

    /**
     * Restore defaults so tests are independent from each other.
     */
    public static function reset(): void
    {
        self::$responseBody           = '{}';
        self::$responseStatus         = 200;
        self::$fail                   = false;
        self::$lastUrl                = '';
        self::$lastRequestHeaders     = [];
    }

    // ------------------------------------------------------------------
    // PHP stream wrapper protocol
    // ------------------------------------------------------------------

    /** @var resource|null */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        if (self::$fail) {
            return false;
        }

        self::$lastUrl = $path;

        if ($this->context !== null) {
            $opts = stream_context_get_options($this->context);
            $raw  = $opts['mockhttp']['header'] ?? '';
            foreach (explode("\r\n", $raw) as $line) {
                if (trim($line) !== '') {
                    self::$lastRequestHeaders[] = $line;
                }
            }
        }

        // $http_response_header is a PHP magic variable populated by stream
        // wrappers; we set it in $GLOBALS so the controller's preg_match can
        // find the status line and parse the HTTP status code.
        $GLOBALS['http_response_header'] = ['HTTP/1.1 ' . self::$responseStatus . ' OK'];

        $this->position = 0;
        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk          = substr(self::$responseBody, $this->position, $count);
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$responseBody);
    }

    public function stream_stat(): array
    {
        return [];
    }
}

/**
 * Unit tests for Controller_Proxy.
 *
 * The controller's static helper methods are exercised by instantiating the
 * controller directly and calling the action methods.  AUTH_SERVER_URL is
 * set to the mockhttp:// scheme so every file_get_contents call is handled
 * by MockHttpStreamFuel without real network I/O.
 *
 * @group App
 * @group Controller
 */
class Test_Controller_Proxy extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MockHttpStreamFuel::reset();
        MockHttpStreamFuel::register();
        putenv('AUTH_SERVER_URL=mockhttp://auth-server');

        // Ensure QUERY_STRING is clean so tests are isolated
        $_SERVER['QUERY_STRING']    = '';
        $_SERVER['HTTP_AUTHORIZATION'] = '';
    }

    protected function tearDown(): void
    {
        MockHttpStreamFuel::unregister();
        putenv('AUTH_SERVER_URL');
        unset($GLOBALS['http_response_header']);
        unset($_SERVER['HTTP_AUTHORIZATION']);
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // /clients
    // ------------------------------------------------------------------

    public function test_clients_returns_upstream_body(): void
    {
        MockHttpStreamFuel::$responseBody   = '[{"id":1,"name":"ClientA"}]';
        MockHttpStreamFuel::$responseStatus = 200;

        $controller = new Controller_Proxy();
        $response   = $controller->action_clients();

        $this->assertInstanceOf(\Response::class, $response);
        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('ClientA', (string) $response->body);
    }

    public function test_clients_forwards_query_string(): void
    {
        MockHttpStreamFuel::$responseBody = '[]';
        $_SERVER['QUERY_STRING']          = 'page=2&limit=10';

        $controller = new Controller_Proxy();
        $controller->action_clients();

        $this->assertStringContainsString('page=2', MockHttpStreamFuel::$lastUrl);
        $this->assertStringContainsString('limit=10', MockHttpStreamFuel::$lastUrl);
    }

    public function test_clients_forwards_authorization_header(): void
    {
        MockHttpStreamFuel::$responseBody    = '{}';
        $_SERVER['HTTP_AUTHORIZATION']       = 'Bearer secret-token';

        $controller = new Controller_Proxy();
        $controller->action_clients();

        $authHeaderSent = false;
        foreach (MockHttpStreamFuel::$lastRequestHeaders as $header) {
            if (stripos($header, 'Authorization: Bearer secret-token') !== false) {
                $authHeaderSent = true;
                break;
            }
        }
        $this->assertTrue($authHeaderSent, 'Authorization header was not forwarded to the upstream request');
    }

    // ------------------------------------------------------------------
    // /gate/issue
    // ------------------------------------------------------------------

    public function test_gate_issue_proxies_to_correct_path(): void
    {
        MockHttpStreamFuel::$responseBody = '{"token":"eyJ..."}';

        $controller = new Controller_Proxy();
        $response   = $controller->action_gate_issue();

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('api/gate/issue', MockHttpStreamFuel::$lastUrl);
        $this->assertStringContainsString('token', (string) $response->body);
    }

    // ------------------------------------------------------------------
    // /gate/client/:identifier/verify
    // ------------------------------------------------------------------

    public function test_gate_verify_includes_identifier_in_path(): void
    {
        MockHttpStreamFuel::$responseBody = '{"valid":true}';

        $controller = new Controller_Proxy();
        $response   = $controller->action_gate_verify('client-abc');

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('api/gate/client/client-abc/verify', MockHttpStreamFuel::$lastUrl);
        $this->assertStringContainsString('valid', (string) $response->body);
    }

    // ------------------------------------------------------------------
    // 502 on upstream failure
    // ------------------------------------------------------------------

    public function test_proxy_returns_502_on_failure(): void
    {
        MockHttpStreamFuel::$fail = true;

        $controller = new Controller_Proxy();
        $response   = $controller->action_clients();

        $this->assertInstanceOf(\Response::class, $response);
        $this->assertSame(502, $response->status);
        $this->assertStringContainsString('error', (string) $response->body);
    }
}
