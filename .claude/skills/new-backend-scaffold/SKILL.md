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
- 以降のコマンド例は `NAME` 変数に実際のバックエンド名を入れて使う
  （`<name>` をそのままシェルに渡すと `<`/`>` がリダイレクトと解釈され
  構文エラーになるため、必ず変数化する）。`NAME` はディレクトリ名・Docker
  Composeのプロジェクト名・CIワークフローファイル名にそのまま使われるため、
  既存7バックエンド同様のkebab-case（`^[a-z0-9]+(-[a-z0-9]+)*$`）にする。
  各ブロックの冒頭で検証している。
- **各手順のbashブロックは別々のシェルプロセスとして実行される想定**で、
  `NAME` はブロック間で共有されない。そのため各ブロックの先頭で毎回
  `NAME=<実際の値>` を（同じ値で）設定し直す。同一シェルセッション内で
  複数手順を続けて実行する場合は、2回目以降の再設定は害にならない。
- 手順2〜4のコピー操作は、コピー先が既に存在する場合は実行前に停止する
  （再実行で既存の変更を`cp`が上書きするのを防ぐ）。既存の生成物を作り直したい
  場合は、手動で削除するか意図的に上書きすることを確認した上で行う。

## 1. バックエンドソース（`backends/$NAME/`）

```bash
NAME=node-express   # 実際に追加するバックエンド名に置き換える
[[ "$NAME" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]] || { echo "invalid backend name: $NAME" >&2; exit 1; }

mkdir -p "backends/$NAME"
```

- アプリのソース一式を配置する。
- `backends/$NAME/README.md` を作成する（`backends/go-gin/README.md` の構成
  ―概要／起動方法／エンドポイント一覧―に合わせる）。

## 2. Docker実行環境（`docker/local/app-$NAME/`）

`docker/local/app-go/` の中身を丸ごとコピーして書き換える。ディレクトリ自体を
`cp -r src dst` すると、`dst` が既に存在する場合は `dst/app-go/` のようにネストして
しまうため、コピー先を先に作ってから**中身**をコピーする:

```bash
NAME=node-express   # 手順1と同じ値
[[ "$NAME" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]] || { echo "invalid backend name: $NAME" >&2; exit 1; }

if [ -e "docker/local/app-$NAME" ]; then
  echo "docker/local/app-$NAME は既に存在します。上書きを避けるため中断します" >&2
  exit 1
fi
mkdir -p "docker/local/app-$NAME"
cp -r docker/local/app-go/. "docker/local/app-$NAME/"
```

書き換える点:

- `docker-compose.yml`
  - `name: showcase-app-$NAME`
  - サービス名・イメージのbuild文脈（`go/` ディレクトリ相当）を言語用に差し替え
  - アプリ側の `volumes` を `./../../../backends/$NAME:/var/www/${APP_NAME}` に
  - `networks.showcase.external: true` はそのまま維持（`common` が作るネットワーク
    に参加する）
  - `nginx` サービス定義（`VIRTUAL_HOST` / `VIRTUAL_PORT` 等の環境変数連携）は
    ほぼそのまま流用可
- `.env.example`
  - `APP_NAME=app-$NAME`
  - `SERVER_NAME=apis.showcase-$NAME.dev`
  - `NGINX_CONTAINER=showcase-${NAME}_nginx`
  - 言語コンテナの `<LANG>_CONTAINER=showcase-${NAME}_<service>` を追加
  - デバッグポート等、言語固有の変数を追加
- `nginx/`（`Dockerfile` / `nginx.conf` / `templates/default.conf.template`）は
  ほぼそのまま流用できる（`APP_PATH` 等の環境変数経由でルーティングするため）

## 3. Dockerラッパースクリプト（`docker/bin/docker-$NAME.sh`）

`docker/bin/docker-go.sh` をコピーして書き換える。

```bash
NAME=node-express   # 手順1と同じ値
[[ "$NAME" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]] || { echo "invalid backend name: $NAME" >&2; exit 1; }

if [ -e "docker/bin/docker-$NAME.sh" ]; then
  echo "docker/bin/docker-$NAME.sh は既に存在します。上書きを避けるため中断します" >&2
  exit 1
fi
cp docker/bin/docker-go.sh "docker/bin/docker-$NAME.sh"
chmod 755 "docker/bin/docker-$NAME.sh"
```

書き換える点: `cd` 先を `app-$NAME`、`-p showcase-$NAME`、`exec` で入る
サービス名・シェル（bash推奨、必要ならsh）。

さらに `docker/bin/docker-backends.sh` の `run()` に1行追加する。
この行は `docker-backends.sh` 自身のスクリプト内に書く固定のコードであり、
その場では `NAME` 変数は存在しないため、`$NAME` ではなく**実際のバックエンド名を
直接書く**（例: `node-express`）:

```bash
    bash "${SCRIPT_DIR}/docker-node-express.sh" "${ARG}"
```

