package com.authorization.showcase;

import com.sun.net.httpserver.HttpServer;
import org.junit.jupiter.api.AfterAll;
import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.test.web.client.TestRestTemplate;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpMethod;
import org.springframework.http.ResponseEntity;
import org.springframework.test.context.DynamicPropertyRegistry;
import org.springframework.test.context.DynamicPropertySource;
import org.springframework.test.util.ReflectionTestUtils;

import java.io.IOException;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.atomic.AtomicReference;

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest(webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT)
class ProxyControllerTest {

    private static HttpServer fakeAuthServer;
    private static int fakeAuthPort;

    @Autowired
    private TestRestTemplate restTemplate;

    @Autowired
    private ProxyController proxyController;

    @BeforeAll
    static void startFakeAuthServer() throws IOException {
        fakeAuthServer = HttpServer.create(new InetSocketAddress(0), 0);
        fakeAuthPort = fakeAuthServer.getAddress().getPort();

        fakeAuthServer.createContext("/api/clients", exchange -> {
            byte[] body = "[{\"id\":1}]".getBytes(StandardCharsets.UTF_8);
            exchange.getResponseHeaders().set("Content-Type", "application/json");
            exchange.sendResponseHeaders(200, body.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(body);
            }
        });

        fakeAuthServer.createContext("/api/gate/issue", exchange -> {
            byte[] body = "{\"token\":\"abc\"}".getBytes(StandardCharsets.UTF_8);
            exchange.getResponseHeaders().set("Content-Type", "application/json");
            exchange.sendResponseHeaders(200, body.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(body);
            }
        });

        fakeAuthServer.createContext("/api/gate/client/", exchange -> {
            byte[] body = "{\"valid\":true}".getBytes(StandardCharsets.UTF_8);
            exchange.getResponseHeaders().set("Content-Type", "application/json");
            exchange.sendResponseHeaders(200, body.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(body);
            }
        });

        fakeAuthServer.start();
    }

    @AfterAll
    static void stopFakeAuthServer() {
        if (fakeAuthServer != null) {
            fakeAuthServer.stop(0);
        }
    }

    @DynamicPropertySource
    static void authServerProperties(DynamicPropertyRegistry registry) {
        // ラムダで遅延評価することで、@BeforeAll でポートが確定した後に参照されます。
        registry.add("auth.server.url", () -> "http://localhost:" + fakeAuthPort);
    }

    @Test
    void health_returns200() {
        ResponseEntity<String> response = restTemplate.getForEntity("/health", String.class);

        assertThat(response.getStatusCode().value()).isEqualTo(200);
        assertThat(response.getBody()).contains("\"status\"", "\"ok\"");
    }

    @Test
    void clients_proxiesToAuthServer() {
        ResponseEntity<String> response = restTemplate.getForEntity("/clients", String.class);

        assertThat(response.getStatusCode().value()).isEqualTo(200);
        assertThat(response.getBody()).contains("\"id\"");
    }

    @Test
    void clients_forwardsAuthorizationHeader() {
        AtomicReference<String> capturedAuth = new AtomicReference<>();

        fakeAuthServer.removeContext("/api/clients");
        fakeAuthServer.createContext("/api/clients", exchange -> {
            capturedAuth.set(exchange.getRequestHeaders().getFirst("Authorization"));
            byte[] body = "[]".getBytes(StandardCharsets.UTF_8);
            exchange.getResponseHeaders().set("Content-Type", "application/json");
            exchange.sendResponseHeaders(200, body.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(body);
            }
        });

        try {
            HttpHeaders headers = new HttpHeaders();
            headers.set("Authorization", "Bearer test-token");
            restTemplate.exchange("/clients", HttpMethod.GET, new HttpEntity<>(headers), String.class);

            assertThat(capturedAuth.get()).isEqualTo("Bearer test-token");
        } finally {
            fakeAuthServer.removeContext("/api/clients");
            fakeAuthServer.createContext("/api/clients", exchange -> {
                byte[] body = "[{\"id\":1}]".getBytes(StandardCharsets.UTF_8);
                exchange.getResponseHeaders().set("Content-Type", "application/json");
                exchange.sendResponseHeaders(200, body.length);
                try (OutputStream os = exchange.getResponseBody()) {
                    os.write(body);
                }
            });
        }
    }

