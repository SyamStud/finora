<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeftRight, ArrowDownLeft, ArrowUpRight, X } from 'lucide-vue-next'
import { accountsApi, categoriesApi, pocketsApi, transactionsApi, transfersApi } from '../api'
import SelectField from './SelectField.vue'
import CurrencyInput from './CurrencyInput.vue'
import { useToastStore } from '../stores/toast'
import { todayISO } from '../utils/format'

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const queryClient = useQueryClient()
const toast = useToastStore()

const mode = ref<'expense' | 'income' | 'transfer'>('expense')
const amount = ref('')
const accountId = ref('')
const pocketId = ref('base')
const destinationId = ref('')
const categoryId = ref('')
const description = ref('')
const fee = ref('')
const date = ref(todayISO())

const accountsQuery = useQuery({
  queryKey: ['accounts'],
  queryFn: () => accountsApi.list(),
  enabled: computed(() => props.open),
})
const categoriesQuery = useQuery({
  queryKey: ['categories'],
  queryFn: () => categoriesApi.list(),
  enabled: computed(() => props.open),
})
const pocketsQuery = useQuery({
  queryKey: computed(() => ['pockets', accountId.value]),
  queryFn: () => pocketsApi.list(accountId.value),
  enabled: computed(() => props.open && mode.value !== 'transfer' && Boolean(accountId.value)),
})

const accounts = computed(() => accountsQuery.data.value ?? [])
const pockets = computed(() => pocketsQuery.data.value ?? [])
const categories = computed(() =>
  (categoriesQuery.data.value ?? []).filter((item) => item.type === mode.value),
)

const accountOptions = computed(() =>
  accounts.value.map((item) => ({ value: item.id, label: item.name })),
)
const categoryOptions = computed(() =>
  categories.value.map((item) => ({ value: item.id, label: item.name })),
)
const pocketOptions = computed(() => [
  { value: 'base', label: 'Saldo dasar rekening' },
  ...pockets.value.map((item) => ({ value: item.id, label: item.name })),
])

watch(
  () => props.open,
  (open) => {
    if (!open) return
    if (!accountId.value && accounts.value.length > 0) {
      accountId.value = accounts.value[0]!.id
    }
  },
)

// Saat form dibuka, tambahkan entri history agar tombol back HP menutup
// modal terlebih dahulu (bukan langsung mundur ke halaman sebelumnya).
watch(
  () => props.open,
  (open, previous) => {
    if (open && !previous) {
      window.history.pushState({ finoraQuickAdd: true }, '')
    } else if (!open && previous && window.history.state?.finoraQuickAdd) {
      window.history.back()
    }
  },
)

function onPopState(): void {
  if (props.open) {
    emit('update:open', false)
  }
}

onMounted(() => window.addEventListener('popstate', onPopState))
onUnmounted(() => window.removeEventListener('popstate', onPopState))

watch(mode, () => {
  categoryId.value = ''
  pocketId.value = 'base'
})

watch(accountId, () => {
  pocketId.value = 'base'
})

function close(): void {
  emit('update:open', false)
}

function reset(): void {
  amount.value = ''
  description.value = ''
  fee.value = ''
  categoryId.value = ''
  pocketId.value = 'base'
  date.value = todayISO()
}

async function invalidate(): Promise<void> {
  await Promise.all([
    queryClient.invalidateQueries({ queryKey: ['dashboard'] }),
    queryClient.invalidateQueries({ queryKey: ['transactions'] }),
    queryClient.invalidateQueries({ queryKey: ['accounts'] }),
    queryClient.invalidateQueries({ queryKey: ['pockets'] }),
    queryClient.invalidateQueries({ queryKey: ['transfers'] }),
    queryClient.invalidateQueries({ queryKey: ['budgets'] }),
  ])
}

