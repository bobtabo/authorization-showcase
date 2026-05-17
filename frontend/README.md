<p align="center">
<a href="https://vuejs.org/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vuejs/vuejs-original-wordmark.svg" height="72" alt="Vue.js"></a>
&nbsp;&nbsp;
<a href="https://nuxt.com/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/nuxtjs/nuxtjs-original.svg" height="72" alt="Nuxt.js"></a>
&nbsp;&nbsp;
<a href="https://www.typescriptlang.org/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/typescript/typescript-original.svg" height="72" alt="TypeScript"></a>
&nbsp;&nbsp;
<a href="https://tailwindcss.com/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/tailwindcss/tailwindcss-original.svg" height="72" alt="Tailwind CSS"></a>
&nbsp;&nbsp;
<a href="https://vite.dev/" target="_blank"><img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vitejs/vitejs-original.svg" height="72" alt="Vite"></a>
</p>

<p align="center">
<a href="https://vuejs.org/"><img src="https://img.shields.io/badge/Vue.js-3.5-42b883?logo=vue.js&logoColor=white" alt="Vue.js 3.5"></a>
<a href="https://nuxt.com/"><img src="https://img.shields.io/badge/Nuxt.js-3.21-00C58E?logo=nuxt.js&logoColor=white" alt="Nuxt.js 3.21"></a>
<a href="https://www.typescriptlang.org/"><img src="https://img.shields.io/badge/TypeScript-5.9-3178C6?logo=typescript&logoColor=white" alt="TypeScript 5.9"></a>
<a href="https://tailwindcss.com/"><img src="https://img.shields.io/badge/Tailwind_CSS-4.3-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4.3"></a>
<a href="https://vite.dev/"><img src="https://img.shields.io/badge/Vite-7.3-C50FDF?logo=vite&logoColor=white" alt="Vite 7.3"></a>
</p>

---

## :book: 概要

JWT（JSON Web Token）の **発行と検証** をブラウザ上でインタラクティブに体験できるショーケースです。

クライアント・バックエンド・メンバー ID を選択して JWT を発行し、その場で署名検証を行います。  
アルゴリズムは **HS256**、署名処理は [jose](https://github.com/panva/jose) ライブラリをフロントエンドのみで完結させています。

---

## :zap: 機能

| 操作 | 内容 |
| --- | --- |
| Client Name 選択 | JWT の `client` クレームに埋め込むクライアントを選択 |
| Backend 選択 | JWT の発行先バックエンドを選択（表示用） |
| Member ID 自動生成 | ページ読み込み・選択変更時にランダムな `M000000` 形式の ID を発行 |
| JWT 発行 | HS256 署名・有効期限 24h の JWT を生成 |
| JWT 検証 | 発行した JWT の署名と有効期限をその場で検証 |

### リセット挙動

- **Backend 変更時** → Client を初期値に戻し、Member ID 再生成・JWT・検証結果をクリア
- **Client 変更時** → Backend はそのまま、Member ID 再生成・JWT・検証結果をクリア

---

## :file_folder: ディレクトリ構成

```
frontend/
├── app.vue               # Nuxt ルートコンポーネント
├── nuxt.config.ts        # Nuxt 設定（Vite / Tailwind v4 / TypeScript strict）
├── tsconfig.json         # TypeScript 設定
├── package.json
├── assets/
│   └── css/
│       └── main.css      # Tailwind v4 エントリポイント
├── pages/
│   └── index.vue         # JWT 発行／検証ページ
└── public/
    └── icon.png          # ロゴアイコン
```

---

## :package: 主要パッケージ

| パッケージ | 用途 |
| --- | --- |
| `nuxt` | フレームワーク（SSR / SPA / Vite 内蔵） |
| `vue` | UI ライブラリ |
| `jose` | JWT 発行・検証（ブラウザ対応） |
| `@lucide/vue` | アイコン |
| `tailwindcss` | ユーティリティファースト CSS（v4） |
| `@tailwindcss/vite` | Tailwind v4 Vite プラグイン |

---

## :rocket: セットアップ

### 1. 依存パッケージのインストール

```bash
npm install
```

### 2. 開発サーバー起動

```bash
npm run dev
```

http://localhost:5173 で起動します。

### 3. プロダクションビルド

```bash
npm run build
npm run preview
```
