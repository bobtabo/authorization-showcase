---
name: git-flow
description: >-
  対応するGitHub Issueからfeatureブランチを作成し、developへのPRを作成する際に使う。
  「Issueブランチを作って」「#Nのブランチを切って」「PRを出して」等の指示で使う。
  develop→mainの同期PRはこのSkillの対象外。
allowed-tools: Bash(git:*), Bash(gh:*)
---

# git-flow

このリポジトリ（bobtabo/authorization-showcase）のブランチ運用を統一する手順書。

## 前提

- 対応するGitHub Issue（番号 N）が既に存在すること。Issue番号を伴わないブランチ名
  （例: `feature/xxx-yyy`）は作らない。Issueがまだ無い場合は、既存Issueの書式
  （`[showcase] <type>: <日本語要約>`、`## 背景` → `## タスク` の構成）に合わせて
  先にIssueを作成してから本Skillを使う。
- 派生元ブランチは既定で `develop`。呼び出し時に別ブランチが明示された場合は
  そちらを派生元として使う（例: hotfixを `main` から切る等、たまにある例外に対応）。

## 1. featureブランチ作成

```bash
set -euo pipefail
BASE=develop   # 明示指定があればそちらに置き換える
N=34           # 対応するIssue番号に置き換える（実行前に書き換える。以下このSKILL.mdでは
               # <N> を「この値に置き換える」プレースホルダーとして表記する）

git checkout "$BASE"
git pull --ff-only origin "$BASE"
gh issue view "$N" --repo bobtabo/authorization-showcase --json title -q .title
git checkout -b "feature/issue-$N"
```

`set -euo pipefail` により、Issueが存在せず `gh issue view` が失敗した場合は
`git checkout -b` を実行せずに停止する。`git pull` は `--ff-only` とし、
ローカルとリモートが分岐している場合は自動マージせず停止する。

## 2. developへのPR作成

- base: `develop` / head: `feature/issue-$N`（派生元を変えた場合もbaseは`develop`）
- タイトル: `$TYPE(#$N): $SUMMARY`
  （`TYPE`: feat/fix/docs/chore/refactor 等、conventional commit風。Issueのtypeを踏襲する）
- 本文: Summary / Changes / Closes(またはRefs) / 署名の4点を含める。Issueの一部のみ
  対応する場合（Issueの残タスクが残る場合）は `Closes #$N` ではなく `Refs #$N` とし、
  Issueを自動クローズさせない。

- コマンド（`N`/`TYPE`/`SUMMARY`/`CHANGES`/`LINK` に実際の値を設定してから実行する。
  この節のブロックは手順1とは別のシェルプロセスで実行される想定のため、`N`を
  再設定する。heredocはクォートしない `<<EOF` にして変数展開させる。
  プレースホルダーのまま送信しない）:

  ```bash
  set -euo pipefail
  N=34                             # 手順1で使った値と同じIssue番号
  TYPE=feat                       # feat/fix/docs/chore/refactor 等
  SUMMARY="日本語要約"
  CHANGES="- 変更ファイル/内容"    # 複数行なら改行を含めて組み立てる
  LINK="Closes #$N"                # 部分対応の場合は "Refs #$N" にする

  git push -u origin "feature/issue-$N"
  gh pr create --repo bobtabo/authorization-showcase \
    --base develop --head "feature/issue-$N" \
    --title "${TYPE}(#${N}): ${SUMMARY}" --body "$(cat <<EOF
  ## Summary

  Issue #$N 対応。$SUMMARY

  ## Changes
  $CHANGES

  $LINK

  🤖 Generated with [Claude Code](https://claude.com/claude-code)
  EOF
  )"
  ```

## 3. レビュー監視・対応（CI/CodeRabbit）

PRを作成したら、CIとCodeRabbitレビューが収束するまで監視する。人間レビュアーの
承認を待つ／催促するのはこのSkillの対象外（そこはユーザーに判断を委ねる）。
以下の各bashブロックは自己完結させてあるが、`PR`はハードコードせず毎回
`feature/issue-$N` から解決する（PR番号を決め打ちすると、別のPRを誤って操作する
事故につながる）。

### 3.1 CI待ち

`backends/**` を変更している場合、`feature/issue-*` へのpushではバックエンドCIが
自動実行されない（`branches-ignore`で除外されるため）。対応ワークフローが既に
defaultブランチ（`main`）に存在するなら、backend-ci-trigger Skillで
`workflow_dispatch`により手動発火してから待つ。new-backend-scaffold Skillで
**新規追加した**ワークフロー（まだ`main`に無い）は`workflow_dispatch`で発火できない
ため、この場合は無理に発火しようとせず「CI未実行（develop→main同期後に確認）」と
明示してユーザーに報告する（バックエンド変更が無ければこの節は不要。CodeRabbitの
チェックはpush毎に自動で走る）。

固定の `sleep` ループでフォアグラウンドをブロックしない。Bashツールの
`run_in_background: true` でポーリングし、完了通知を待つ。全チェックの状態を
集約し、`FAILURE`/`ERROR`（GitHubのStatusState仕様上の終端失敗状態）が1件でもあれば
即停止、API取得失敗・タイムアウトも明示的に失敗として扱う（先頭のチェックだけを
見ない。応答をシェル変数に保持してからechoでjqへ渡すと本文中のエスケープシーケンスで
壊れることがあるため、`gh`の`--jq`で直接フィルタする）:

```bash
set -euo pipefail
N=34   # このfeatureブランチのIssue番号
PR=$(gh pr view "feature/issue-$N" --repo bobtabo/authorization-showcase --json number -q .number)

for i in $(seq 1 40); do
  STATES=$(gh pr view "$PR" --repo bobtabo/authorization-showcase \
    --json statusCheckRollup --jq '.statusCheckRollup[].state') || {
    echo "gh pr view の取得に失敗しました" >&2
    exit 1
  }
  if echo "$STATES" | grep -qE '^(FAILURE|ERROR)$'; then
    echo "CIが失敗しました" >&2
    exit 1
  fi
  if [ -n "$STATES" ] && ! echo "$STATES" | grep -qv '^SUCCESS$'; then
    echo "全チェックSUCCESS"
    break
  fi
  echo "[$i] pending: $STATES"
  if [ "$i" -eq 40 ]; then
    echo "タイムアウト: CIが完了しませんでした" >&2
    exit 1
  fi
  sleep 15
done
```

### 3.2 CodeRabbitの指摘への対応

「未返信」とは、CodeRabbit（`coderabbitai[bot]`）のtop-levelコメントのうち、
`coderabbitai[bot]` 以外からの返信が付いていないものを指す（top-levelコメント数を
そのまま見ると、既に返信済みのものも数えてしまい判定を誤る）:

```bash
set -euo pipefail
N=34   # このfeatureブランチのIssue番号
PR=$(gh pr view "feature/issue-$N" --repo bobtabo/authorization-showcase --json number -q .number)

COMMENTS_URL="repos/bobtabo/authorization-showcase/pulls/$PR/comments"
TOP_IDS=$(gh api "$COMMENTS_URL" --paginate \
  --jq '.[] | select(.user.login=="coderabbitai[bot]" and .in_reply_to_id==null) | .id')
REPLIED_IDS=$(gh api "$COMMENTS_URL" --paginate \
  --jq '.[] | select(.in_reply_to_id != null and .user.login!="coderabbitai[bot]") | .in_reply_to_id')
# gh api の --jq でAPI応答を直接フィルタする（応答をシェル変数に保持してから
# echo | jq に通すと、本文中のエスケープシーケンスがechoで壊れてjqがパース
# エラーになることがあるため避ける）

comm -23 <(echo "$TOP_IDS" | sort) <(echo "$REPLIED_IDS" | sort -u)
# 出力された各IDについて本文を確認する:
#   gh api repos/bobtabo/authorization-showcase/pulls/comments/<id> --jq '.path, .line, .body'
```

未返信の指摘ごとに:

1. 指摘内容を現在のコードと照らして検証する。既に対応済み／的外れ／このリポジトリの
   既知のスコープ外事項（Issue本文に明記済み等）なら、修正はせず理由を添えて返信する。
2. 妥当な指摘は修正してコミット・プッシュする。
3. 各コメントIDに返信する（本文はプレースホルダーのまま送信せず、実際のコミットSHAと
   修正内容を変数に入れてから渡す）:

```bash
set -euo pipefail
N=34   # このfeatureブランチのIssue番号
PR=$(gh pr view "feature/issue-$N" --repo bobtabo/authorization-showcase --json number -q .number)
COMMENT_ID=1234567                       # 3.2で洗い出した未返信コメントのID
COMMIT_SHA=$(git rev-parse --short HEAD)
FIX_SUMMARY="何をどう直したかを記述する"
REPLY_BODY="対応しました（${COMMIT_SHA}）。${FIX_SUMMARY}"

gh api "repos/bobtabo/authorization-showcase/pulls/$PR/comments/$COMMENT_ID/replies" \
  -f body="$REPLY_BODY"
```

### 3.3 再レビューのトリガーと収束判定

CodeRabbitは短時間に複数コミットが積まれると "reviews paused"（自動一時停止）になり、
push しただけでは新しいレビューが走らないことがある。その場合は明示的に再トリガーする:

```bash
set -euo pipefail
N=34   # このfeatureブランチのIssue番号
PR=$(gh pr view "feature/issue-$N" --repo bobtabo/authorization-showcase --json number -q .number)
gh pr comment "$PR" --repo bobtabo/authorization-showcase --body "@coderabbitai review"
```

再トリガー後、3.1のCI待ちと3.2の未返信コメント抽出を繰り返す。

収束（完了）の判定基準は次の**両方**を満たすこと:
- 3.2の未返信コメント抽出結果が0件
- `gh pr view "$PR" --repo bobtabo/authorization-showcase --json mergeable,mergeStateStatus`
  が `mergeable: MERGEABLE` / `mergeStateStatus: CLEAN`

目安として5ラウンド前後で収束しない場合は、無限にループさせず一旦打ち切り、
未解決点を添えてユーザーに報告し、続行するかどうかの判断を仰ぐ。

収束したら最終状態（CI結果・対応した指摘の要約・PRリンク）をユーザーに報告する。
**developへのマージはこのSkillでは行わない**（ユーザーの明示的な指示を待つ）。

## 禁止事項

- `develop` / `main` への直接push（featureブランチ経由のPRを必ず通す）
- `develop` → `main` のマージはこのSkillでは行わない（「develop → main 同期」PRとして別途手動で作成する）

## 既知の制約

- 本SkillはSKILL.mdという「明示的に呼び出したときの手順書」であり、`allowed-tools` や
  現状の `.claude/hooks/guard-bash.sh` は `develop` / `main` への直接pushを機械的には
  ブロックしない（`guard-bash.sh` が対象とするのは `push --force` / `reset --hard` 等の
  破壊的操作のみ）。保護ブランチへのpushを権限層で強制したい場合は、`guard-bash.sh` の
  拡張または GitHub 側のブランチ保護ルールの設定を別Issueとして検討する。