    @Test
    void clients_forwardsQueryString() {
        AtomicReference<String> capturedQuery = new AtomicReference<>();

        fakeAuthServer.removeContext("/api/clients");
        fakeAuthServer.createContext("/api/clients", exchange -> {
            capturedQuery.set(exchange.getRequestURI().getRawQuery());
            byte[] body = "[]".getBytes(StandardCharsets.UTF_8);
            exchange.getResponseHeaders().set("Content-Type", "application/json");
            exchange.sendResponseHeaders(200, body.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(body);
            }
        });

        try {
            restTemplate.getForEntity("/clients?page=2&limit=10", String.class);

            assertThat(capturedQuery.get()).isEqualTo("page=2&limit=10");
        } finally {
            fakeAuthServer.removeContext("/api/clients");
            fakeAuthServer.createContext("/api/clients", exchange -> {
                byte[] body = "[{\"id\":1}]".getBytes(StandardCharsets.UTF_8);
                exchange.getResponseHeaders().set("Content-Type", "application/json");
                exchange.sendResponseHeaders(200, body.length);
                try (OutputStream os = exchange.getResponseBody()) {
                    os.write(body);
                }
            });
        }
    }

    @Test
    void gateIssue_proxiesToAuthServer() {
        ResponseEntity<String> response = restTemplate.getForEntity("/gate/issue", String.class);

        assertThat(response.getStatusCode().value()).isEqualTo(200);
        assertThat(response.getBody()).contains("token");
    }

    @Test
    void gateVerify_includesIdentifierInPath() {
        AtomicReference<String> capturedPath = new AtomicReference<>();

        fakeAuthServer.removeContext("/api/gate/client/");
        fakeAuthServer.createContext("/api/gate/client/", exchange -> {
            capturedPath.set(exchange.getRequestURI().getPath());
            byte[] body = "{\"valid\":true}".getBytes(StandardCharsets.UTF_8);
            exchange.getResponseHeaders().set("Content-Type", "application/json");
            exchange.sendResponseHeaders(200, body.length);
            try (OutputStream os = exchange.getResponseBody()) {
                os.write(body);
            }
        });

        try {
            ResponseEntity<String> response = restTemplate.getForEntity(
                    "/gate/client/alpha-tech/verify", String.class);

            assertThat(response.getStatusCode().value()).isEqualTo(200);
            assertThat(capturedPath.get()).contains("alpha-tech");
        } finally {
            fakeAuthServer.removeContext("/api/gate/client/");
            fakeAuthServer.createContext("/api/gate/client/", exchange -> {
                byte[] body = "{\"valid\":true}".getBytes(StandardCharsets.UTF_8);
                exchange.getResponseHeaders().set("Content-Type", "application/json");
                exchange.sendResponseHeaders(200, body.length);
                try (OutputStream os = exchange.getResponseBody()) {
                    os.write(body);
                }
            });
        }
    }

    @Test
    void proxyGet_returns502OnUpstreamError() {
        // ポート 1 は到達不能なため接続エラーが発生し、コントローラーは 502 を返します。
        ReflectionTestUtils.setField(proxyController, "authServerUrl", "http://127.0.0.1:1");
        try {
            ResponseEntity<String> response = restTemplate.getForEntity("/clients", String.class);

            assertThat(response.getStatusCode().value()).isEqualTo(502);
        } finally {
            ReflectionTestUtils.setField(proxyController, "authServerUrl",
                    "http://localhost:" + fakeAuthPort);
        }
    }
}
