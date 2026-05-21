<?php

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * 認可サーバーへのリバースプロキシを提供するコントローラークラスです。
 *
 * @author Satoshi Nagashiba <satoshi.nagashiba@gmail.com>
 * @package App\Controller
 */
class ProxyController extends AppController
{
    private string $authServerUrl;

    /**
     * 初期化処理を行います。
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->autoRender = false;
        $this->authServerUrl = env('AUTH_SERVER_URL', 'http://host.docker.internal:8080/function/php');
    }

    /**
     * 指定パスへ GET リクエストを転送し、認可サーバーのレスポンスをそのまま返します。
     *
     * @param string $path 転送先パス
     * @return ResponseInterface レスポンス
     */
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

    /**
     * ヘルスチェック応答を返します。
     *
     * @return ResponseInterface レスポンス
     */
    public function health(): ResponseInterface
    {
        return $this->response->withStatus(200)->withType('application/json')
            ->withStringBody('{"status":"ok"}');
    }

    /**
     * クライアント一覧を認可サーバーから取得して返します。
     *
     * @return ResponseInterface レスポンス
     */
    public function clients(): ResponseInterface
    {
        return $this->proxyGet('api/clients');
    }

    /**
     * クライアント会員向け JWT を発行して返します。
     *
     * @return ResponseInterface レスポンス
     */
    public function gateIssue(): ResponseInterface
    {
        return $this->proxyGet('api/gate/issue');
    }

    /**
     * JWT を検証してペイロードを返します。
     *
     * @return ResponseInterface レスポンス
     */
    public function gateVerify(): ResponseInterface
    {
        $identifier = $this->request->getParam('identifier', '');
        return $this->proxyGet('api/gate/client/' . $identifier . '/verify');
    }
}
