<p align="center">
<a href="https://www.php.net/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" height="72" alt="PHP"></a>
&nbsp;&nbsp;
<a href="https://cakephp.org/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/cakephp/cakephp-original.svg" height="72" alt="CakePHP"></a>
</p>

<p align="center">
<a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white" alt="PHP 8.4"></a>
<a href="https://cakephp.org/"><img src="https://img.shields.io/badge/CakePHP-5.3-D33C43?logo=cakephp&logoColor=white" alt="CakePHP 5.3"></a>
</p>

---

## :book: 概要

認可サーバー API（JWT 発行・検証）を利用する **PHP + CakePHP** 実装のショーケースです。  
フロントエンドからのリクエストを認可サーバー（`AUTH_SERVER_URL`）に転送し、JWT 発行・検証を行います。

---

## :package: 技術スタック

| 項目 | バージョン |
|:----|:---------|
| PHP | 8.4 |
| CakePHP | 5.3 |
| PHPUnit | 13.x |

---

## :rocket: セットアップ

### Docker で起動

```bash
cd docker
bin/docker-php-cake.sh up
bin/docker-php-cake.sh exec
cp .env.example .env
```

---

## :white_check_mark: テスト

```bash
# 依存パッケージインストール
composer install

# テスト実行
AUTH_SERVER_URL=https://ample-precise-knee.ngrok-free.dev/restapis/{api-id}/local/_user_request_ ./vendor/bin/phpunit
```

テストは `AUTH_SERVER_URL` で指定した認可サーバーに対して実際にリクエストを送るインテグレーションテストです。

---

## :gear: 環境変数

| 変数名 | デフォルト値 | 説明 |
|:------|:-----------|:----|
| `AUTH_SERVER_URL` | `http://localstack:4566/restapis/{api-id}/local/_user_request_` | 転送先認可サーバーの URL |

---

## :link: リンク

- [バックエンド一覧](../README.md)
- [リポジトリルート](../../README.md)
