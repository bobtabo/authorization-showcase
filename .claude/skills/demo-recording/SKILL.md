---
name: demo-recording
description: >-
  e2e/ ディレクトリで Playwright を使った動作デモGIFを録画する際に使う。
  「デモGIFを録って」「動作を録画して」等の指示で使う。
allowed-tools: Bash(npm:*), Bash(cd:*)
---

# demo-recording

`e2e/` はE2Eテストではなく、動作デモGIF録画専用のディレクトリ（`frontend/` とは分離、
CIには組み込まれない・手動実行のみ）。

## 前提

- `ffmpeg` がインストール済み（`brew install ffmpeg`）
- Playwright ブラウザをインストール済み（`npx playwright install chromium`、
  `e2e/` 配下で実行）
- `frontend/` の開発サーバーが `http://localhost:5173` で起動していること
  （フロントの起動は別途 `npm run dev` を `frontend/` で実行しておく。このSkillの
  対象外）

## 手順

```bash
cd e2e
npm install
npm run record     # playwright test --project=chromium を実行。output/*.webm を生成
npm run to-gif      # scripts/to-gif.sh でwebm→GIF変換。docs/gif/*.gif を生成
```

## 新規デモシナリオの追加

新しいシナリオを録画したい場合は `e2e/scenarios/` に `*.spec.ts` を追加する
（既存の `e2e/scenarios/jwt-flow.spec.ts` を参考にする）。シナリオ追加後に
上記の `npm run record` → `npm run to-gif` を実行する。

## 生成物の扱い

- `e2e/output/`（録画中間ファイルの `.webm`）は `.gitignore` 対象。コミットしない。
- `docs/gif/*.gif`（変換後のGIF）はREADME等に埋め込んで使うため、リポジトリに
  含める（コミット対象）。
