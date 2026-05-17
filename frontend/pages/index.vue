<script setup lang="ts">
import { SignJWT, jwtVerify } from 'jose'
import { KeyRound, ShieldCheck } from '@lucide/vue'

const CLIENT_OPTIONS = ['クライアントA', 'クライアントB', 'クライアントC', 'クライアントD']
const BACKEND_OPTIONS = [
  'Go（Gin）',
  'Java（Spring Boot）',
  'PHP（CakePHP）',
  'PHP（CodeIgniter）',
  'PHP（FuelPHP）',
  'Python（Django）',
  'Ruby（Rails）',
]

const SECRET = new TextEncoder().encode('your-secret-key-change-in-production')

interface VerificationResult {
  success: boolean
  payload?: Record<string, unknown>
  error?: string
}

const selectedClient = ref<string>(CLIENT_OPTIONS[0])
const selectedBackend = ref<string>(BACKEND_OPTIONS[0])
const memberId = ref<string>('')
const jwt = ref<string>('')
const verificationResult = ref<VerificationResult | null>(null)
const showVerifyButton = ref<boolean>(false)

const generateRandomMemberId = (): void => {
  memberId.value = 'M' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0')
}

const generateJWT = async (): Promise<void> => {
  try {
    const token = await new SignJWT({
      client: selectedClient.value,
      memberId: memberId.value,
    })
      .setProtectedHeader({ alg: 'HS256' })
      .setIssuedAt()
      .setExpirationTime('24h')
      .sign(SECRET)

    jwt.value = token
    showVerifyButton.value = true
    verificationResult.value = null
  } catch (error) {
    console.error('JWT生成エラー:', error)
  }
}

const verifyJWT = async (): Promise<void> => {
  try {
    const { payload } = await jwtVerify(jwt.value, SECRET)
    verificationResult.value = { success: true, payload: payload as Record<string, unknown> }
  } catch (error: unknown) {
    verificationResult.value = {
      success: false,
      error: error instanceof Error ? error.message : '不明なエラー',
    }
  }
}

const resetForm = (): void => {
  generateRandomMemberId()
  jwt.value = ''
  verificationResult.value = null
  showVerifyButton.value = false
}

watch(selectedBackend, () => {
  selectedClient.value = CLIENT_OPTIONS[0]
  resetForm()
})

watch(selectedClient, () => {
  resetForm()
})

onMounted(() => {
  generateRandomMemberId()
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 flex flex-col">
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center gap-3">
          <img src="/icon.png" alt="Logo" class="w-10 h-10 rounded-lg" />
          <h2 class="text-gray-800">Authorization Gateway ShowCase</h2>
        </div>
      </div>
    </header>

    <div class="flex-1 flex items-center justify-center p-4 sm:p-8">
      <div class="w-full max-w-2xl">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 sm:p-8">
          <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
              <div class="w-1 h-8 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full" />
              <h1 class="text-gray-800">JWT発行／検証</h1>
            </div>
            <div class="flex items-center gap-3">
              <label class="text-sm text-gray-600">Backend:</label>
              <select
                v-model="selectedBackend"
                class="px-3 py-1.5 bg-gray-50 border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all cursor-pointer"
              >
                <option v-for="backend in BACKEND_OPTIONS" :key="backend" :value="backend">
                  {{ backend }}
                </option>
              </select>
            </div>
          </div>

          <div class="space-y-5">
            <div>
              <label class="block mb-2 text-sm text-gray-600">Client Name</label>
              <select
                v-model="selectedClient"
                class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all cursor-pointer"
              >
                <option v-for="client in CLIENT_OPTIONS" :key="client" :value="client">
                  {{ client }}
                </option>
              </select>
            </div>

            <div>
              <label class="block mb-2 text-sm text-gray-600">Member ID</label>
              <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl text-gray-800 font-mono">
                {{ memberId }}
              </div>
            </div>

            <button
              class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl hover:from-blue-600 hover:to-purple-600 transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2"
              @click="generateJWT"
            >
              <KeyRound :size="20" />
              JWTを発行する
            </button>

            <div v-if="jwt" class="space-y-4 pt-5">
              <div>
                <label class="block mb-2 text-sm text-gray-600">JWT Token</label>
                <textarea
                  :value="jwt"
                  readonly
                  rows="6"
                  class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm font-mono text-gray-700 resize-none focus:outline-none"
                />
              </div>

              <button
                v-if="showVerifyButton"
                class="w-full px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl hover:from-emerald-600 hover:to-teal-600 transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2"
                @click="verifyJWT"
              >
                <ShieldCheck :size="20" />
                JWTを検証する
              </button>
            </div>

            <div v-if="verificationResult" class="mt-5">
              <div
                v-if="verificationResult.success"
                class="bg-gradient-to-r from-emerald-50 to-teal-50 p-5 rounded-xl border border-emerald-200"
              >
                <div class="flex items-center gap-2 mb-3">
                  <div class="w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center text-white text-sm">
                    ✓
                  </div>
                  <h3 class="text-emerald-800">Verification Success</h3>
                </div>
                <pre class="text-sm bg-white p-4 rounded-lg overflow-x-auto text-gray-700 border border-emerald-100">{{ JSON.stringify(verificationResult.payload, null, 2) }}</pre>
              </div>

              <div
                v-else
                class="bg-gradient-to-r from-red-50 to-pink-50 p-5 rounded-xl border border-red-200"
              >
                <div class="flex items-center gap-2 mb-2">
                  <div class="w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-white text-sm">
                    ✗
                  </div>
                  <h3 class="text-red-800">Verification Failed</h3>
                </div>
                <p class="text-sm text-red-600 ml-8">
                  {{ verificationResult.error }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <footer class="bg-white/80 backdrop-blur-sm border-t border-gray-200 py-4">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-600">
          © 2026 Authorization Gateway. All rights reserved.
        </p>
      </div>
    </footer>
  </div>
</template>
