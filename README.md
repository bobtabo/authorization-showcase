# <img src="https://raw.githubusercontent.com/bobtabo/authorization/main/frontend/app/icon.svg" height="25" style="margin-top:-4px;vertical-align:middle;" alt="認可サーバー"> 認可サーバー（JWT発行／検証）

このリポジトリは、認可サーバー API（JWT 発行／検証）を複数の言語・フレームワークで利用するショーケースです。  
各コンポーネントの詳細は、それぞれのディレクトリにあるドキュメントを参照してください。

---

## :clipboard: 目次

- [デモ](#デモ)
- [システム構造](#システム構造)
- [プロジェクト構成](#プロジェクト構成)
- [開発環境構築手順](#開発環境構築手順)
  - [前提](#前提)
  - [1. リポジトリのクローン](#1-リポジトリのクローン)
  - [2. 初回セットアップ](#2-初回セットアップ)
  - [3. 共通コンテナの起動](#3-共通コンテナの起動nginx-proxy)
  - [4. バックエンドコンテナの起動](#4-バックエンドコンテナの起動)
  - [5. フロントエンドの起動](#5-フロントエンドの起動)
  - [6. バックエンドの初期設定](#6-バックエンドの初期設定)
    - [6.1 Go（Gin）](#61-gogin)
    - [6.2 Java（Spring Boot）](#62-javaspring-boot)
    - [6.3 PHP（CakePHP）](#63-phpcakephp)
    - [6.4 PHP（CodeIgniter）](#64-phpcodeigniter)
    - [6.5 PHP（FuelPHP）](#65-phpfuelphp)
    - [6.6 Python（Django）](#66-pythondjango)
    - [6.7 Ruby（Rails）](#67-rubyrails)
  - [7. ブラウザで開く](#7-ブラウザで開く)

---

## :clapper: デモ

<p align="center">
  <img src="./docs/gif/jwt-flow.gif" alt="JWT発行／検証フローのデモ" width="800">
</p>

デモの作成方法は [e2e/README.md](./e2e/README.md) を参照してください。

---

## :building_construction: システム構造

```.
├── 📂 backends/           # バックエンド構成（PHP / Go / Java / Python / Ruby）
│   ├── go-gin/
│   ├── java-springboot/
│   ├── php-cakephp/
│   ├── php-codeigniter/
│   ├── php-fuelphp/
│   ├── python-django/
│   └── ruby-rails/
├── 📂 docker/             # コンテナ定義
├── 📂 docs/
│   └── gif/               # デモ GIF
├── 📂 e2e/                # 動作デモ GIF 録画（Playwright）
├── 📂 frontend/           # 認可管理画面（Vue.js / Nuxt.js）
└── 📜 README.md
```

---

## :file_folder: プロジェクト構成

| ディレクトリ              | 内容                              | ドキュメント                                 |
|:--------------------|:--------------------------------|:---------------------------------------|
| **`backends/`**     | 認可サーバー API 利用バックエンド（言語・FW 別）   | [README.md](./backends/README.md)      |
| **`docker/`**       | コンテナ定義                          | [README.md](./docker/README.md)        |
| **`e2e/`**          | 動作デモ GIF 録画（Playwright）          | [README.md](./e2e/README.md)           |
| **`frontend/`**     | JWTサンプル画面（Vue.js / Nuxt.js）     | [README.md](./frontend/README.md)      |

---

## :hammer_and_wrench: 開発環境構築手順

### 前提

- [認可サーバー](https://github.com/bobtabo/authorization) が **localstack モード**で起動していること（`BACKEND_MODE=localstack` がデフォルト）
- [AWS CLI](https://docs.aws.amazon.com/ja_jp/cli/latest/userguide/getting-started-install.html) がインストール済みで、LocalStack 用プロファイル（`localstack`）が設定済みであること

  > [!NOTE]
  > LocalStack 用のプロファイル設定：
  > ```bash
  > aws configure --profile localstack
  > # AWS Access Key ID: test
  > # AWS Secret Access Key: test
  > # Default region name: ap-northeast-1
  > # Default output format: json
  > ```

### 1. リポジトリのクローン

```bash
git clone git@github.com:bobtabo/authorization-showcase.git
cd authorization-showcase
```

### 2. 初回セットアップ

```bash
cd docker
find ./bin -type f -exec chmod 755 {} +
bin/docker-common.sh env
```

認可サーバーの API Gateway ID を取得して環境ファイルを生成します。

```bash
bash ../scripts/setup-local-env.sh
```

### 3. 共通コンテナの起動（Nginx Proxy）

```bash
bin/docker-common.sh up
```

### 4. バックエンドコンテナの起動

```bash
bin/docker-backends.sh up
```

### 5. フロントエンドの起動

```bash
cd frontend
npm install
npm run dev
```

### 6. バックエンドの初期設定

各バックエンドで環境変数の設定が必要です。</br>
使用するバックエンドのみ実施してください。

#### 6.1 Go（Gin）

```bash
bin/docker-go.sh exec
cp .env.example .env
```

#### 6.2 Java（Spring Boot）

```bash
bin/docker-java.sh exec
cp .env.example .env
```

#### 6.3 PHP（CakePHP）

```bash
bin/docker-php-cake.sh exec
cp .env.example .env
```

#### 6.4 PHP（CodeIgniter)

```bash
bin/docker-php-codeigniter.sh exec
cp .env.example .env
```

#### 6.5 PHP（FuelPHP）

```bash
bin/docker-php-fuel.sh exec
cp .env.example .env
```

#### 6.6 Python（Django）

```bash
bin/docker-python.sh exec
pip install -r requirements.txt
cp .env.example .env
```

#### 6.7 Ruby（Rails）

```bash
bin/docker-ruby.sh exec
bundle install
cp .env.example .env
```

### 7. ブラウザで開く

http://localhost:5173
