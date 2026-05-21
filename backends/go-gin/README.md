<p align="center">
<a href="https://go.dev/" target="_blank"><img src="https://go.dev/blog/go-brand/Go-Logo/PNG/Go-Logo_Blue.png" height="72" alt="Go"></a>
&nbsp;&nbsp;
<a href="https://gin-gonic.com/" target="_blank"><img src="https://raw.githubusercontent.com/gin-gonic/logo/master/color.png" height="72" alt="Gin"></a>
</p>

<p align="center">
<a href="https://go.dev/"><img src="https://img.shields.io/badge/Go-1.25-00ADD8?logo=go&logoColor=white" alt="Go 1.25"></a>
<a href="https://gin-gonic.com/"><img src="https://img.shields.io/badge/Gin-1.12-00ADD8?logo=go&logoColor=white" alt="Gin 1.12"></a>
</p>

---

## :book: 概要

認可サーバー API（JWT 発行・検証）を利用する **Go + Gin** 実装のショーケースです。  
フロントエンドからのリクエストを認可サーバー（`AUTH_SERVER_URL`）に転送し、JWT 発行・検証を行います。

---

## :package: 技術スタック

| 項目 | バージョン |
|:----|:---------|
| Go | 1.25 |
| Gin | 1.12 |

---

## :rocket: セットアップ

### Docker で起動

```bash
cd docker
bin/docker-go.sh up
bin/docker-go.sh exec
cp .env.example .env
```

---

## :white_check_mark: テスト

```bash
# 依存パッケージ取得
go mod download

# テスト実行
AUTH_SERVER_URL=https://<ngrok-url>/function/php go test ./...
```

テストは `AUTH_SERVER_URL` で指定した認可サーバーに対して実際にリクエストを送るインテグレーションテストです。  
`AUTH_SERVER_URL` が未設定の場合はテストをスキップします。

---

## :gear: 環境変数

| 変数名 | デフォルト値 | 説明 |
|:------|:-----------|:----|
| `AUTH_SERVER_URL` | `http://host.docker.internal:8080/function/php` | 転送先認可サーバーの URL |

---

## :link: リンク

- [バックエンド一覧](../README.md)
- [リポジトリルート](../../README.md)
