---
name: new-backend-scaffold
description: >-
  新しい言語/フレームワークのバックエンドをこのリポジトリに追加する際に使う。
  既存7バックエンド（Go/Java/PHP×3/Python/Ruby）と同じ構成（README/.env.example/
  docker-compose.yml/CIワークフロー）で雛形を揃える。
allowed-tools: Bash(git:*), Bash(cp:*), Bash(mkdir:*)
---

# new-backend-scaffold

新規バックエンド `<name>`（例: `node-express`）を、既存実装と同じ構成で追加する
手順。最小構成の `go-gin` を雛形として使う（アプリコンテナ1つ + nginxの2サービス
構成で、他バックエンドも基本的に同じ形）。

## 前提

- 事前に対応するGitHub Issueを作成し、git-flow Skillで `feature/issue-N`
  ブランチを作成しておく。

## 1. バックエンドソース（`backends/<name>/`）

```bash
mkdir -p backends/<name>
```

- アプリのソース一式を配置する。
- `backends/<name>/README.md` を作成する（`backends/go-gin/README.md` の構成
  ―概要／起動方法／エンドポイント一覧―に合わせる）。

## 2. Docker実行環境（`docker/local/app-<name>/`）

`docker/local/app-go/` を丸ごとコピーして書き換える。

```bash
cp -r docker/local/app-go docker/local/app-<name>
```

書き換える点:

- `docker-compose.yml`
  - `name: showcase-app-<name>`
  - サービス名・イメージのbuild文脈（`go/` ディレクトリ相当）を言語用に差し替え
  - アプリ側の `volumes` を `./../../../backends/<name>:/var/www/${APP_NAME}` に
  - `networks.showcase.external: true` はそのまま維持（`common` が作るネットワーク
    に参加する）
  - `nginx` サービス定義（`VIRTUAL_HOST` / `VIRTUAL_PORT` 等の環境変数連携）は
    ほぼそのまま流用可
- `.env.example`
  - `APP_NAME=app-<name>`
  - `SERVER_NAME=apis.showcase-<name>.dev`
  - `NGINX_CONTAINER=showcase-<name>_nginx`
  - 言語コンテナの `<LANG>_CONTAINER=showcase-<name>_<service>` を追加
  - デバッグポート等、言語固有の変数を追加
- `nginx/`（`Dockerfile` / `nginx.conf` / `templates/default.conf.template`）は
  ほぼそのまま流用できる（`APP_PATH` 等の環境変数経由でルーティングするため）

## 3. Dockerラッパースクリプト（`docker/bin/docker-<name>.sh`）

`docker/bin/docker-go.sh` をコピーして書き換える。

```bash
cp docker/bin/docker-go.sh docker/bin/docker-<name>.sh
chmod 755 docker/bin/docker-<name>.sh
```

書き換える点: `cd` 先を `app-<name>`、`-p showcase-<name>`、`exec` で入る
サービス名・シェル（bash推奨、必要ならsh）。

さらに `docker/bin/docker-backends.sh` の `run()` に1行追加する:

```bash
bash "${SCRIPT_DIR}/docker-<name>.sh" "${ARG}"
```

## 4. CIワークフロー（`.github/workflows/<name>-ci.yml`）

`.github/workflows/go-gin-ci.yml` をコピーして書き換える。

```bash
cp .github/workflows/go-gin-ci.yml .github/workflows/<name>-ci.yml
```

書き換える点:
- `paths: - 'backends/<name>/**'`
- `branches-ignore: ['feature/issue-*']` と `concurrency.group:
  shared-authserver-tunnel` は変更しない（全バックエンド共有のngrok/LocalStack
  輻輳防止のため）
- 言語ツールチェインのセットアップステップ（`actions/setup-go` 相当）
- `Resolve AUTH_SERVER_URL` ステップは他バックエンドと同一ロジックのため、
  そのまま流用する（LocalStackのAPI Gateway IDをngrok経由で動的取得する仕組み）
- テスト実行コマンド・`working-directory: backends/<name>` のみ差し替え

新規追加したワークフローファイルは `main` にマージされるまで `workflow_dispatch`
での手動発火ができない（GitHubの仕様。詳細は backend-ci-trigger Skillの当該
注記を参照）。featureブランチ段階でのCI確認は、develop→main同期後まで待つか、
ワークフローファイルのみ先に反映する必要がある。

## 5. ドキュメント更新

- `backends/README.md`: 実装スタック一覧の表に1行追加（状態は `🚧 予定` または
  `✅ 完了`）
- ルート `README.md`:
  - 「システム構造」のツリー（`backends/` 配下の一覧）に追加
  - 「6. バックエンドの初期設定」に `6.x <name>` の節を追加
- 他Skillの登録先（更新を忘れるとSkill経由で新バックエンドを選択・起動・CI発火
  できない）:
  - `.claude/skills/backend-dispatch/SKILL.md` の対応表に1行追加
  - `.claude/skills/backend-ci-trigger/SKILL.md` のワークフロー対応表に1行追加
  - `.claude/skills/docker-ops/SKILL.md` の「起動順序」の個別列挙に
    `docker/bin/docker-<name>.sh up` を追加

以下7ファイルすべてに `<name>` が登場することを最終確認する:
`docker/bin/docker-backends.sh`（手順3）、`.github/workflows/<name>-ci.yml`
（手順4）、`backends/README.md` / `README.md`（本節）、
`.claude/skills/{backend-dispatch,backend-ci-trigger,docker-ops}/SKILL.md`
（本節）。

```bash
grep -rl "<name の実際値>" \
  docker/bin/docker-backends.sh \
  .github/workflows/<name>-ci.yml \
  backends/README.md README.md \
  .claude/skills/backend-dispatch/SKILL.md \
  .claude/skills/backend-ci-trigger/SKILL.md \
  .claude/skills/docker-ops/SKILL.md
```

## 6. 動作確認

`showcase` ネットワークが無い（＝ `docker/bin/docker-common.sh up` を一度も
実行していない）クリーンな環境では、先に共通コンテナを起動しておく必要がある
（docker-ops Skill参照）。

```bash
docker/bin/docker-common.sh up   # showcaseネットワーク・nginx-proxyが無ければ起動
docker/bin/docker-<name>.sh up
docker/bin/docker-<name>.sh exec   # コンテナに入り疎通確認
```

CIの手動発火・確認は backend-ci-trigger Skillを参照。ここまで確認できたら
git-flow Skillでdevelopへのpullリクエストを作成する。
