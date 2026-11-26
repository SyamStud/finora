<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { X } from 'lucide-vue-next'
import { useAuthStore } from './stores/auth'
import { useToastStore } from './stores/toast'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()

function handleUnauthorized(): void {
  auth.reset()
  void router.push({ name: 'login' })
}

onMounted(() => window.addEventListener('finora:unauthorized', handleUnauthorized))
onUnmounted(() => window.removeEventListener('finora:unauthorized', handleUnauthorized))
</script>

<template>
  <RouterView />

  <div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4">
    <div
      v-for="item in toast.items"
      :key="item.id"
      class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl px-4 py-3 text-sm shadow-lg"
      :class="item.type === 'success' ? 'bg-brand-900 text-white' : 'bg-rose-600 text-white'"
    >
      <span class="flex-1 leading-snug">{{ item.message }}</span>
      <button class="opacity-70 hover:opacity-100" @click="toast.dismiss(item.id)">
        <X :size="16" />
      </button>
    </div>
  </div>
</template>
