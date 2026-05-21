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
 * Stream wrapper that intercepts http:// requests made by file_get_contents
 * inside ProxyController::proxyGet().  Register it before each test that
 * needs a controlled upstream response, and unregister it afterward.
 *
 * Static properties let each test configure the response without instantiating
 * the wrapper directly (PHP creates the instance internally).
 */
class MockHttpStream
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
        // Wrap only if not already registered
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
        self::$responseBody   = '{}';
        self::$responseStatus = 200;
        self::$fail           = false;
        self::$lastUrl        = '';
        self::$lastRequestHeaders = [];
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

        // Capture request headers from the stream context if present
        if ($this->context !== null) {
            $opts = stream_context_get_options($this->context);
            $raw  = $opts['mockhttp']['header'] ?? '';
            foreach (explode("\r\n", $raw) as $line) {
                if (trim($line) !== '') {
                    self::$lastRequestHeaders[] = $line;
                }
            }
        }

        // Inject a synthetic HTTP response header so the controller's status
        // parsing logic (which reads $http_response_header) has something to work with.
        $statusLine = 'HTTP/1.1 ' . self::$responseStatus . ' OK';
        // $http_response_header is a PHP magic variable populated by the stream;
        // we cannot set it directly here, but CakePHP's controller reads it from
        // the global scope.  We populate it so the preg_match succeeds.
        $GLOBALS['http_response_header'] = [$statusLine];

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
 * Integration tests for ProxyController.
 *
 * The test suite redirects AUTH_SERVER_URL to the mockhttp:// scheme so that
 * file_get_contents() calls inside ProxyController::proxyGet() are intercepted
 * by MockHttpStream without any real network I/O.
 */
class ProxyControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        MockHttpStream::reset();
        MockHttpStream::register();
        // Point the controller at our fake scheme so stream_context_create picks it up
        putenv('AUTH_SERVER_URL=mockhttp://auth-server');
    }

    protected function tearDown(): void
    {
        MockHttpStream::unregister();
        putenv('AUTH_SERVER_URL');
        unset($GLOBALS['http_response_header']);
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Health
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

    public function testClients(): void
    {
        MockHttpStream::$responseBody   = '[{"id":1,"name":"ClientA"}]';
        MockHttpStream::$responseStatus = 200;

        $this->get('/clients');

        $this->assertResponseCode(200);
        $this->assertResponseContains('ClientA');
    }

    public function testClientsForwardsQueryString(): void
    {
        MockHttpStream::$responseBody = '[]';

        $this->get('/clients?page=2&limit=10');

        // The URL that file_get_contents actually opened must include the query string
        $this->assertStringContainsString('page=2', MockHttpStream::$lastUrl);
        $this->assertStringContainsString('limit=10', MockHttpStream::$lastUrl);
        $this->assertResponseCode(200);
    }

    // ------------------------------------------------------------------
    // /gate/issue
    // ------------------------------------------------------------------

    public function testGateIssue(): void
    {
        MockHttpStream::$responseBody = '{"token":"eyJ..."}';

        $this->get('/gate/issue');

        $this->assertResponseCode(200);
        $this->assertStringContainsString('api/gate/issue', MockHttpStream::$lastUrl);
        $this->assertResponseContains('token');
    }

    // ------------------------------------------------------------------
    // /gate/client/{identifier}/verify
    // ------------------------------------------------------------------

    public function testGateVerify(): void
    {
        MockHttpStream::$responseBody = '{"valid":true}';

        $this->get('/gate/client/client-xyz/verify');

        $this->assertResponseCode(200);
        $this->assertStringContainsString('api/gate/client/client-xyz/verify', MockHttpStream::$lastUrl);
        $this->assertResponseContains('valid');
    }

    // ------------------------------------------------------------------
    // 502 on upstream failure
    // ------------------------------------------------------------------

    public function testProxyReturns502OnFailure(): void
    {
        MockHttpStream::$fail = true;

        $this->get('/clients');

        $this->assertResponseCode(502);
        $this->assertResponseContains('error');
    }
}
