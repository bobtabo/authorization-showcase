import { test, expect, type Locator, type Page } from '@playwright/test'

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

/** 録画に実カーソルは映らないため、擬似カーソルを描画してクリック操作を可視化する */
async function installCursor(page: Page): Promise<void> {
  await page.addInitScript(() => {
    // addInitScript の実行時点では document.documentElement がまだ存在しないため、
    // DOMContentLoaded まで要素の追加を遅延させる
    function setup(): void {
      const cursor = document.createElement('div')
      Object.assign(cursor.style, {
        position: 'fixed',
        top: '0',
        left: '0',
        width: '18px',
        height: '18px',
        borderRadius: '50%',
        background: 'rgba(59, 130, 246, 0.9)',
        border: '2px solid white',
        boxShadow: '0 1px 4px rgba(0,0,0,0.4)',
        pointerEvents: 'none',
        zIndex: '2147483647',
        transform: 'translate(-50%, -50%)',
        transition: 'left 0.05s linear, top 0.05s linear, transform 0.1s ease-out',
      })
      document.documentElement.appendChild(cursor)

      const style = document.createElement('style')
      style.textContent = `
        @keyframes demo-cursor-ripple {
          from { opacity: 0.8; transform: translate(-50%, -50%) scale(0.4); }
          to { opacity: 0; transform: translate(-50%, -50%) scale(2.4); }
        }
      `
      document.documentElement.appendChild(style)

      window.addEventListener(
        'mousemove',
        (e) => {
          cursor.style.left = `${e.clientX}px`
          cursor.style.top = `${e.clientY}px`
        },
        { capture: true },
      )

      window.addEventListener(
        'mousedown',
        (e) => {
          cursor.style.transform = 'translate(-50%, -50%) scale(0.7)'
          const ripple = document.createElement('div')
          Object.assign(ripple.style, {
            position: 'fixed',
            left: `${e.clientX}px`,
            top: `${e.clientY}px`,
            width: '18px',
            height: '18px',
            borderRadius: '50%',
            border: '2px solid rgba(59, 130, 246, 0.8)',
            pointerEvents: 'none',
            zIndex: '2147483646',
            animation: 'demo-cursor-ripple 0.6s ease-out forwards',
          })
          document.documentElement.appendChild(ripple)
          ripple.addEventListener('animationend', () => ripple.remove())
        },
        { capture: true },
      )

      window.addEventListener(
        'mouseup',
        () => {
          cursor.style.transform = 'translate(-50%, -50%) scale(1)'
        },
        { capture: true },
      )
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', setup)
    } else {
      setup()
    }
  })
}

/** 擬似カーソルを対象要素までなめらかに移動させてからクリックする */
async function moveAndClick(page: Page, locator: Locator): Promise<void> {
  await locator.scrollIntoViewIfNeeded()
  const box = await locator.boundingBox()
  if (!box) throw new Error('要素の座標を取得できませんでした')
  await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 25 })
  await humanDelay(300)
  await page.mouse.down()
  await humanDelay(100)
  await page.mouse.up()
}

test('JWT発行／検証フローのデモ録画', async ({ page }) => {
  await installCursor(page)

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
  const bearerInput = page.getByPlaceholder('アクセストークンを入力')
  await moveAndClick(page, bearerInput)
  await bearerInput.fill(BEARER_TOKEN)
  await humanDelay(800)

  // JWT を発行
  await moveAndClick(page, page.getByRole('button', { name: 'JWTを発行する' }))
  await expect(page.locator('textarea')).not.toHaveValue('')
  await humanDelay(1500)

  // JWT を検証
  await moveAndClick(page, page.getByRole('button', { name: 'JWTを検証する' }))
  const resultPanel = page.getByText('Verification Success')
  await expect(resultPanel).toBeVisible()
  await resultPanel.scrollIntoViewIfNeeded()
  await humanDelay(2000)
})
