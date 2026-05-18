<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;

class ProxyController extends AppController
{
    private string $authServerUrl;

    public function initialize(): void
    {
        parent::initialize();
        $this->autoRender = false;
        $this->authServerUrl = env('AUTH_SERVER_URL', 'http://host.docker.internal:8080/function/php');
    }

    private function proxyGet(string $path): ResponseInterface
    {
        $query = $this->request->getUri()->getQuery();
        $url = $this->authServerUrl . '/' . $path . ($query ? '?' . $query : '');

        $headers = ['Accept: application/json'];
        $auth = $this->request->getHeaderLine('Authorization');
        if ($auth) {
            $headers[] = 'Authorization: ' . $auth;
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => implode("\r\n", $headers),
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return $this->response->withStatus(502)->withType('application/json')
                ->withStringBody('{"error":"Failed to connect to auth server"}');
        }

        $statusCode = 200;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/[\d.]+ (\d+)/', $http_response_header[0], $m);
            $statusCode = (int)($m[1] ?? 200);
        }

        return $this->response->withStatus($statusCode)->withType('application/json')
            ->withStringBody($body);
    }

    public function health(): ResponseInterface
    {
        return $this->response->withStatus(200)->withType('application/json')
            ->withStringBody('{"status":"ok"}');
    }

    public function clients(): ResponseInterface
    {
        return $this->proxyGet('clients');
    }

    public function gateIssue(): ResponseInterface
    {
        return $this->proxyGet('gate/issue');
    }

    public function gateVerify(string $identifier): ResponseInterface
    {
        return $this->proxyGet('gate/client/' . $identifier . '/verify');
    }
}
