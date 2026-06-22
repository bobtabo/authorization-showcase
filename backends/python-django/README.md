<p align="center">
<a href="https://www.python.org/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/python/python-original.svg" height="72" alt="Python"></a>
&nbsp;&nbsp;
<a href="https://www.djangoproject.com/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/django/django-plain.svg" height="72" alt="Django"></a>
&nbsp;&nbsp;
<a href="https://www.django-rest-framework.org/" target="_blank"><img src="https://www.django-rest-framework.org/theme/img/logo.png" height="72" alt="Django REST Framework"></a>
</p>

<p align="center">
<a href="https://www.python.org/"><img src="https://img.shields.io/badge/Python-3.13-3776AB?logo=python&logoColor=white" alt="Python 3.13"></a>
<a href="https://www.djangoproject.com/"><img src="https://img.shields.io/badge/Django-6.x-092E20?logo=django&logoColor=white" alt="Django 6.x"></a>
<a href="https://www.django-rest-framework.org/"><img src="https://img.shields.io/badge/Django_REST_Framework-3.x-A30000?logo=django&logoColor=white" alt="Django REST Framework 3.x"></a>
</p>

---

## :book: 概要

認可サーバー API（JWT 発行・検証）を利用する **Python + Django** 実装のショーケースです。  
フロントエンドからのリクエストを認可サーバー（`AUTH_SERVER_URL`）に転送し、JWT 発行・検証を行います。

---

## :package: 技術スタック

| 項目 | バージョン |
|:----|:---------|
| Python | 3.13 |
| Django | 6.x |
| Django REST Framework | 3.x |

---

## :rocket: セットアップ

### Docker で起動

```bash
cd docker
bin/docker-python.sh up
bin/docker-python.sh exec
pip install -r requirements.txt
cp .env.example .env
```

---

## :white_check_mark: テスト

```bash
# 依存パッケージインストール
pip install -r requirements.txt

# テスト実行
AUTH_SERVER_URL=https://ample-precise-knee.ngrok-free.dev/restapis/{api-id}/local/_user_request_ python manage.py test
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
