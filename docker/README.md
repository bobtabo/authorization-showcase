<p align="center">
<a href="https://www.docker.com/" target="_blank"><img src="https://findy-tools.io/public_images/tool_vendor/docker/logo_docker_square.png.png" height="72" alt="Docker"></a>
&nbsp;&nbsp;
<a href="https://nginx.org/" target="_blank"><img src="https://images.icon-icons.com/2699/PNG/512/nginx_logo_icon_169915.png" height="72" alt="nginx"></a>
</p>

<p align="center">
<a href="https://www.docker.com/"><img src="https://img.shields.io/badge/Docker-latest-1D63ED?logo=docker&logoColor=white" alt="Docker"></a>
<a href="https://nginx.org/"><img src="https://img.shields.io/badge/nginx_proxy-latest-009639?logo=nginx&logoColor=white" alt="nginx proxy"></a>
</p>

---

## :file_folder: ディレクトリ構成

| パス                                                         | 内容                                                         |
|------------------------------------------------------------|------------------------------------------------------------|
| [`local/app-go/`](local/app-go/)                           | Go（Gin）実行環境。`jwilder/nginx-proxy` 経由でホスト名で振り分ける。           |
| [`local/app-java/`](local/app-java/)                       | Java（Spring Boot）実行環境。`jwilder/nginx-proxy` 経由でホスト名で振り分ける。 |
| [`local/app-php-cake/`](local/app-php-cake/)               | PHP（CakePHP）実行環境。`jwilder/nginx-proxy` 経由でホスト名で振り分ける。      |
| [`local/app-php-codeigniter/`](local/app-php-codeigniter/) | PHP（CodeIgniter）実行環境。`jwilder/nginx-proxy` 経由でホスト名で振り分ける。  |
| [`local/app-php-fuel/`](local/app-php-fuel/)               | PHP（FuelPHP） 実行環境。`jwilder/nginx-proxy` 経由でホスト名で振り分ける。     |
| [`local/app-python/`](local/app-python/)                   | Python（Django）実行環境。`jwilder/nginx-proxy` 経由でホスト名で振り分ける。          |
| [`local/app-ruby/`](local/app-ruby/)                       | Ruby（Rails）実行環境。`jwilder/nginx-proxy` 経由でホスト名で振り分ける。       |
| [`local/common/`](local/common/)                           | 複数バックエンドで共有する共通インフラ。                                       |

`common` 側で Docker ネットワーク `showcase` を作成し、各 `docker-compose` はそのネットワークに参加します（`external: true`）。

## :white_check_mark: 前提

- Docker Engine および Docker Compose（`docker compose` または `docker-compose`）が使えること
- ポート **8443**（プロキシ）がローカルで空いていること（`.env` で変更可）

## :whale: 共通コンテナ操作

### 事前準備
```bash
cd docker

# 初回のみ: スクリプトに実行権限を付与
find ./bin -type f -exec chmod 755 {} +

# 初回のみ: 証明書・環境変数のセットアップ
bin/docker-common.sh env
```

### コンテナを起動する
```bash
# 起動（内部で showcase ネットワーク作成 + compose up）
bin/docker-common.sh up
```

### コンテナを停止する
```bash
bin/docker-common.sh stop
```

### コンテナを再開する
```bash
bin/docker-common.sh start
```

### コンテナを破棄する
```bash
# ボリュームや data も消えるので注意！
bin/docker-common.sh down
```

## :gear: アプリコンテナ操作

`common` でネットワークとプロキシが立ち上がった状態で、各アプリ環境を起動します。

### コンテナを起動する

```bash
# Go（Gin）環境を起動する
bin/docker-go.sh up

# Java（Spring Boot）環境を起動する
bin/docker-java.sh up

# PHP（CakePHP）環境を起動する
bin/docker-php-cake.sh up

# PHP（CodeIgniter）環境を起動する
bin/docker-php-codeigniter.sh up

# PHP（FuelPHP）環境を起動する
bin/docker-php-fuel.sh up

# Python（Django）環境を起動する
bin/docker-python.sh up

# Ruby（Rails）環境を起動する
bin/docker-ruby.sh up
```

### コンテナに入る

```bash
# Go（Gin）環境に入る
bin/docker-go.sh exec

# Java（Spring Boot）環境に入る
bin/docker-java.sh exec

# PHP（CakePHP）環境に入る
bin/docker-php-cake.sh exec

# PHP（CodeIgniter）環境に入る
bin/docker-php-codeigniter.sh exec

# PHP（FuelPHP）環境に入る
bin/docker-php-fuel.sh exec

# Python（Django）環境に入る
bin/docker-python.sh exec

# Ruby（Rails）環境に入る
bin/docker-ruby.sh exec
```

### コンテナを破棄する

```bash
# Go（Gin）環境を破棄する
bin/docker-go.sh down

# Java（Spring Boot）環境を破棄する
bin/docker-java.sh down

# PHP（CakePHP）環境を破棄する
bin/docker-php-cake.sh down

# PHP（CodeIgniter）環境を破棄する
bin/docker-php-codeigniter.sh down

# PHP（FuelPHP）環境を破棄する
bin/docker-php-fuel.sh down

# Python（Django）環境を破棄する
bin/docker-python.sh down

# Ruby（Rails）環境を破棄する
bin/docker-ruby.sh down
```

### 全コンテナを一括起動する

```bash
bin/docker-backends.sh up
```

### 全コンテナを一括破棄する

```bash
bin/docker-backends.sh down
```

## :fire: 注意

- `docker-xxx-down.sh` は **データディレクトリやログを削除する**処理が入っています。実行前に内容を確認してください。
- 証明書・パスワード類は **開発用サンプル**です。共有環境では流用しないでください。
