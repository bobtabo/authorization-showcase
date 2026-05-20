<?php

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

/**
 * 認可サーバーへのリバースプロキシを提供するコントローラークラスです。
 *
 * @author Satoshi Nagashiba <satoshi.nagashiba@gmail.com>
 * @package Controller
 */
class Controller_Proxy extends Controller
{
    /**
     * 認可サーバーの URL を取得します。
     *
     * @return string 認可サーバー URL
     */
    private static function authServerUrl(): string
    {
        return getenv('AUTH_SERVER_URL') ?: 'http://host.docker.internal:8080/function/php';
    }

    /**
     * 指定パスへ GET リクエストを転送し、認可サーバーのレスポンスをそのまま返します。
     *
     * @param string $path 転送先パス
     * @return \Response レスポンス
     */
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

    /**
     * クライアント一覧を認可サーバーから取得して返します。
     *
     * @return \Response レスポンス
     */
    public function action_clients()
    {
        return static::proxyGet('api/clients');
    }

    /**
     * クライアント会員向け JWT を発行して返します。
     *
     * @return \Response レスポンス
     */
    public function action_gate_issue()
    {
        return static::proxyGet('api/gate/issue');
    }

    /**
     * JWT を検証してペイロードを返します。
     *
     * @param string $identifier クライアント識別子
     * @return \Response レスポンス
     */
    public function action_gate_verify($identifier)
    {
        return static::proxyGet('api/gate/client/' . $identifier . '/verify');
    }
}
