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
BASE=develop   # 明示指定があればそちらに置き換える
git checkout "$BASE"
git pull origin "$BASE"
gh issue view <N> --repo bobtabo/authorization-showcase --json title -q .title
git checkout -b feature/issue-<N>
```

## 2. developへのPR作成

- base: `develop` / head: `feature/issue-<N>`（派生元を変えた場合もbaseは`develop`）
- タイトル: `<type>(#<N>): <日本語要約>`
  （type: feat/fix/docs/chore/refactor 等、conventional commit風。Issueのtypeを踏襲する）
- 本文テンプレート:

  ```markdown
  ## Summary

  Issue #<N> 対応。<変更内容の要約>

  ## Changes
  - <変更ファイル/内容>

  Closes #<N>

  🤖 Generated with [Claude Code](https://claude.com/claude-code)
  ```

  Issueの一部のみ対応する場合（Issueの残タスクが残る場合）は `Closes #<N>` ではなく
  `Refs #<N>` とし、Issueを自動クローズさせない。

- コマンド:

  ```bash
  git push -u origin feature/issue-<N>
  gh pr create --repo bobtabo/authorization-showcase \
    --base develop --head feature/issue-<N> \
    --title "<type>(#<N>): <要約>" --body "$(cat <<'EOF'
  ...
  EOF
  )"
  ```

## 禁止事項

- `develop` / `main` への直接push（featureブランチ経由のPRを必ず通す）
- `develop` → `main` のマージはこのSkillでは行わない（「develop → main 同期」PRとして別途手動で作成する）
