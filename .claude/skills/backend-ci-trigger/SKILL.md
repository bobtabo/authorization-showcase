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
で手動発火する。develop/main へのpush時は該当pathsに応じて自動実行される。

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
git diff --name-only develop... | sed -n 's#^backends/\([^/]*\)/.*#\1#p' | sort -u
# 出力された各ディレクトリ名を上表と突き合わせて対象ワークフローを決定する
```

## 手動発火と結果確認

```bash
gh workflow run <ワークフローファイル> --repo bobtabo/authorization-showcase --ref feature/issue-<N>

# 実行中/直近のrunを確認
gh run list --repo bobtabo/authorization-showcase \
  --workflow=<ワークフローファイル> --branch=feature/issue-<N> --limit 1

# 完了まで待ってログを見る
gh run watch --repo bobtabo/authorization-showcase <run-id>
gh run view --repo bobtabo/authorization-showcase <run-id> --log
```

## 注意

- 全バックエンドCIは同一のngrokトンネル/LocalStackを共有しており、
  `concurrency: group: shared-authserver-tunnel, cancel-in-progress: false`
  で直列実行される。複数ワークフローを続けて発火すると、後発のものは前段の
  完了待ちで少し時間がかかる。
- develop/main へのマージ前にfeatureブランチ側でCIグリーンを確認したい場合は、
  このSkillで対象ワークフローを手動発火してから確認する（git-flow SkillでPRを
  作成しても、featureブランチ自体のpushではCIは動かない点に注意）。
