// This is a program developed by BobTabo.
//
// Copyright (c) 2026 BobTabo. All Rights Reserved.

package main

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
)

const (
	testBearerToken = "Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369"
	testIdentifier  = "alpha-tech"
	testMember      = "M000001"
)

func authURL(t *testing.T) string {
	t.Helper()
	base := os.Getenv("AUTH_SERVER_URL")
	if base == "" {
		t.Skip("AUTH_SERVER_URL not set")
	}
	return base
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
	r := newRouter(authURL(t))
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/clients?statuses[]=2", nil)
	req.Header.Set("Authorization", testBearerToken)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d: %s", w.Code, w.Body.String())
	}
	var clients []map[string]interface{}
	if err := json.Unmarshal(w.Body.Bytes(), &clients); err != nil {
		t.Fatalf("expected JSON array: %v\nbody: %s", err, w.Body.String())
	}
	if len(clients) == 0 {
		t.Error("expected at least one client")
	}
}

func TestGateIssue(t *testing.T) {
	r := newRouter(authURL(t))
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/gate/issue?member="+testMember, nil)
	req.Header.Set("Authorization", testBearerToken)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d: %s", w.Code, w.Body.String())
	}
	var body map[string]interface{}
	if err := json.Unmarshal(w.Body.Bytes(), &body); err != nil {
		t.Fatalf("expected JSON: %v\nbody: %s", err, w.Body.String())
	}
	if _, ok := body["token"]; !ok {
		t.Errorf("expected 'token' in response: %s", w.Body.String())
	}
}

func TestGateVerify(t *testing.T) {
	r := newRouter(authURL(t))

	issueW := httptest.NewRecorder()
	issueReq := httptest.NewRequest(http.MethodGet, "/gate/issue?member="+testMember, nil)
	issueReq.Header.Set("Authorization", testBearerToken)
	r.ServeHTTP(issueW, issueReq)
	if issueW.Code != http.StatusOK {
		t.Fatalf("issue failed %d: %s", issueW.Code, issueW.Body.String())
	}
	var issued map[string]interface{}
	json.Unmarshal(issueW.Body.Bytes(), &issued)
	jwt, _ := issued["token"].(string)
	if jwt == "" {
		t.Fatalf("no token in issue response: %s", issueW.Body.String())
	}

	verifyW := httptest.NewRecorder()
	verifyReq := httptest.NewRequest(http.MethodGet, "/gate/client/"+testIdentifier+"/verify?token="+jwt, nil)
	verifyReq.Header.Set("Authorization", testBearerToken)
	r.ServeHTTP(verifyW, verifyReq)

	if verifyW.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d: %s", verifyW.Code, verifyW.Body.String())
	}
}

func TestUpstreamError(t *testing.T) {
	r := newRouter("http://127.0.0.1:1")
	w := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/clients", nil)

	r.ServeHTTP(w, req)

	if w.Code != http.StatusBadGateway {
		t.Fatalf("expected 502, got %d", w.Code)
	}
}
