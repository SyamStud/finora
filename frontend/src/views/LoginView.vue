<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Landmark, LockKeyhole, Mail } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { ApiError } from '../api/client'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit(): Promise<void> {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    await router.push(redirect)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : 'Gagal masuk. Coba lagi.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="relative flex min-h-dvh flex-col justify-center overflow-hidden bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 px-5 py-10">

    <div class="relative mx-auto w-full max-w-sm">
      <div class="mb-8 flex items-center gap-3 text-white">
        <div class="grid size-12 place-items-center rounded-2xl bg-white/15 backdrop-blur">
          <Landmark :size="24" />
        </div>
        <div>
          <h1 class="text-2xl font-extrabold">Finora</h1>
          <p class="text-xs text-brand-100">Satu tempat untuk semua uang Anda.</p>
        </div>
      </div>

      <form class="space-y-4 rounded-[28px] bg-white p-6 shadow-[0_24px_60px_rgba(8,20,60,0.35)]" @submit.prevent="submit">
        <div>
          <h2 class="text-xl font-extrabold text-ink-900">Selamat datang kembali 👋</h2>
          <p class="mt-1 text-xs text-ink-500">Masuk untuk melihat kondisi keuangan Anda.</p>
        </div>

        <div v-if="error" class="rounded-2xl bg-rose-50 px-3 py-2.5 text-xs font-semibold text-rose-600">
          {{ error }}
        </div>

        <div>
          <label class="label" for="email">Email</label>
          <div class="relative">
            <Mail :size="16" class="absolute top-1/2 left-3.5 -translate-y-1/2 text-ink-500" />
            <input id="email" v-model="email" type="email" class="field pl-10" autocomplete="email" required />
          </div>
        </div>
        <div>
          <label class="label" for="password">Kata sandi</label>
          <div class="relative">
            <LockKeyhole :size="16" class="absolute top-1/2 left-3.5 -translate-y-1/2 text-ink-500" />
            <input id="password" v-model="password" type="password" class="field pl-10" autocomplete="current-password" required />
          </div>
        </div>

        <button type="submit" class="btn-primary w-full py-3" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>

        <p class="text-center text-xs text-ink-500">
          Belum punya akun?
          <RouterLink to="/register" class="font-extrabold text-brand-700 hover:underline">Daftar</RouterLink>
        </p>
      </form>
    </div>
  </div>
</template>
