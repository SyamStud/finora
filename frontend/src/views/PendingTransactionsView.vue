<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { Check, Sparkles, X } from 'lucide-vue-next'
import CurrencyInput from '../components/CurrencyInput.vue'
import SelectField from '../components/SelectField.vue'
import { accountsApi, categoriesApi, pendingApi } from '../api'
import { useToastStore } from '../stores/toast'
import { rupiah, shortDate, todayISO } from '../utils/format'
import type { PendingTransaction } from '../types'

const queryClient = useQueryClient()
const toast = useToastStore()
const editing = ref<PendingTransaction | null>(null)
const editForm = ref({ type: 'expense', amount: '', account_id: '', category_id: '', description: '', transaction_date: todayISO() })

const query = useQuery({ queryKey: ['pending'], queryFn: () => pendingApi.list() })
const accountsQuery = useQuery({ queryKey: ['accounts'], queryFn: () => accountsApi.list() })
const categoriesQuery = useQuery({ queryKey: ['categories'], queryFn: () => categoriesApi.list() })

const items = computed(() => query.data.value ?? [])
const accountOptions = computed(() =>
  (accountsQuery.data.value ?? []).map((item) => ({ value: item.id, label: item.name })),
)
const categoryOptions = computed(() =>
  (categoriesQuery.data.value ?? []).map((item) => ({ value: item.id, label: item.name })),
)

async function refresh(): Promise<void> {
  await Promise.all([
    queryClient.invalidateQueries({ queryKey: ['pending'] }),
    queryClient.invalidateQueries({ queryKey: ['transactions'] }),
    queryClient.invalidateQueries({ queryKey: ['dashboard'] }),
    queryClient.invalidateQueries({ queryKey: ['accounts'] }),
  ])
}

const approveMutation = useMutation({
  mutationFn: (id: string) => pendingApi.approve(id),
  onSuccess: async () => {
    await refresh()
    toast.success('Transaksi disetujui dan disimpan.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const rejectMutation = useMutation({
  mutationFn: (id: string) => pendingApi.reject(id),
  onSuccess: async () => {
    await refresh()
    toast.success('Transaksi ditolak.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const updateMutation = useMutation({
  mutationFn: () => pendingApi.update(editing.value!.id, {
    type: editForm.value.type,
    amount: editForm.value.amount,
    account_id: editForm.value.account_id,
    category_id: editForm.value.category_id || null,
    description: editForm.value.description,
    transaction_date: editForm.value.transaction_date,
  }),
  onSuccess: async () => {
    await refresh()
    editing.value = null
    toast.success('Perubahan disimpan.')
  },
  onError: (error: Error) => toast.error(error.message),
})

function openEdit(item: PendingTransaction): void {
  editForm.value = {
    type: item.type,
    amount: item.amount ?? '',
    account_id: item.account?.id ?? '',
    category_id: item.category?.id ?? '',
    description: item.description ?? '',
    transaction_date: item.transaction_date?.slice(0, 10) ?? todayISO(),
  }
  editing.value = item
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center gap-2 rounded-2xl bg-brand-50 p-3 text-[11px] text-brand-800">
      <Sparkles :size="16" class="shrink-0" />
      Notifikasi dari HP diparse otomatis. Periksa lalu setujui transaksi yang benar.
    </div>

    <div v-if="query.isPending.value" class="app-card h-40 animate-pulse" />
    <div v-else-if="query.isError.value" class="card text-sm text-accent-500">Gagal memuat transaksi pending.</div>

    <template v-else>
      <article v-for="item in items" :key="item.id" class="app-card">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-extrabold text-ink-900">
              {{ item.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
              <span v-if="item.amount" class="ml-1">{{ rupiah(item.amount) }}</span>
            </p>
            <p class="mt-0.5 truncate text-[11px] text-ink-500">
              {{ shortDate(item.transaction_date ?? item.received_at) }} · {{ item.source }}
              <span v-if="item.confidence"> · keyakinan {{ Math.round(Number(item.confidence) * 100) }}%</span>
            </p>
          </div>
          <span class="chip shrink-0 bg-accent-50 text-accent-500">{{ item.status }}</span>
        </div>

        <div class="mt-3 rounded-2xl bg-[#fdf9f5] px-3 py-2 text-[11px] text-ink-700">
          "{{ item.raw_text }}"
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
          <span class="chip bg-white text-ink-700">{{ item.account?.name ?? item.account_name ?? 'Rekening belum dipilih' }}</span>
          <span class="chip bg-white text-ink-700">{{ item.category?.name ?? item.category_name ?? 'Tanpa kategori' }}</span>
          <span class="chip bg-white text-ink-700">{{ item.description || 'Tanpa deskripsi' }}</span>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <button class="btn-secondary !px-3 !py-2 !text-xs" @click="openEdit(item)">Periksa &amp; edit</button>
          <button
            class="inline-flex items-center gap-1 rounded-2xl bg-brand-800 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50"
            :disabled="approveMutation.isPending.value || rejectMutation.isPending.value"
            @click="approveMutation.mutate(item.id)"
          >
            <Check :size="14" /> Setujui
          </button>
          <button
            class="inline-flex items-center gap-1 rounded-2xl border border-[#ece4dd] px-3 py-2 text-xs font-semibold text-ink-700 disabled:opacity-50"
            :disabled="approveMutation.isPending.value || rejectMutation.isPending.value"
            @click="rejectMutation.mutate(item.id)"
          >
            <X :size="14" /> Tolak
          </button>
        </div>
      </article>

      <p v-if="!items.length" class="app-card py-12 text-center text-sm text-ink-500">
        Belum ada transaksi pending. Notifikasi yang masuk akan muncul di sini.
      </p>
    </template>

    <Teleport to="body">
      <div v-if="editing" class="fixed inset-0 z-40 flex items-end justify-center bg-ink-900/30 sm:items-center" @click.self="editing = null">
        <form class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-3xl" @submit.prevent="updateMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">Periksa transaksi</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="editing = null"><X :size="20" /></button>
          </div>
          <div class="space-y-4">
            <SelectField v-model="editForm.type" label="Tipe" :options="[{ value: 'expense', label: 'Pengeluaran' }, { value: 'income', label: 'Pemasukan' }]" />
            <div>
              <label class="label" for="pending-amount">Nominal</label>
              <CurrencyInput id="pending-amount" v-model="editForm.amount" prefix="Rp" required />
            </div>
            <SelectField v-model="editForm.account_id" label="Rekening" placeholder="Pilih rekening" :options="accountOptions" required />
            <SelectField v-model="editForm.category_id" label="Kategori" placeholder="Pilih kategori" :options="categoryOptions" />
            <div>
              <label class="label" for="pending-desc">Deskripsi</label>
              <input id="pending-desc" v-model="editForm.description" class="field" />
            </div>
            <div>
              <label class="label" for="pending-date">Tanggal</label>
              <input id="pending-date" v-model="editForm.transaction_date" type="date" class="field" required />
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="updateMutation.isPending.value">Simpan perubahan</button>
        </form>
      </div>
    </Teleport>
  </div>
</template>
