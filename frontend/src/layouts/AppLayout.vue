<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftRight,
  Bell,
  ChartNoAxesCombined,
  Home,
  Landmark,
  LogOut,
  PiggyBank,
  Plus,
  Settings,
  Tags,
  Wallet,
} from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import QuickAddModal from '../components/QuickAddModal.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const quickAddOpen = ref(false)

const pageTitle = computed(() => (route.meta.title as string | undefined) ?? 'Finora')

const navigation = [
  { name: 'Beranda', route: 'dashboard', icon: Home },
  { name: 'Kartu & Rekening', route: 'accounts', icon: Wallet },
  { name: 'Transaksi', route: 'transactions', icon: ArrowLeftRight },
  { name: 'Notifikasi Pending', route: 'pending', icon: Bell },
  { name: 'Aset', route: 'assets', icon: PiggyBank },
  { name: 'Anggaran', route: 'budgets', icon: ChartNoAxesCombined },
  { name: 'Kategori', route: 'categories', icon: Tags },
  { name: 'Pengaturan', route: 'settings', icon: Settings },
]

const bottomNav = [
  { name: 'Home', route: 'dashboard', icon: Home },
  { name: 'Cards', route: 'accounts', icon: Wallet },
  { name: 'Activity', route: 'transactions', icon: ArrowLeftRight },
  { name: 'Assets', route: 'assets', icon: PiggyBank },
  { name: 'Settings', route: 'settings', icon: Settings },
]

const activeNavName = computed(() =>
  route.name === 'account-detail' ? 'accounts' : route.name,
)

function isActive(name: string): boolean {
  return activeNavName.value === name
}

function openQuickAdd(): void {
  quickAddOpen.value = true
}

onMounted(() => window.addEventListener('finora:quick-add', openQuickAdd))
onUnmounted(() => window.removeEventListener('finora:quick-add', openQuickAdd))

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-dvh lg:flex">
    <!-- Sidebar desktop -->
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-brand-900 px-5 py-7 text-white lg:flex">
      <div class="flex items-center gap-3 px-2">
        <div class="grid size-11 place-items-center rounded-2xl bg-white/10">
          <Landmark :size="21" />
        </div>
        <div>
          <p class="text-lg font-extrabold leading-tight">Finora</p>
          <p class="text-[11px] text-brand-200">Satu tempat untuk semua uang</p>
        </div>
      </div>

      <nav class="mt-9 flex-1 space-y-1.5">
        <RouterLink
          v-for="item in navigation"
          :key="item.route"
          :to="{ name: item.route }"
          class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold text-brand-100 transition hover:bg-white/10"
          :class="isActive(item.route) ? '!text-white !font-extrabold' : ''"
        >
          <component :is="item.icon" :size="18" />
          {{ item.name }}
        </RouterLink>
      </nav>

      <button
        class="mt-auto flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold text-brand-100 transition hover:bg-white/10"
        @click="logout"
      >
        <LogOut :size="18" />
        Keluar
      </button>
    </aside>

    <div class="min-w-0 flex-1 lg:pl-64">
      <!-- Header -->
      <header class="px-4 pt-5 pb-3 md:px-8">
        <div class="mx-auto flex max-w-5xl items-center gap-3">
          <button class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full bg-brand-100" aria-label="Profil" @click="router.push({ name: 'settings' })">
            <span class="text-sm font-extrabold text-brand-800">{{ auth.user?.name?.charAt(0)?.toUpperCase() ?? 'F' }}</span>
          </button>
          <h1 class="flex-1 text-center text-lg font-extrabold tracking-tight text-ink-900 md:text-xl">{{ pageTitle }}</h1>
          <button class="relative grid size-10 place-items-center rounded-full bg-white text-ink-700 shadow-sm" aria-label="Notifikasi">
            <Bell :size="18" />
            <span class="absolute top-2 right-2 size-2 rounded-full bg-accent-400 ring-2 ring-white" />
          </button>
        </div>
      </header>

      <main class="mx-auto max-w-5xl px-4 pt-3 pb-28 md:px-8 lg:pb-12">
        <RouterView />
      </main>
    </div>

    <!-- Floating add button, outside the bottom nav -->
    <button
      class="fixed right-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-40 grid size-12 place-items-center rounded-full bg-accent-500 text-white shadow-lg shadow-accent-500/30 transition hover:bg-accent-400 lg:hidden"
      aria-label="Tambah transaksi"
      @click="quickAddOpen = true"
    >
      <Plus :size="24" :stroke-width="2.5" />
    </button>

    <!-- Bottom navigation -->
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-[#f0e8e1] bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
      <div class="mx-auto flex h-16 max-w-md items-center justify-around px-1">
        <RouterLink
          v-for="item in bottomNav"
          :key="item.route"
          :to="{ name: item.route }"
          class="flex min-w-14 flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-[10px] font-semibold text-ink-500 transition"
          :class="isActive(item.route) ? '!font-extrabold !text-accent-500' : ''"
        >
          <component :is="item.icon" :size="19" />
          {{ item.name }}
        </RouterLink>
      </div>
    </nav>

    <QuickAddModal v-model:open="quickAddOpen" />
  </div>
</template>