const saveMutation = useMutation({
  mutationFn: async () => {
    const numericAmount = Number(amount.value)
    if (mode.value === 'transfer') {
      return transfersApi.create({
        source_account_id: accountId.value,
        destination_account_id: destinationId.value,
        amount: numericAmount,
        fee: fee.value ? Number(fee.value) : 0,
        description: description.value || null,
        transfer_date: date.value,
      })
    }

    return transactionsApi.create({
      type: mode.value,
      amount: numericAmount,
      account_id: accountId.value,
      pocket_id: pocketId.value === 'base' ? null : pocketId.value,
      category_id: categoryId.value || null,
      description: description.value,
      transaction_date: date.value,
    })
  },
  onSuccess: async () => {
    await invalidate()
    reset()
    close()
    toast.success(mode.value === 'transfer' ? 'Transfer berhasil dicatat.' : 'Transaksi tersimpan.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const canSubmit = computed(() => {
  if (!amount.value || Number(amount.value) <= 0 || !accountId.value || !date.value) return false
  if (mode.value === 'transfer') return Boolean(destinationId.value)
  return Boolean(description.value.trim()) && Boolean(categoryId.value)
})

const tabs = [
  { key: 'expense' as const, label: 'Pengeluaran', icon: ArrowUpRight },
  { key: 'income' as const, label: 'Pemasukan', icon: ArrowDownLeft },
  { key: 'transfer' as const, label: 'Transfer', icon: ArrowLeftRight },
]
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-40 flex items-end justify-center bg-ink-900/40 sm:items-center" @click.self="close">
        <div class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-lg sm:rounded-3xl">
          <div class="mb-5 flex items-start justify-between">
            <div>
              <h2 class="text-xl font-bold text-ink-900">Tambah transaksi</h2>
              <p class="mt-0.5 text-sm text-ink-500">Catat dengan cepat, tanpa form panjang.</p>
            </div>
            <button class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="close">
              <X :size="20" />
            </button>
          </div>

          <div class="mb-5 grid grid-cols-3 gap-2 rounded-2xl bg-slate-100 p-1">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              class="flex items-center justify-center gap-1.5 rounded-xl px-2 py-2 text-xs font-semibold transition"
              :class="mode === tab.key ? 'bg-white text-brand-800 shadow-sm' : 'text-ink-500'"
              @click="mode = tab.key"
            >
              <component :is="tab.icon" :size="14" />
              {{ tab.label }}
            </button>
          </div>

          <form class="space-y-4" @submit.prevent="saveMutation.mutate()">
            <template v-if="mode !== 'transfer'">
              <SelectField
                id="qa-category"
                v-model="categoryId"
                label="Kategori"
                placeholder="Pilih kategori"
                :options="categoryOptions"
                required
              />

              <div>
                <label class="label" for="qa-amount">Jumlah</label>
                <CurrencyInput id="qa-amount" v-model="amount" prefix="Rp" required />
              </div>

              <SelectField
                id="qa-account"
                v-model="accountId"
                label="Rekening"
                placeholder="Pilih rekening"
                :options="accountOptions"
                required
              />

              <SelectField
                id="qa-pocket"
                v-model="pocketId"
                label="Pocket"
                :options="pocketOptions"
              />
            </template>

            <template v-else>
              <div>
                <label class="label" for="qa-amount">Jumlah</label>
                <CurrencyInput id="qa-amount" v-model="amount" prefix="Rp" required />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <SelectField
                  id="qa-account"
                  v-model="accountId"
                  label="Dari rekening"
                  placeholder="Pilih rekening"
                  :options="accountOptions"
                  required
                />
                <SelectField
                  id="qa-destination"
                  v-model="destinationId"
                  label="Ke rekening"
                  placeholder="Pilih rekening"
                  :options="accountOptions"
                  required
                />
              </div>
            </template>

            <div>
              <label class="label" for="qa-description">Deskripsi</label>
              <input
                id="qa-description"
                v-model="description"
                type="text"
                class="field"
                :placeholder="mode === 'transfer' ? 'Contoh: transfer ke wallet' : 'Contoh: beli kopi'"
                :required="mode !== 'transfer'"
              />
            </div>

            <div>
              <label class="label" for="qa-date">Tanggal</label>
              <input id="qa-date" v-model="date" type="date" class="field" required />
            </div>

            <div v-if="mode === 'transfer'">
              <label class="label" for="qa-fee">Biaya (opsional)</label>
              <CurrencyInput id="qa-fee" v-model="fee" prefix="Rp" />
            </div>

            <button type="submit" class="btn-primary w-full py-3" :disabled="!canSubmit || saveMutation.isPending.value">
              {{ saveMutation.isPending.value ? 'Menyimpan...' : 'Simpan transaksi' }}
            </button>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
