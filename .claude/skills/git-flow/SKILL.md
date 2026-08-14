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

- コマンド（`TYPE`/`SUMMARY`/`CHANGES`/`LINK` に実際の値を設定してから実行する。
  heredocはクォートしない `<<EOF` にして変数展開させる。プレースホルダーのまま
  送信しない）:

  ```bash
  set -euo pipefail
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

## 禁止事項

- `develop` / `main` への直接push（featureブランチ経由のPRを必ず通す）
- `develop` → `main` のマージはこのSkillでは行わない（「develop → main 同期」PRとして別途手動で作成する）

## 既知の制約

- 本SkillはSKILL.mdという「明示的に呼び出したときの手順書」であり、`allowed-tools` や
  現状の `.claude/hooks/guard-bash.sh` は `develop` / `main` への直接pushを機械的には
  ブロックしない（`guard-bash.sh` が対象とするのは `push --force` / `reset --hard` 等の
  破壊的操作のみ）。保護ブランチへのpushを権限層で強制したい場合は、`guard-bash.sh` の
  拡張または GitHub 側のブランチ保護ルールの設定を別Issueとして検討する。
