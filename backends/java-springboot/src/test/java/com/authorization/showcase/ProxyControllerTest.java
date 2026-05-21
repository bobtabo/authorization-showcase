package com.authorization.showcase;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.test.web.server.LocalServerPort;
import org.springframework.test.context.DynamicPropertyRegistry;
import org.springframework.test.context.DynamicPropertySource;
import org.springframework.test.util.ReflectionTestUtils;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;

import static org.assertj.core.api.Assertions.assertThat;

/**
 * This is a program developed by BobTabo.
 *
 * Copyright (c) 2026 BobTabo. All Rights Reserved.
 */
@SpringBootTest(webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT)
class ProxyControllerTest {

    static final String BEARER_TOKEN = "Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369";
    static final String IDENTIFIER   = "alpha-tech";
    static final String MEMBER       = "M000001";

    @LocalServerPort
    private int port;

    @Autowired
    private ProxyController proxyController;

    private HttpClient http;

    @DynamicPropertySource
    static void authServerProperties(DynamicPropertyRegistry registry) {
        String url = System.getenv("AUTH_SERVER_URL");
        if (url != null && !url.isEmpty()) {
            registry.add("auth.server.url", () -> url);
        }
    }

    @BeforeEach
    void setUp() {
        http = HttpClient.newHttpClient();
    }

    private String base() {
        return "http://localhost:" + port;
    }

    private HttpRequest.Builder get(String path) {
        return HttpRequest.newBuilder()
                .uri(URI.create(base() + path))
                .GET();
    }

    private HttpRequest.Builder getWithAuth(String path) {
        return get(path).header("Authorization", BEARER_TOKEN);
    }

    @Test
    void health_returns200() throws Exception {
        HttpResponse<String> response = http.send(
                get("/health").build(), HttpResponse.BodyHandlers.ofString());

        assertThat(response.statusCode()).isEqualTo(200);
        assertThat(response.body()).contains("\"status\"", "\"ok\"");
    }

    @Test
    void clients_returns_json_array() throws Exception {
        HttpResponse<String> response = http.send(
                getWithAuth("/clients?statuses%5B%5D=2").build(), HttpResponse.BodyHandlers.ofString());

        assertThat(response.statusCode()).isEqualTo(200);
        assertThat(response.body()).startsWith("[");
    }

    @Test
    void gateIssue_returns_token() throws Exception {
        HttpResponse<String> response = http.send(
                getWithAuth("/gate/issue?member=" + MEMBER).build(), HttpResponse.BodyHandlers.ofString());

        assertThat(response.statusCode()).isEqualTo(200);
        assertThat(response.body()).contains("token");
    }

    @Test
    void gateVerify_returns_payload() throws Exception {
        HttpResponse<String> issueResp = http.send(
                getWithAuth("/gate/issue?member=" + MEMBER).build(), HttpResponse.BodyHandlers.ofString());
        assertThat(issueResp.statusCode()).isEqualTo(200);

        String body = issueResp.body();
        int start = body.indexOf("\"token\":");
        assertThat(start).isGreaterThan(-1);
        String jwt = body.substring(start + 9).replaceAll("^\"|\".*", "");

        HttpResponse<String> verifyResp = http.send(
                getWithAuth("/gate/client/" + IDENTIFIER + "/verify?token=" + jwt).build(),
                HttpResponse.BodyHandlers.ofString());

        assertThat(verifyResp.statusCode()).isEqualTo(200);
    }

    @Test
    void proxy_returns_502_on_upstream_error() throws Exception {
        ReflectionTestUtils.setField(proxyController, "authServerUrl", "http://127.0.0.1:1");
        try {
            HttpResponse<String> response = http.send(
                    getWithAuth("/clients").build(), HttpResponse.BodyHandlers.ofString());

            assertThat(response.statusCode()).isEqualTo(502);
        } finally {
            String url = System.getenv("AUTH_SERVER_URL");
            String restore = (url != null && !url.isEmpty()) ? url : "http://host.docker.internal:8080/function/php";
            ReflectionTestUtils.setField(proxyController, "authServerUrl", restore);
        }
    }
}
