package com.authorization.showcase;

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

import static org.assertj.core.api.Assertions.assertThat;

@SpringBootTest(webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT)
class ProxyControllerTest {

    static final String BEARER_TOKEN = "Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369";
    static final String IDENTIFIER   = "alpha-tech";
    static final String MEMBER       = "M000001";

    @Autowired
    private TestRestTemplate restTemplate;

    @Autowired
    private ProxyController proxyController;

    @DynamicPropertySource
    static void authServerProperties(DynamicPropertyRegistry registry) {
        String url = System.getenv("AUTH_SERVER_URL");
        if (url != null && !url.isEmpty()) {
            registry.add("auth.server.url", () -> url);
        }
    }

    private HttpHeaders authHeaders() {
        HttpHeaders headers = new HttpHeaders();
        headers.set("Authorization", BEARER_TOKEN);
        return headers;
    }

    @Test
    void health_returns200() {
        ResponseEntity<String> response = restTemplate.getForEntity("/health", String.class);

        assertThat(response.getStatusCode().value()).isEqualTo(200);
        assertThat(response.getBody()).contains("\"status\"", "\"ok\"");
    }

    @Test
    void clients_returns_json_array() {
        ResponseEntity<String> response = restTemplate.exchange(
                "/clients?statuses[]=2", HttpMethod.GET, new HttpEntity<>(authHeaders()), String.class);

        assertThat(response.getStatusCode().value()).isEqualTo(200);
        assertThat(response.getBody()).startsWith("[");
    }

    @Test
    void gateIssue_returns_token() {
        ResponseEntity<String> response = restTemplate.exchange(
                "/gate/issue?member=" + MEMBER, HttpMethod.GET, new HttpEntity<>(authHeaders()), String.class);

        assertThat(response.getStatusCode().value()).isEqualTo(200);
        assertThat(response.getBody()).contains("token");
    }

    @Test
    void gateVerify_returns_payload() {
        ResponseEntity<String> issueResp = restTemplate.exchange(
                "/gate/issue?member=" + MEMBER, HttpMethod.GET, new HttpEntity<>(authHeaders()), String.class);
        assertThat(issueResp.getStatusCode().value()).isEqualTo(200);

        String body = issueResp.getBody();
        int start = body.indexOf("\"token\":");
        assertThat(start).isGreaterThan(-1);
        String jwt = body.substring(start + 9).replaceAll("^\"|\".*", "");

        ResponseEntity<String> verifyResp = restTemplate.exchange(
                "/gate/client/" + IDENTIFIER + "/verify?token=" + jwt,
                HttpMethod.GET, new HttpEntity<>(authHeaders()), String.class);

        assertThat(verifyResp.getStatusCode().value()).isEqualTo(200);
    }

    @Test
    void proxy_returns_502_on_upstream_error() {
        ReflectionTestUtils.setField(proxyController, "authServerUrl", "http://127.0.0.1:1");
        try {
            ResponseEntity<String> response = restTemplate.exchange(
                    "/clients", HttpMethod.GET, new HttpEntity<>(authHeaders()), String.class);

            assertThat(response.getStatusCode().value()).isEqualTo(502);
        } finally {
            String url = System.getenv("AUTH_SERVER_URL");
            String restore = (url != null && !url.isEmpty()) ? url : "http://host.docker.internal:8080/function/php";
            ReflectionTestUtils.setField(proxyController, "authServerUrl", restore);
        }
    }
}
