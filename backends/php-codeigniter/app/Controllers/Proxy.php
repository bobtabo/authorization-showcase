<?php

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */

namespace App\Controllers;

/**
 * 認可サーバーへのリバースプロキシを提供するコントローラークラスです。
 *
 * @author Satoshi Nagashiba <satoshi.nagashiba@gmail.com>
 * @package App\Controllers
 */
class Proxy extends BaseController
{
    private string $authServerUrl;

    /**
     * 初期化処理を行います。
     *
     * @param \CodeIgniter\HTTP\RequestInterface  $request  HTTP リクエスト
     * @param \CodeIgniter\HTTP\ResponseInterface $response HTTP レスポンス
     * @param \Psr\Log\LoggerInterface            $logger   ロガー
     * @return void
     */
    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->authServerUrl = env('AUTH_SERVER_URL', 'http://host.docker.internal:8080/function/php');
    }

    /**
     * 指定パスへ GET リクエストを転送し、認可サーバーのレスポンスをそのまま返します。
     *
     * @param string $path 転送先パス
     * @return \CodeIgniter\HTTP\ResponseInterface レスポンス
     */
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

    /**
     * クライアント一覧を認可サーバーから取得して返します。
     *
     * @return \CodeIgniter\HTTP\ResponseInterface レスポンス
     */
    public function clients(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyGet('api/clients');
    }

    /**
     * クライアント会員向け JWT を発行して返します。
     *
     * @return \CodeIgniter\HTTP\ResponseInterface レスポンス
     */
    public function gateIssue(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyGet('api/gate/issue');
    }

    /**
     * JWT を検証してペイロードを返します。
     *
     * @param string $identifier クライアント識別子
     * @return \CodeIgniter\HTTP\ResponseInterface レスポンス
     */
    public function gateVerify(string $identifier): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyGet("api/gate/client/{$identifier}/verify");
    }
}
