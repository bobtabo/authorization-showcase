<p align="center">
<a href="https://www.ruby-lang.org/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/ruby/ruby-original.svg" height="72" alt="Ruby"></a>
&nbsp;&nbsp;
<a href="https://rubyonrails.org/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/rails/rails-plain.svg" height="72" alt="Ruby on Rails"></a>
</p>

<p align="center">
<a href="https://www.ruby-lang.org/"><img src="https://img.shields.io/badge/Ruby-3.4-CC342D?logo=ruby&logoColor=white" alt="Ruby 3.4"></a>
<a href="https://rubyonrails.org/"><img src="https://img.shields.io/badge/Rails-8.1-CC0000?logo=rubyonrails&logoColor=white" alt="Rails 8.1"></a>
</p>

---

## :book: 概要

認可サーバー API（JWT 発行・検証）を利用する **Ruby + Ruby on Rails** 実装のショーケースです。  
フロントエンドからのリクエストを認可サーバー（`AUTH_SERVER_URL`）に転送し、JWT 発行・検証を行います。

---

## :package: 技術スタック

| 項目 | バージョン |
|:----|:---------|
| Ruby | 3.4 |
| Rails | 8.1 |

---

## :rocket: セットアップ

### Docker で起動

```bash
cd docker
bin/docker-ruby.sh up
bin/docker-ruby.sh exec
bundle install
cp .env.example .env
```

---

## :white_check_mark: テスト

```bash
# 依存 gem インストール
bundle install

# テスト実行
AUTH_SERVER_URL=https://ample-precise-knee.ngrok-free.dev/restapis/{api-id}/local/_user_request_ bundle exec rails test test/controllers/
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
