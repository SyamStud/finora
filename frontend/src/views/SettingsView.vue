<script setup lang="ts">
import { useRouter } from 'vue-router'
import { CircleHelp, LogOut, ShieldCheck, Smartphone } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="space-y-5">
    <section class="hero-card">
      <div class="relative flex items-center gap-4">
        <div class="grid size-16 place-items-center rounded-3xl bg-white/15 text-2xl font-extrabold">
          {{ auth.user?.name?.charAt(0)?.toUpperCase() ?? 'F' }}
        </div>
        <div>
          <p class="text-xs text-brand-100">Akun Anda</p>
          <p class="text-xl font-extrabold">{{ auth.user?.name }}</p>
          <p class="text-xs text-brand-100">{{ auth.user?.email }}</p>
        </div>
      </div>
    </section>

    <section class="app-card space-y-4">
      <h2 class="text-sm font-extrabold text-ink-900">Aplikasi</h2>
      <div class="flex items-center gap-3 text-sm font-medium text-ink-700">
        <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-700"><ShieldCheck :size="17" /></span>
        Data Anda tersimpan aman di server.
      </div>
      <div class="flex items-center gap-3 text-sm font-medium text-ink-700">
        <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-700"><Smartphone :size="17" /></span>
        Finora bisa dipasang sebagai aplikasi (PWA).
      </div>
      <div class="flex items-center gap-3 text-sm font-medium text-ink-700">
        <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-700"><CircleHelp :size="17" /></span>
        Versi 1.0 · Personal Finance Tracker
      </div>
    </section>

    <button class="btn-secondary w-full !text-rose-600" @click="logout">
      <LogOut :size="16" /> Keluar dari akun
    </button>
  </div>
</template>
