package main

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
)

// startFakeAuthServer は認可サーバーの代替となるテスト用 HTTP サーバーを起動します。
// handler には受け取ったリクエストを検証・応答するロジックを渡してください。
func startFakeAuthServer(handler http.HandlerFunc) *httptest.Server {
	return httptest.NewServer(handler)
}

func newTestRouter(fakeServerURL string) http.Handler {
	os.Setenv("AUTH_SERVER_URL", fakeServerURL)
	return newRouter(fakeServerURL)
}

func TestHealth(t *testing.T) {
	r := newRouter("http://unused")
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/health", nil)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d", w.Code)
	}
	var body map[string]string
	if err := json.Unmarshal(w.Body.Bytes(), &body); err != nil {
		t.Fatalf("invalid JSON: %v", err)
	}
	if body["status"] != "ok" {
		t.Errorf("expected status ok, got %q", body["status"])
	}
}

func TestClients(t *testing.T) {
	fake := startFakeAuthServer(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/api/clients" {
			http.NotFound(w, r)
			return
		}
		w.Header().Set("Content-Type", "application/json")
		w.Write([]byte(`[{"id":1}]`))
	})
	defer fake.Close()

	r := newRouter(fake.URL)
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/clients", nil)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d", w.Code)
	}
	if w.Body.String() != `[{"id":1}]` {
		t.Errorf("unexpected body: %s", w.Body.String())
	}
}

func TestClients_ForwardsAuthHeader(t *testing.T) {
	var receivedAuth string
	fake := startFakeAuthServer(func(w http.ResponseWriter, r *http.Request) {
		receivedAuth = r.Header.Get("Authorization")
		w.Header().Set("Content-Type", "application/json")
		w.Write([]byte(`[]`))
	})
	defer fake.Close()

	r := newRouter(fake.URL)
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/clients", nil)
	req.Header.Set("Authorization", "Bearer test-token")

	r.ServeHTTP(w, req)

	if receivedAuth != "Bearer test-token" {
		t.Errorf("expected Authorization to be forwarded, got %q", receivedAuth)
	}
}

func TestClients_QueryString(t *testing.T) {
	var receivedQuery string
	fake := startFakeAuthServer(func(w http.ResponseWriter, r *http.Request) {
		receivedQuery = r.URL.RawQuery
		w.Header().Set("Content-Type", "application/json")
		w.Write([]byte(`[]`))
	})
	defer fake.Close()

	r := newRouter(fake.URL)
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/clients?page=2&limit=10", nil)

	r.ServeHTTP(w, req)

	if receivedQuery != "page=2&limit=10" {
		t.Errorf("expected query string to be forwarded, got %q", receivedQuery)
	}
}

func TestGateIssue(t *testing.T) {
	var receivedPath string
	fake := startFakeAuthServer(func(w http.ResponseWriter, r *http.Request) {
		receivedPath = r.URL.Path
		w.Header().Set("Content-Type", "application/json")
		w.Write([]byte(`{"token":"abc"}`))
	})
	defer fake.Close()

	r := newRouter(fake.URL)
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/gate/issue", nil)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d", w.Code)
	}
	if receivedPath != "/api/gate/issue" {
		t.Errorf("expected upstream path /api/gate/issue, got %q", receivedPath)
	}
}

func TestGateVerify(t *testing.T) {
	var receivedPath string
	fake := startFakeAuthServer(func(w http.ResponseWriter, r *http.Request) {
		receivedPath = r.URL.Path
		w.Header().Set("Content-Type", "application/json")
		w.Write([]byte(`{"valid":true}`))
	})
	defer fake.Close()

	r := newRouter(fake.URL)
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/gate/client/alpha-tech/verify", nil)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d", w.Code)
	}
	if receivedPath != "/api/gate/client/alpha-tech/verify" {
		t.Errorf("expected upstream path to include alpha-tech, got %q", receivedPath)
	}
}

func TestProxyGet_UpstreamError(t *testing.T) {
	// ポートに何もバインドされていないアドレスを指定することで到達不能な上流を再現します。
	r := newRouter("http://127.0.0.1:1")
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/clients", nil)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusBadGateway {
		t.Fatalf("expected 502, got %d", w.Code)
	}
}