## 4. CIワークフロー（`.github/workflows/$NAME-ci.yml`）

`.github/workflows/go-gin-ci.yml` をコピーして書き換える。

```bash
NAME=node-express   # 手順1と同じ値
[[ "$NAME" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]] || { echo "invalid backend name: $NAME" >&2; exit 1; }

if [ -e ".github/workflows/$NAME-ci.yml" ]; then
  echo ".github/workflows/$NAME-ci.yml は既に存在します。上書きを避けるため中断します" >&2
  exit 1
fi
cp .github/workflows/go-gin-ci.yml ".github/workflows/$NAME-ci.yml"
```

書き換える点:
- ファイル先頭のコメント（`# Go（Gin）CI ワークフロー` / `# developブランチの
  backends/go-gin/** 変更時に CI を実行`）と `name: Go（Gin）CI` を、追加する
  言語・バックエンド名に合わせて書き換える（コピー直後はGo向けの文言が残っている）
- `paths: - 'backends/$NAME/**'`
- `branches-ignore: ['feature/issue-*']` と `concurrency.group:
  shared-authserver-tunnel` は変更しない（全バックエンド共有のngrok/LocalStack
  輻輳防止のため）
- 言語ツールチェインのセットアップステップ（`actions/setup-go` 相当）
- `Resolve AUTH_SERVER_URL` ステップは他バックエンドと同一ロジックのため、
  そのまま流用する（LocalStackのAPI Gateway IDをngrok経由で動的取得する仕組み）
- テスト実行コマンド・`working-directory: backends/$NAME` のみ差し替え

新規追加したワークフローファイルは `main` にマージされるまで `workflow_dispatch`
での手動発火ができない（GitHubの仕様。詳細は backend-ci-trigger Skillの当該
注記を参照）。featureブランチ段階でのCI確認は、develop→main同期後まで待つか、
ワークフローファイルのみ先に反映する必要がある。

## 5. ドキュメント更新

- `backends/README.md`: 実装スタック一覧の表に1行追加（状態は `🚧 予定` または
  `✅ 完了`）
- ルート `README.md`:
  - 「システム構造」のツリー（`backends/` 配下の一覧）に追加
  - 「6. バックエンドの初期設定」に `6.x $NAME` の節を追加
- 他Skillの登録先（更新を忘れるとSkill経由で新バックエンドを選択・起動・CI発火
  できない）:
  - `.claude/skills/backend-dispatch/SKILL.md` の対応表に1行追加
  - `.claude/skills/backend-ci-trigger/SKILL.md` のワークフロー対応表に1行追加
  - `.claude/skills/docker-ops/SKILL.md` の「起動順序」の個別列挙に
    `docker/bin/docker-$NAME.sh up` を追加

以下7ファイルすべてに `$NAME` が登場することを最終確認する:
`docker/bin/docker-backends.sh`（手順3）、`.github/workflows/$NAME-ci.yml`
（手順4）、`backends/README.md` / `README.md`（本節）、
`.claude/skills/{backend-dispatch,backend-ci-trigger,docker-ops}/SKILL.md`
（本節）。1ファイルでも欠けていたら止まるように、ファイルごとに個別検査する
（`grep -rl` は一致したファイルのみ表示するため、欠落の見逃しに気づけない）。

```bash
set -euo pipefail
NAME=node-express   # 手順1と同じ値
[[ "$NAME" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]] || { echo "invalid backend name: $NAME" >&2; exit 1; }

files=(
  "docker/bin/docker-backends.sh"
  ".github/workflows/$NAME-ci.yml"
  "backends/README.md"
  "README.md"
  ".claude/skills/backend-dispatch/SKILL.md"
  ".claude/skills/backend-ci-trigger/SKILL.md"
  ".claude/skills/docker-ops/SKILL.md"
)

for f in "${files[@]}"; do
  if ! grep -q -- "$NAME" "$f"; then
    echo "登録漏れ: $f に '$NAME' が見つかりません" >&2
    exit 1
  fi
done
echo "7ファイルすべてに登録を確認しました"
```

## 6. 動作確認

`showcase` ネットワークが無い（＝ `docker/bin/docker-common.sh up` を一度も
実行していない）クリーンな環境では、先に共通コンテナを起動しておく必要がある
（docker-ops Skill参照）。

```bash
NAME=node-express   # 手順1と同じ値
[[ "$NAME" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]] || { echo "invalid backend name: $NAME" >&2; exit 1; }

docker/bin/docker-common.sh up   # showcaseネットワーク・nginx-proxyが無ければ起動
"docker/bin/docker-$NAME.sh" up
"docker/bin/docker-$NAME.sh" exec   # コンテナに入り疎通確認
```

CIの手動発火・確認は backend-ci-trigger Skillを参照。ここまで確認できたら
git-flow Skillでdevelopへのpullリクエストを作成する。
