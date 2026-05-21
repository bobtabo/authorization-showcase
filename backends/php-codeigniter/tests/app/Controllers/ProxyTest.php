<?php

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Stream wrapper that intercepts http:// requests made by file_get_contents
 * inside Proxy::proxyGet().  Register it before each test that needs a
 * controlled upstream response, and unregister it afterward.
 *
 * Static properties let each test configure the response without instantiating
 * the wrapper directly (PHP creates the instance internally when
 * file_get_contents opens a URL matching the registered scheme).
 */
class MockHttpStreamCI
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

        // Populate the PHP magic variable that the controller reads for status
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
 * Integration tests for the Proxy controller.
 *
 * AUTH_SERVER_URL is set to the mockhttp:// scheme so that every
 * file_get_contents call inside Proxy::proxyGet() is handled by
 * MockHttpStreamCI without real network I/O.
 */
class ProxyTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        MockHttpStreamCI::reset();
        MockHttpStreamCI::register();
        putenv('AUTH_SERVER_URL=mockhttp://auth-server');
    }

    protected function tearDown(): void
    {
        MockHttpStreamCI::unregister();
        putenv('AUTH_SERVER_URL');
        unset($GLOBALS['http_response_header']);
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Health — handled directly by the route closure, no upstream call
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

    public function testClients(): void
    {
        MockHttpStreamCI::$responseBody   = '[{"id":1,"name":"ClientA"}]';
        MockHttpStreamCI::$responseStatus = 200;

        $result = $this->get('clients');

        $result->assertStatus(200);
        $this->assertStringContainsString('ClientA', (string) $result->response()->getBody());
    }

    public function testClientsForwardsQueryString(): void
    {
        MockHttpStreamCI::$responseBody = '[]';

        $result = $this->get('clients?page=2&limit=10');

        $this->assertStringContainsString('page=2', MockHttpStreamCI::$lastUrl);
        $this->assertStringContainsString('limit=10', MockHttpStreamCI::$lastUrl);
        $result->assertStatus(200);
    }

    // ------------------------------------------------------------------
    // /gate/issue
    // ------------------------------------------------------------------

    public function testGateIssue(): void
    {
        MockHttpStreamCI::$responseBody = '{"token":"eyJ..."}';

        $result = $this->get('gate/issue');

        $result->assertStatus(200);
        $this->assertStringContainsString('api/gate/issue', MockHttpStreamCI::$lastUrl);
        $this->assertStringContainsString('token', (string) $result->response()->getBody());
    }

    // ------------------------------------------------------------------
    // /gate/client/(:any)/verify
    // ------------------------------------------------------------------

    public function testGateVerify(): void
    {
        MockHttpStreamCI::$responseBody = '{"valid":true}';

        $result = $this->get('gate/client/client-xyz/verify');

        $result->assertStatus(200);
        $this->assertStringContainsString('api/gate/client/client-xyz/verify', MockHttpStreamCI::$lastUrl);
        $this->assertStringContainsString('valid', (string) $result->response()->getBody());
    }

    // ------------------------------------------------------------------
    // 502 on upstream failure
    // ------------------------------------------------------------------

    public function testProxyReturns502OnFailure(): void
    {
        MockHttpStreamCI::$fail = true;

        $result = $this->get('clients');

        $result->assertStatus(502);
        $this->assertStringContainsString('error', (string) $result->response()->getBody());
    }
}
