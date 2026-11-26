<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowDownLeft, ArrowUpRight, Bell, ChevronDown, ListFilter, Search, Trash2 } from 'lucide-vue-next'
import { accountsApi, categoriesApi, transactionsApi } from '../api'
import { useToastStore } from '../stores/toast'
import { compactRupiah, shortDate } from '../utils/format'

const queryClient = useQueryClient()
const toast = useToastStore()

const search = ref('')
const type = ref('')
const accountId = ref('')
const categoryId = ref('')
const page = ref(1)
const showFilter = ref(false)
const pendingDelete = ref<string | null>(null)

const filters = computed(() => ({
  search: search.value,
  type: type.value,
  account_id: accountId.value,
  category_id: categoryId.value,
  page: page.value,
  per_page: 20,
}))

const query = useQuery({
  queryKey: computed(() => ['transactions', filters.value]),
  queryFn: () => transactionsApi.list(filters.value),
})

const accountsQuery = useQuery({ queryKey: ['accounts'], queryFn: () => accountsApi.list() })
const categoriesQuery = useQuery({ queryKey: ['categories'], queryFn: () => categoriesApi.list() })

const transactions = computed(() => query.data.value?.data ?? [])
const meta = computed(() => query.data.value?.meta)

const grouped = computed(() => {
  const result: { date: string; items: typeof transactions.value }[] = []
  for (const item of transactions.value) {
    const date = item.transaction_date.slice(0, 10)
    let group = result.find((entry) => entry.date === date)
    if (!group) {
      group = { date, items: [] }
      result.push(group)
    }
    group.items.push(item)
  }
  return result
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => transactionsApi.remove(id),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    pendingDelete.value = null
    toast.success('Transaksi dihapus.')
  },
  onError: (error: Error) => {
    pendingDelete.value = null
    toast.error(error.message)
  },
})

function askRemove(id: string): void {
  pendingDelete.value = id
}

function confirmRemove(): void {
  if (pendingDelete.value === null) return
  deleteMutation.mutate(pendingDelete.value)
}

function resetFilters(): void {
  search.value = ''
  type.value = ''
  accountId.value = ''
  categoryId.value = ''
  page.value = 1
}
</script>

