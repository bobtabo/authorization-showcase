import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: './scenarios',
  use: {
    baseURL: 'http://localhost:5173',
    video: 'on',
    viewport: { width: 1280, height: 800 },
  },
  outputDir: './output',
  projects: [
    { name: 'chromium', use: { channel: 'chromium' } },
  ],
})
