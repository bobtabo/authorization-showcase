<p align="center">
<a href="https://www.php.net/releases/7_4_0.php" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" height="72" alt="PHP"></a>
&nbsp;&nbsp;
<a href="https://fuelphp.com/" target="_blank"><img src="https://avatars.githubusercontent.com/u/1149176" height="72" alt="FuelPHP"></a>
</p>

<p align="center">
<a href="https://www.php.net/releases/7_4_0.php"><img src="https://img.shields.io/badge/PHP-7.4-777BB4?logo=php&logoColor=white" alt="PHP 7.4"></a>
<a href="https://fuelphp.com/"><img src="https://img.shields.io/badge/FuelPHP-1.9-orange" alt="FuelPHP 1.9"></a>
</p>

---

## :book: 概要

認可サーバー API（JWT 発行・検証）を利用する **PHP 7.4 + FuelPHP** 実装のショーケースです。  
フロントエンドからのリクエストを認可サーバー（`AUTH_SERVER_URL`）に転送し、JWT 発行・検証を行います。

---

## :package: 技術スタック

| 項目 | バージョン |
|:----|:---------|
| PHP | 7.4 |
| FuelPHP | 1.9 |
| PHPUnit | 11.x |

---

## :rocket: セットアップ

### Docker で起動

```bash
cd docker
bin/docker-php-fuel.sh up
bin/docker-php-fuel.sh exec
cp .env.example .env
```

---

## :white_check_mark: テスト

```bash
# 依存パッケージインストール
composer install

# テスト実行
AUTH_SERVER_URL=https://ample-precise-knee.ngrok-free.dev/restapis/{api-id}/local/_user_request_ ./fuel/vendor/bin/phpunit -c fuel/core/phpunit.xml --testsuite app
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