<template>
  <div class="space-y-2.5">
    <!-- Ringkas: search + toggle filter -->
    <div class="flex items-center gap-2">
      <div class="relative flex-1">
        <Search :size="16" class="absolute top-1/2 left-3.5 -translate-y-1/2 text-ink-500" />
        <input v-model="search" class="field !rounded-2xl pl-10" placeholder="Cari transaksi..." @input="page = 1" />
      </div>
      <button
        class="btn-secondary !px-3 !py-2.5 !text-xs"
        :class="showFilter || type || accountId || categoryId ? '!border-brand-200 !text-brand-700' : ''"
        @click="showFilter = !showFilter"
      >
        <ListFilter :size="15" /> Filter
        <ChevronDown :size="14" :class="showFilter ? 'rotate-180 transition' : 'transition'" />
      </button>
      <RouterLink to="/pending" class="btn-secondary !px-3 !py-2.5 !text-xs" aria-label="Transaksi pending">
        <Bell :size="15" />
      </RouterLink>
    </div>

    <div class="no-scrollbar flex gap-2 overflow-x-auto">
      <button
        v-for="option in [
          { value: '', label: 'Semua' },
          { value: 'income', label: 'Pemasukan' },
          { value: 'expense', label: 'Pengeluaran' },
        ]"
        :key="option.value"
        class="chip shrink-0 border transition"
        :class="type === option.value
          ? 'border-brand-800 bg-brand-800 text-white'
          : 'border-[#ece4dd] bg-white text-ink-700'"
        @click="type = option.value; page = 1"
      >
        {{ option.label }}
      </button>
    </div>

    <div v-if="showFilter" class="app-card !p-4">
      <div class="grid gap-3 sm:grid-cols-2">
        <div>
          <label class="label" for="filter-account">Rekening</label>
          <select id="filter-account" v-model="accountId" class="field" @change="page = 1">
            <option value="">Semua rekening</option>
            <option v-for="account in accountsQuery.data.value ?? []" :key="account.id" :value="account.id">{{ account.name }}</option>
          </select>
        </div>
        <div>
          <label class="label" for="filter-category">Kategori</label>
          <select id="filter-category" v-model="categoryId" class="field" @change="page = 1">
            <option value="">Semua kategori</option>
            <option v-for="category in categoriesQuery.data.value ?? []" :key="category.id" :value="category.id">{{ category.name }}</option>
          </select>
        </div>
      </div>
      <button class="mt-3 text-xs font-semibold text-accent-500 hover:underline" @click="resetFilters">Reset filter</button>
    </div>

    <div v-if="query.isPending.value" class="app-card h-48 animate-pulse" />
    <div v-else-if="query.isError.value" class="card text-sm text-accent-500">Gagal memuat transaksi.</div>

    <template v-else>
      <section v-for="group in grouped" :key="group.date" class="app-card !p-4">
        <h2 class="mb-2 text-xs font-extrabold text-ink-900">{{ shortDate(group.date) }}</h2>
        <div class="divide-y divide-[#f3ece6]">
          <div v-for="item in group.items" :key="item.id" class="flex items-center gap-2.5 py-2.5 first:pt-1 last:pb-0">
            <span
              class="grid size-9 shrink-0 place-items-center rounded-full"
              :class="item.type === 'income' ? 'bg-brand-50 text-brand-700' : 'bg-accent-50 text-accent-500'"
            >
              <ArrowDownLeft v-if="item.type === 'income'" :size="17" />
              <ArrowUpRight v-else :size="17" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-bold text-ink-900">{{ item.description }}</p>
              <p class="truncate text-[11px] text-ink-500">
                {{ item.category?.name ?? 'Tanpa kategori' }} · {{ item.account?.name }}
                <template v-if="item.pocket"> · {{ item.pocket.name }}</template>
              </p>
            </div>
            <p class="text-sm font-extrabold" :class="item.type === 'income' ? 'amount-positive' : 'amount-negative'">
              {{ item.type === 'income' ? '+' : '−' }}{{ compactRupiah(item.amount) }}
            </p>
            <button
              class="grid size-8 shrink-0 place-items-center rounded-full bg-[#fdf9f5] text-ink-500 transition hover:bg-accent-50 hover:text-accent-500"
              aria-label="Hapus transaksi"
              @click="askRemove(item.id)"
            >
              <Trash2 :size="15" />
            </button>
          </div>
        </div>
      </section>

      <p v-if="!transactions.length" class="app-card py-12 text-center text-sm text-ink-500">
        Tidak ada transaksi yang cocok.
      </p>

      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-center gap-3">
        <button class="btn-secondary !px-3 !py-2 !text-xs" :disabled="page <= 1" @click="page--">Sebelumnya</button>
        <span class="text-xs text-ink-500">{{ meta.current_page }} / {{ meta.last_page }}</span>
        <button class="btn-secondary !px-3 !py-2 !text-xs" :disabled="page >= meta.last_page" @click="page++">Berikutnya</button>
      </div>
    </template>

    <Teleport to="body">
      <div
        v-if="pendingDelete"
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/30 px-4"
        @click.self="pendingDelete = null"
      >
        <div class="w-full max-w-xs rounded-3xl bg-white p-6 text-center shadow-2xl">
          <span class="mx-auto grid size-12 place-items-center rounded-full bg-accent-50 text-accent-500">
            <Trash2 :size="22" />
          </span>
          <h3 class="mt-4 text-lg font-extrabold text-ink-900">Hapus transaksi?</h3>
          <p class="mt-1 text-xs text-ink-500">Transaksi ini akan dihapus dan saldo akan disesuaikan.</p>
          <div class="mt-6 grid grid-cols-2 gap-2">
            <button class="btn-secondary" @click="pendingDelete = null">Batal</button>
            <button
              class="inline-flex items-center justify-center rounded-2xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-accent-400 disabled:opacity-50"
              :disabled="deleteMutation.isPending.value"
              @click="confirmRemove"
            >
              {{ deleteMutation.isPending.value ? 'Menghapus...' : 'Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
