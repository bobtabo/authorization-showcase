# :clapper: E2E（GIF 録画）

Playwright を E2E テストとしてではなく、動作デモの GIF 録画ツールとして利用するためのディレクトリです。
`frontend/` とは分離されており、CI には組み込まれません（手動実行のみ）。

## :file_folder: ディレクトリ構成

```
e2e/
├── package.json
├── playwright.config.ts
├── scenarios/          # デモシナリオ（各 Issue で追加）
└── scripts/
    └── to-gif.sh        # webm → GIF 変換スクリプト
```

## :white_check_mark: 前提条件

- ffmpeg がインストール済みであること（`brew install ffmpeg`）
- Playwright ブラウザのインストール: `npx playwright install chromium`
- `frontend/` の開発サーバーが `http://localhost:5173` で起動していること

## :rocket: 使い方

```bash
cd e2e
npm install
npm run record     # 録画（output/*.webm が生成される）
npm run to-gif      # GIF 変換（docs/gif/*.gif が生成される）
```

`output/`（録画中間ファイル）は `.gitignore` 対象ですが、`docs/gif/*.gif` は README に埋め込んで使うためリポジトリに含めます。
