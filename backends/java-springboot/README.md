<p align="center">
<a href="https://www.java.com/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/java/java-original.svg" height="72" alt="Java"></a>
&nbsp;&nbsp;
<a href="https://spring.io/projects/spring-boot" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/spring/spring-original.svg" height="72" alt="Spring Boot"></a>
</p>

<p align="center">
<a href="https://www.java.com/"><img src="https://img.shields.io/badge/Java-21-ED8B00?logo=openjdk&logoColor=white" alt="Java 21"></a>
<a href="https://spring.io/projects/spring-boot"><img src="https://img.shields.io/badge/Spring_Boot-4.0-6DB33F?logo=springboot&logoColor=white" alt="Spring Boot 4.0"></a>
</p>

---

## :book: 概要

認可サーバー API（JWT 発行・検証）を利用する **Java + Spring Boot** 実装のショーケースです。  
フロントエンドからのリクエストを認可サーバー（`AUTH_SERVER_URL`）に転送し、JWT 発行・検証を行います。

---

## :package: 技術スタック

| 項目 | バージョン |
|:----|:---------|
| Java | 21 |
| Spring Boot | 4.0 |
| Gradle | 9.x |

---

## :rocket: セットアップ

### Docker で起動

```bash
cd docker
bin/docker-java.sh up
bin/docker-java.sh exec
cp .env.example .env
```

---

## :white_check_mark: テスト

```bash
# テスト実行
AUTH_SERVER_URL=https://ample-precise-knee.ngrok-free.dev/restapis/{api-id}/local/_user_request_ ./gradlew test
```

テストは `AUTH_SERVER_URL` で指定した認可サーバーに対して実際にリクエストを送るインテグレーションテストです。

---

## :gear: 環境変数

| 変数名 | デフォルト値 | 説明 |
|:------|:-----------|:----|
| `AUTH_SERVER_URL` | `http://host.docker.internal:4566/restapis/{api-id}/local/_user_request_` | 転送先認可サーバーの URL |

---

## :link: リンク

- [バックエンド一覧](../README.md)
- [リポジトリルート](../../README.md)
