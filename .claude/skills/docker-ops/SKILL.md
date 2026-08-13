---
name: docker-ops
description: >-
  docker/bin/docker-*.sh の使い方（env/up/down/exec 等）でコンテナを起動・停止・
  操作する際、またはコンテナが起動しない等のトラブル対応をする際に使う。
allowed-tools: Bash(docker:*), Bash(cd:*), Bash(find:*)
---

# docker-ops

`docker/bin/*.sh` は各バックエンド・共通インフラのDocker操作を統一するラッパー
スクリプト。コンテナの起動・停止・破棄（`up`/`down`/`start`/`stop`）は必ずこれらを
経由する（`guard-bash.sh` が `docker stop` / `docker compose stop|kill|down` 等の
直接実行をブロックし、本Skillのラッパー使用へ誘導する）。

一方、起動済みコンテナに対する非対話の単発コマンド実行（`exec`）は
`guard-bash.sh` のブロック対象外であり、`docker compose -p showcase-<x> -f
docker/local/app-<x>/docker-compose.yml exec --user 1000 <service> <コマンド>`
を直接使ってよい（backend-dispatch Skill参照）。

## 前提（初回のみ）

```bash
cd docker
find ./bin -type f -exec chmod 755 {} +
bin/docker-common.sh env   # 証明書・.envのセットアップ
```

## 起動順序

Docker ネットワーク `showcase` と nginx-proxy を作る `common` を先に起動し、
その後で各バックエンドを起動する。順序を逆にすると起動に失敗する。

```bash
cd docker
bin/docker-common.sh up          # 1. 共通コンテナ（nginx-proxy, showcaseネットワーク作成）
bin/docker-backends.sh up        # 2. 全バックエンド一括起動
# もしくは個別に:
bin/docker-go.sh up
bin/docker-java.sh up
bin/docker-php-cake.sh up
bin/docker-php-codeigniter.sh up
bin/docker-php-fuel.sh up
bin/docker-python.sh up
bin/docker-ruby.sh up
```

各 `docker-<x>.sh up` は `.env` が無ければ `.env.example` から自動生成してから
`docker compose -p showcase-<x> up -d --build --force-recreate` を実行する。

## コンテナに入る／破棄する

```bash
docker/bin/docker-<x>.sh exec   # コンテナ内シェルに入る（Goはsh、他はbash）
docker/bin/docker-<x>.sh down   # そのバックエンドのコンテナ・イメージ・ボリュームを破棄
docker/bin/docker-backends.sh down   # 全バックエンド一括破棄
```

`common` の操作は `up` / `down` に加えて `start` / `stop`（コンテナ破棄せず
起動・停止のみ）も使える:

```bash
docker/bin/docker-common.sh start
docker/bin/docker-common.sh stop
```

## 破壊的操作への注意

- `down` は対象コンテナに加えて **イメージ・ボリュームも削除**する
  （`--rmi all --volumes`）。
- `docker-common.sh down` はさらに `docker/local/common/data/` と
  `docker/local/common/logs/` ディレクトリも削除する。
- 上記のためユーザーの明示的な指示なしに `down` を実行しない。コンテナの
  再起動だけで済む場合は `common` は `stop`→`start`、各バックエンドは
  `up`（`--force-recreate` で再作成される）を使う。

## トラブルシュート

- **バックエンドコンテナが起動しない**: `common` が起動しておらず `showcase`
  ネットワークが無い可能性が高い。`docker/bin/docker-common.sh up` を先に実行する。
- **状態確認**: `docker ps` でコンテナ名 `showcase-<x>_<service>`
  （例: `showcase-go_go`, `showcase-go_nginx`）が上がっているか確認する。
- **.env が古い/壊れている**: バックエンド側の `.env` は `docker/local/app-<x>/.env`
  にあり、削除して `docker/bin/docker-<x>.sh up` を再実行すれば
  `.env.example` から再生成される。
- どのバックエンドがどのディレクトリ・スクリプトに対応するかは backend-dispatch
  Skillを参照。
