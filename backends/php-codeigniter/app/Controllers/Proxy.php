<?php

namespace App\Controllers;

class Proxy extends BaseController
{
    private string $authServerUrl;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->authServerUrl = env('AUTH_SERVER_URL', 'http://host.docker.internal:8080/function/php');
    }

    private function proxyGet(string $path): \CodeIgniter\HTTP\ResponseInterface
    {
        $query = $this->request->getUri()->getQuery();
        $url   = $this->authServerUrl . '/' . $path . ($query ? '?' . $query : '');

        $headers = ['Accept: application/json'];
        $auth    = $this->request->getHeaderLine('Authorization');
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
            return $this->response->setStatusCode(502)
                ->setHeader('Content-Type', 'application/json')
                ->setBody('{"error":"Failed to connect to auth server"}');
        }

        $statusCode = 200;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/[\d.]+ (\d+)/', $http_response_header[0], $m);
            $statusCode = (int)($m[1] ?? 200);
        }

        return $this->response->setStatusCode($statusCode)
            ->setHeader('Content-Type', 'application/json')
            ->setBody($body);
    }

    public function clients(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyGet('clients');
    }

    public function gateIssue(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyGet('gate/issue');
    }

    public function gateVerify(string $identifier): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyGet("gate/client/{$identifier}/verify");
    }
}
