import { test, expect } from '@playwright/test'

/**
 * デモ用シナリオ: JWT発行／検証の一連の流れを録画し GIF 化する。
 *
 * バックエンド API はすべてモックする（実バックエンドコンテナの起動は不要）。
 * クライアント情報は https://github.com/bobtabo/authorization/blob/develop/frontend/e2e/demo/authorization-flow.spec.ts
 * のデモと表記を揃えている。
 *
 * 実行:
 *   npm run record
 */

const BACKEND_URL = 'http://localhost:18080' // Go（Gin） — デフォルトで選択されるバックエンド

const CLIENT = {
  id: 1,
  name: '株式会社デモテスト',
  identifier: 'demo-client-001',
  status: 2,
}

const BEARER_TOKEN = 'demo-bearer-token-001'

/** 人間らしい操作に見せるための待機 */
async function humanDelay(ms = 800): Promise<void> {
  await new Promise((r) => setTimeout(r, ms))
}

function base64url(input: Record<string, unknown>): string {
  return Buffer.from(JSON.stringify(input)).toString('base64url')
}

function buildMockJwt(payload: Record<string, unknown>): string {
  const header = base64url({ alg: 'HS256', typ: 'JWT' })
  const body = base64url(payload)
  return `${header}.${body}.mock-signature-for-demo-purposes-only`
}

function decodeMockJwtPayload(token: string): Record<string, unknown> {
  const [, body] = token.split('.')
  return JSON.parse(Buffer.from(body ?? '', 'base64url').toString('utf-8'))
}

test('JWT発行／検証フローのデモ録画', async ({ page }) => {
  // クライアント一覧をモック
  await page.route(`${BACKEND_URL}/clients*`, (route) =>
    route.fulfill({ json: [CLIENT] }),
  )

  // JWT 発行をモック（リクエストの member をそのまま payload に埋め込む）
  await page.route(`${BACKEND_URL}/gate/issue*`, (route) => {
    const url = new URL(route.request().url())
    const member = url.searchParams.get('member') ?? ''
    const now = Math.floor(Date.now() / 1000)
    const token = buildMockJwt({
      sub: member,
      client_id: CLIENT.identifier,
      iat: now,
      exp: now + 3600,
    })
    return route.fulfill({ json: { token } })
  })

  // JWT 検証をモック（発行したトークンをデコードしてそのまま返す）
  await page.route(`${BACKEND_URL}/gate/client/${CLIENT.identifier}/verify*`, (route) => {
    const url = new URL(route.request().url())
    const token = url.searchParams.get('token') ?? ''
    return route.fulfill({ json: decodeMockJwtPayload(token) })
  })

  await page.goto('/')
  await expect(page.getByText('Authorization Gateway ShowCase')).toBeVisible()
  await humanDelay(1000)

  // クライアント一覧がロードされ、デモ用クライアントが自動選択される
  const clientSelect = page.locator('select').nth(1)
  await expect(clientSelect).not.toBeDisabled()
  await expect(clientSelect).toHaveValue(CLIENT.identifier)
  await humanDelay(800)

  // Bearer Token を入力
  await page.getByPlaceholder('アクセストークンを入力').fill(BEARER_TOKEN)
  await humanDelay(800)

  // JWT を発行
  await page.getByRole('button', { name: 'JWTを発行する' }).click()
  await expect(page.locator('textarea')).not.toHaveValue('')
  await page.getByRole('button', { name: 'JWTを検証する' }).scrollIntoViewIfNeeded()
  await humanDelay(1500)

  // JWT を検証
  await page.getByRole('button', { name: 'JWTを検証する' }).click()
  const resultPanel = page.getByText('Verification Success')
  await expect(resultPanel).toBeVisible()
  await resultPanel.scrollIntoViewIfNeeded()
  await humanDelay(2000)
})
