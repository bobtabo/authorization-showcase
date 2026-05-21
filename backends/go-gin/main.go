// 認可サーバーへのリバースプロキシを提供するエントリーポイントです。
package main

import (
	"io"
	"net/http"
	"os"

	"github.com/gin-gonic/gin"
)

// authServerURL は環境変数から認可サーバーの URL を取得します。
func authServerURL() string {
	if url := os.Getenv("AUTH_SERVER_URL"); url != "" {
		return url
	}
	return "http://host.docker.internal:8080/function/php"
}

// proxyGet は指定 URL に GET リクエストを転送し、レスポンスをそのまま返します。
func proxyGet(c *gin.Context, targetURL string) {
	req, err := http.NewRequest(http.MethodGet, targetURL, nil)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	req.Header.Set("Accept", "application/json")
	if auth := c.GetHeader("Authorization"); auth != "" {
		req.Header.Set("Authorization", auth)
	}

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		c.JSON(http.StatusBadGateway, gin.H{"error": err.Error()})
		return
	}
	defer resp.Body.Close()

	body, _ := io.ReadAll(resp.Body)
	ct := resp.Header.Get("Content-Type")
	if ct == "" {
		ct = "application/json"
	}
	c.Data(resp.StatusCode, ct, body)
}

// buildURL はベース URL・パス・クエリ文字列を結合して完全な URL を返します。
func buildURL(base, path, query string) string {
	url := base + "/" + path
	if query != "" {
		url += "?" + query
	}
	return url
}

// newRouter はルーターを生成して返します。
func newRouter(base string) *gin.Engine {
	r := gin.Default()

	r.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"status": "ok"})
	})

	r.GET("/clients", func(c *gin.Context) {
		proxyGet(c, buildURL(base, "api/clients", c.Request.URL.RawQuery))
	})

	r.GET("/gate/issue", func(c *gin.Context) {
		proxyGet(c, buildURL(base, "api/gate/issue", c.Request.URL.RawQuery))
	})

	r.GET("/gate/client/:identifier/verify", func(c *gin.Context) {
		identifier := c.Param("identifier")
		proxyGet(c, buildURL(base, "api/gate/client/"+identifier+"/verify", c.Request.URL.RawQuery))
	})

	return r
}

// main はルーターを起動します。
func main() {
	newRouter(authServerURL()).Run(":8080")
}
