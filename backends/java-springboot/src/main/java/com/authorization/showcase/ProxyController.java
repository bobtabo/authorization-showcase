package com.authorization.showcase;

import jakarta.servlet.http.HttpServletRequest;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.client.RestClient;
import org.springframework.web.client.RestClientResponseException;

/**
 * 認可サーバーへのリバースプロキシを提供するコントローラークラスです。
 *
 * @author Satoshi Nagashiba <satoshi.nagashiba@gmail.com>
 */
@RestController
@CrossOrigin(origins = "*", allowedHeaders = "*")
public class ProxyController {

    @Value("${auth.server.url:http://host.docker.internal:8080/function/php}")
    private String authServerUrl;

    private final RestClient restClient = RestClient.create();

    /**
     * 指定パスへ GET リクエストを転送し、認可サーバーのレスポンスをそのまま返します。
     *
     * @param path        転送先パス
     * @param queryString クエリ文字列
     * @param authHeader  Authorization ヘッダー値
     * @return 認可サーバーからのレスポンス
     */
    private ResponseEntity<String> proxyGet(String path, String queryString, String authHeader) {
        String url = authServerUrl + "/" + path;
        if (queryString != null && !queryString.isEmpty()) {
            url += "?" + queryString;
        }
        try {
            var spec = restClient.get().uri(url).header("Accept", "application/json");
            if (authHeader != null && !authHeader.isEmpty()) {
                spec = spec.header("Authorization", authHeader);
            }
            return spec.retrieve().toEntity(String.class);
        } catch (RestClientResponseException e) {
            return ResponseEntity.status(e.getStatusCode()).body(e.getResponseBodyAsString());
        } catch (Exception e) {
            return ResponseEntity.status(502).body("{\"error\":\"" + e.getMessage() + "\"}");
        }
    }

    /**
     * ヘルスチェックエンドポイントです。
     *
     * @return ステータス JSON
     */
    @GetMapping("/health")
    public ResponseEntity<String> health() {
        return ResponseEntity.ok("{\"status\":\"ok\"}");
    }

    /**
     * クライアント一覧を認可サーバーから取得して返します。
     *
     * @param request HTTP リクエスト
     * @param auth    Authorization ヘッダー値
     * @return クライアント一覧 JSON
     */
    @GetMapping("/clients")
    public ResponseEntity<String> clients(
            HttpServletRequest request,
            @RequestHeader(value = "Authorization", required = false) String auth) {
        return proxyGet("clients", request.getQueryString(), auth);
    }

    /**
     * クライアント会員向け JWT を発行して返します。
     *
     * @param request HTTP リクエスト
     * @param auth    Authorization ヘッダー値
     * @return JWT 発行結果 JSON
     */
    @GetMapping("/gate/issue")
    public ResponseEntity<String> gateIssue(
            HttpServletRequest request,
            @RequestHeader(value = "Authorization", required = false) String auth) {
        return proxyGet("gate/issue", request.getQueryString(), auth);
    }

    /**
     * JWT を検証してペイロードを返します。
     *
     * @param identifier クライアント識別子
     * @param request    HTTP リクエスト
     * @param auth       Authorization ヘッダー値
     * @return JWT ペイロード JSON
     */
    @GetMapping("/gate/client/{identifier}/verify")
    public ResponseEntity<String> gateVerify(
            @PathVariable String identifier,
            HttpServletRequest request,
            @RequestHeader(value = "Authorization", required = false) String auth) {
        return proxyGet("gate/client/" + identifier + "/verify", request.getQueryString(), auth);
    }
}
