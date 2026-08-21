---
name: backend-ci-trigger
description: >-
  featureブランチでバックエンドの変更をpushしたあと、対応するCIが自動実行
  されないため手動でCIを発火・確認する際に使う。「CIを回して」「CIの結果を
  確認して」等の指示で使う。
allowed-tools: Bash(gh:*)
---

# backend-ci-trigger

各バックエンドCI（`.github/workflows/*-ci.yml`）は次の2点により、featureブランチへの
pushでは**自動実行されない**。

1. `paths` トリガーが対応するバックエンドディレクトリのみに限定されている
2. `branches-ignore: ['feature/issue-*']` により、`feature/issue-*` へのpush自体が
   トリガー対象外

そのため `feature/issue-N` ブランチ上でCI結果を確認したい場合は、`workflow_dispatch`
で手動発火する。`feature/issue-*` **以外**のブランチ（develop/main に限らない）への
pushでは、該当pathsに応じて自動実行される。

`workflow_dispatch` は、対象のワークフローファイルがdefaultブランチ（`main`）に
既に存在している場合のみ使える。new-backend-scaffold Skillで新規追加したワークフロー
ファイルは、`main` へマージされるまで `gh workflow run` で手動発火できない
（GitHubがdispatch可否をdefaultブランチ上の定義で判定するため）。この場合は、
develop→main同期後に確認するか、ワークフローファイルのみ先にdevelopおよびmainへ
反映しておく。

## ワークフロー対応表

| ワークフローファイル | 対象パス |
|---|---|
| `go-gin-ci.yml` | `backends/go-gin/**` |
| `java-springboot-ci.yml` | `backends/java-springboot/**` |
| `php-cakephp-ci.yml` | `backends/php-cakephp/**` |
| `php-codeigniter-ci.yml` | `backends/php-codeigniter/**` |
| `php-fuelphp-ci.yml` | `backends/php-fuelphp/**` |
| `python-django-ci.yml` | `backends/python-django/**` |
| `ruby-rails-ci.yml` | `backends/ruby-rails/**` |

`gh workflow run` にはワークフロー表示名（日本語・括弧付き）ではなく、上記の
ファイル名を使う（曖昧さがなく確実）。

## 発火対象の判断

変更したファイルパスから、対応するバックエンドのワークフローを特定する。
複数バックエンドにまたがる変更（例: 共通ドキュメントやCI設定自体の変更）は、
影響するワークフローをそれぞれ個別に発火する（一括発火するコマンドは無い）。

```bash
BASE=develop   # git-flowでこのfeatureブランチの派生元とした基準ブランチに合わせる
git diff --name-only "$BASE"...
```

- `backends/<name>/**` に一致する変更 → 出力パスの `<name>` 部分を上表と
  突き合わせ、対応するワークフローのみ発火する。
- `.github/workflows/**`、`docker/**`、リポジトリ直下の設定ファイル等、
  特定のバックエンドに閉じない変更（＝上記以外の変更）が1件でも含まれる場合は、
  影響範囲を個別に切り分けず、上表の7ワークフロー全てを発火するフォールバックを
  取る。

## 手動発火と結果確認

`gh workflow run` は対象runのURLを返せる場合は返す（`gh`のバージョンやイベント
種別により返らないこともある）。まずそのURLからrun IDを取得することを優先し、
取れなかった場合のみ `gh run list` へフォールバックする。フォールバック時は
「発火前の時刻より後に作られた `workflow_dispatch` runが複数ある」と一意に
特定できないため、候補が**ちょうど1件**のときだけ採用し、0件または複数件なら
中断する（同時刻の並行dispatchと取り違えないため）:

```bash
set -euo pipefail
WORKFLOW=go-gin-ci.yml   # 対象ワークフローファイル名（上表を参照して置き換える）
N=34                      # このfeatureブランチに対応するIssue番号に置き換える

DISPATCHED_AT=$(date -u +%Y-%m-%dT%H:%M:%SZ)
DISPATCH_OUTPUT=$(gh workflow run "$WORKFLOW" --repo bobtabo/authorization-showcase \
  --ref "feature/issue-$N" 2>&1)
echo "$DISPATCH_OUTPUT"

RUN_ID=$(echo "$DISPATCH_OUTPUT" | grep -oE '/actions/runs/[0-9]+' | grep -oE '[0-9]+' | head -1 || true)

if [ -z "$RUN_ID" ]; then
  # URLが返らない場合のフォールバック。候補が複数/0件の場合は一意に特定できないため中断する
  for i in $(seq 1 20); do
    CANDIDATES=$(gh run list --repo bobtabo/authorization-showcase \
      --workflow="$WORKFLOW" --branch="feature/issue-$N" --event=workflow_dispatch \
      --json databaseId,createdAt \
      --jq "[.[] | select(.createdAt > \"$DISPATCHED_AT\")]")
    COUNT=$(echo "$CANDIDATES" | jq 'length')
    if [ "$COUNT" -eq 1 ]; then
      RUN_ID=$(echo "$CANDIDATES" | jq -r '.[0].databaseId')
      break
    elif [ "$COUNT" -gt 1 ]; then
      echo "runの候補が複数あり一意に特定できません。手動でrun IDを確認してください" >&2
      exit 1
    fi
    sleep 3
  done
fi
if [ -z "$RUN_ID" ]; then
  echo "新しいrunを検出できませんでした" >&2
  exit 1
fi

# 完了まで待ってログを見る。--exit-status はCI失敗時に非0で終了するため、
# set -e 下でもgh run view --logが実行されるようステータスを一旦保存する
set +e
gh run watch --repo bobtabo/authorization-showcase "$RUN_ID" --exit-status
WATCH_STATUS=$?
set -e
gh run view --repo bobtabo/authorization-showcase "$RUN_ID" --log
exit "$WATCH_STATUS"
```

## 注意

- 全バックエンドCIは同一のngrokトンネル/LocalStackを共有しており、
  `concurrency: group: shared-authserver-tunnel, cancel-in-progress: false`
  で直列実行される。複数ワークフローを続けて発火すると、後発のものは前段の
  完了待ちで少し時間がかかる。
- develop/main へのマージ前にfeatureブランチ側でCIグリーンを確認したい場合は、
  このSkillで対象ワークフローを手動発火してから確認する（git-flow SkillでPRを
  作成しても、featureブランチ自体のpushではCIは動かない点に注意）。
