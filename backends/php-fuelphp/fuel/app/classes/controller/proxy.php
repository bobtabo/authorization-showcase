<?php

class Controller_Proxy extends Controller
{
    private static function authServerUrl(): string
    {
        return getenv('AUTH_SERVER_URL') ?: 'http://host.docker.internal:8080/function/php';
    }

    private static function proxyGet(string $path): \Response
    {
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $url         = static::authServerUrl() . '/' . $path . ($queryString ? '?' . $queryString : '');

        $headers = ['Accept: application/json'];
        $auth    = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
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
            $response = new \Response('{"error":"Failed to connect to auth server"}', 502);
            $response->set_header('Content-Type', 'application/json; charset=utf-8');
            return $response;
        }

        $statusCode = 200;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/[\d.]+ (\d+)/', $http_response_header[0], $m);
            $statusCode = (int)($m[1] ?? 200);
        }

        $response = new \Response($body, $statusCode);
        $response->set_header('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    public function action_clients()
    {
        return static::proxyGet('clients');
    }

    public function action_gate_issue()
    {
        return static::proxyGet('gate/issue');
    }

    public function action_gate_verify($identifier)
    {
        return static::proxyGet('gate/client/' . $identifier . '/verify');
    }
}
