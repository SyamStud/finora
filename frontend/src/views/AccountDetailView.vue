<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, ArrowRightLeft, MoreHorizontal, Plus, Wallet, X } from 'lucide-vue-next'
import MastercardMark from '../components/MastercardMark.vue'
import CardChipMark from '../components/CardChipMark.vue'
import CurrencyInput from '../components/CurrencyInput.vue'
import { accountsApi, pocketsApi } from '../api'
import { useToastStore } from '../stores/toast'
import { rupiah, shortDate, todayISO } from '../utils/format'
import type { Pocket } from '../types'

const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const toast = useToastStore()

const accountId = computed(() => String(route.params.id))
const showPocket = ref(false)
const showAllocate = ref(false)
const editingPocket = ref<Pocket | null>(null)
const pocketEditForm = ref({ name: '', color: '#1b4b4a' })

const pocketForm = ref({ name: '', color: '#e8694f' })
const allocateForm = ref({
  source: 'base',
  destination: '',
  amount: '',
  note: '',
  allocated_at: todayISO(),
})

const accountQuery = useQuery({
  queryKey: computed(() => ['account', accountId.value]),
  queryFn: () => accountsApi.show(accountId.value),
})
const pocketsQuery = useQuery({
  queryKey: computed(() => ['pockets', accountId.value]),
  queryFn: () => pocketsApi.list(accountId.value),
})
const allocationsQuery = useQuery({
  queryKey: computed(() => ['allocations', accountId.value]),
  queryFn: () => pocketsApi.allocations(accountId.value),
})

const account = computed(() => accountQuery.data.value)
const pockets = computed(() => pocketsQuery.data.value ?? [])
const allocations = computed(() => allocationsQuery.data.value ?? [])

const colors = ['#1b4b4a', '#2e6d66', '#e8694f', '#f48066', '#3b4d4c', '#8fc2b8', '#ffc2ae']

const destinationOptions = computed(() => [
  { value: 'base', label: 'Saldo dasar' },
  ...pockets.value.map((pocket) => ({ value: pocket.id, label: pocket.name })),
])
const sourceOptions = computed(() => destinationOptions.value)

async function refresh(): Promise<void> {
  await Promise.all([
    queryClient.invalidateQueries({ queryKey: ['account', accountId.value] }),
    queryClient.invalidateQueries({ queryKey: ['pockets', accountId.value] }),
    queryClient.invalidateQueries({ queryKey: ['allocations', accountId.value] }),
    queryClient.invalidateQueries({ queryKey: ['accounts'] }),
    queryClient.invalidateQueries({ queryKey: ['dashboard'] }),
  ])
}

const pocketMutation = useMutation({
  mutationFn: () => pocketsApi.create(accountId.value, pocketForm.value),
  onSuccess: async () => {
    await refresh()
    showPocket.value = false
    pocketForm.value = { name: '', color: '#1b4b4a' }
    toast.success('Pocket dibuat.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const pocketUpdateMutation = useMutation({
  mutationFn: () => pocketsApi.update(editingPocket.value!.id, pocketEditForm.value),
  onSuccess: async () => {
    await refresh()
    editingPocket.value = null
    toast.success('Pocket diperbarui.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const pocketArchiveMutation = useMutation({
  mutationFn: (id: string) => pocketsApi.archive(id),
  onSuccess: async () => {
    await refresh()
    editingPocket.value = null
    toast.success('Pocket diarsipkan.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const allocateMutation = useMutation({
  mutationFn: () =>
    pocketsApi.allocate({
      account_id: accountId.value,
      source_pocket_id: allocateForm.value.source === 'base' ? null : allocateForm.value.source,
      destination_pocket_id: allocateForm.value.destination === 'base' ? null : allocateForm.value.destination,
      amount: Number(allocateForm.value.amount),
      note: allocateForm.value.note || null,
      allocated_at: allocateForm.value.allocated_at,
    }),
  onSuccess: async () => {
    await refresh()
    showAllocate.value = false
    allocateForm.value = { source: 'base', destination: '', amount: '', note: '', allocated_at: todayISO() }
    toast.success('Alokasi berhasil.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const canAllocate = computed(() =>
  Boolean(
    allocateForm.value.destination &&
    allocateForm.value.amount &&
    Number(allocateForm.value.amount) > 0 &&
    allocateForm.value.source !== allocateForm.value.destination,
  ),
)

function openAllocate(): void {
  allocateForm.value.destination = pockets.value.length === 1 ? pockets.value[0]!.id : ''
  showAllocate.value = true
}

function maskedNumber(id: string): string {
  return id.replace(/-/g, '').slice(-4).toUpperCase()
}

function openPocketEdit(pocket: Pocket): void {
  pocketEditForm.value = { name: pocket.name, color: pocket.color ?? '#1b4b4a' }
  editingPocket.value = pocket
}
</script>

<template>
  <div class="space-y-5">
    <button class="flex items-center gap-1.5 text-sm font-semibold text-ink-500 hover:text-brand-800" @click="router.push({ name: 'accounts' })">
      <ArrowLeft :size="16" /> Semua rekening
    </button>

    <div v-if="accountQuery.isPending.value" class="card animate-pulse h-48" />
    <div v-else-if="accountQuery.isError.value || !account" class="card text-sm text-rose-600">
      Rekening tidak ditemukan.
    </div>

    <template v-else>
      <section
        class="bank-card h-52"
        :style="{ backgroundColor: account.color ?? '#1b4b4a' }"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[9px] font-semibold tracking-widest text-white/60 uppercase">Nama rekening</p>
            <p class="mt-0.5 truncate text-sm font-extrabold">{{ account.name }}</p>
          </div>
          <MastercardMark />
        </div>

        <div class="mt-3">
          <p class="text-[9px] font-semibold tracking-widest text-white/60 uppercase">Saldo</p>
          <p class="mt-0.5 text-xl font-extrabold tracking-tight">{{ rupiah(account.balance) }}</p>
        </div>

        <div class="mt-auto text-white/80">
          <div class="flex justify-end">
            <CardChipMark />
          </div>
          <p class="mt-2 font-mono text-[13px] font-semibold tracking-[0.18em]">
            •••• •••• •••• {{ maskedNumber(account.id) }}
          </p>
        </div>
      </section>

      <section class="grid grid-cols-2 gap-3">
        <div class="card">
          <p class="text-xs text-ink-500">Saldo dasar</p>
          <p class="mt-1 text-lg font-bold text-ink-900">{{ rupiah(account.base_balance) }}</p>
          <p class="mt-0.5 text-[11px] text-ink-500">Belum dialokasikan</p>
        </div>
        <div class="card">
          <p class="text-xs text-ink-500">Total pocket</p>
          <p class="mt-1 text-lg font-bold text-brand-800">{{ rupiah(account.pockets_balance) }}</p>
          <p class="mt-0.5 text-[11px] text-ink-500">{{ pockets.length }} pocket aktif</p>
        </div>
      </section>

      <div class="flex flex-wrap gap-2">
        <button class="btn-primary" @click="showPocket = true"><Plus :size="16" /> Buat pocket</button>
        <button class="btn-secondary" @click="openAllocate"><ArrowRightLeft :size="16" /> Alokasi dana</button>
      </div>

      <section>
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-extrabold text-ink-900">Pocket di {{ account.name }}</h2>
          <span class="text-xs text-ink-500">{{ pockets.length }} pocket</span>
        </div>
        <div class="card">
          <div class="space-y-2">
            <div
              v-for="pocket in pockets"
              :key="pocket.id"
              class="flex items-center gap-3 rounded-2xl bg-slate-50 px-3 py-3"
            >
              <span class="grid size-10 place-items-center rounded-xl bg-white" :style="{ color: pocket.color ?? '#1b4b4a' }">
                <Wallet :size="18" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-ink-900">{{ pocket.name }}</p>
                <p class="text-[11px] text-ink-500">Bagian dari {{ account.name }}</p>
              </div>
              <p class="text-sm font-bold text-ink-900">{{ rupiah(pocket.balance) }}</p>
              <button
                class="rounded-lg p-1.5 text-ink-500 transition hover:bg-white hover:text-brand-700"
                aria-label="Edit pocket"
                @click="openPocketEdit(pocket)"
              >
                <MoreHorizontal :size="18" />
              </button>
            </div>
            <p v-if="!pockets.length" class="py-8 text-center text-sm text-ink-500">
              Belum ada pocket. Buat pocket untuk memisahkan uang jajan, kebutuhan, dll.
            </p>
          </div>
        </div>
      </section>

      <section class="card">
        <h2 class="mb-4 text-sm font-bold text-ink-900">Riwayat alokasi</h2>
        <div class="space-y-3">
          <div v-for="item in allocations" :key="item.id" class="flex items-center gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0">
            <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-700">
              <ArrowRightLeft :size="16" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-ink-900">{{ item.source }} → {{ item.destination }}</p>
              <p class="text-xs text-ink-500">{{ shortDate(item.allocated_at) }}<span v-if="item.note"> · {{ item.note }}</span></p>
            </div>
            <p class="text-sm font-bold text-ink-900">{{ rupiah(item.amount) }}</p>
          </div>
          <p v-if="!allocations.length" class="py-6 text-center text-sm text-ink-500">Belum ada alokasi.</p>
        </div>
      </section>
    </template>

    <Teleport to="body">
      <div v-if="showPocket" class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4" @click.self="showPocket = false">
        <form class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl" @submit.prevent="pocketMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-bold">Pocket baru</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="showPocket = false">
              <X :size="20" />
            </button>
          </div>
          <p class="mb-4 text-xs text-ink-500">Pocket dibuat di dalam rekening {{ account?.name }}.</p>
          <div class="space-y-4">
            <div>
              <label class="label" for="pocket-name">Nama pocket</label>
              <input id="pocket-name" v-model="pocketForm.name" class="field" placeholder="Jajan, Kebutuhan, dll" required />
            </div>
            <div>
              <label class="label">Warna</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="color in colors"
                  :key="color"
                  type="button"
                  class="size-8 rounded-full ring-2 transition"
                  :class="pocketForm.color === color ? 'ring-ink-900 ring-offset-2' : 'ring-transparent'"
                  :style="{ background: color }"
                  @click="pocketForm.color = color"
                />
              </div>
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="pocketMutation.isPending.value">
            Buat pocket
          </button>
        </form>
      </div>

      <div v-if="showAllocate" class="fixed inset-0 z-40 flex items-end justify-center bg-ink-900/40 sm:items-center" @click.self="showAllocate = false">
        <form class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-3xl" @submit.prevent="allocateMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-bold">Alokasi dana</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="showAllocate = false">
              <X :size="20" />
            </button>
          </div>
          <p class="mb-4 text-xs text-ink-500">Memindahkan uang di dalam {{ account?.name }}. Total saldo rekening tidak berubah.</p>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label" for="alloc-source">Dari</label>
                <select id="alloc-source" v-model="allocateForm.source" class="field">
                  <option v-for="item in sourceOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
              </div>
              <div>
                <label class="label" for="alloc-dest">Ke</label>
                <select id="alloc-dest" v-model="allocateForm.destination" class="field" required>
                  <option value="" disabled>Pilih tujuan</option>
                  <option v-for="item in destinationOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="label" for="alloc-amount">Jumlah</label>
              <CurrencyInput id="alloc-amount" v-model="allocateForm.amount" prefix="Rp" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label" for="alloc-date">Tanggal</label>
                <input id="alloc-date" v-model="allocateForm.allocated_at" type="date" class="field" required />
              </div>
              <div>
                <label class="label" for="alloc-note">Catatan</label>
                <input id="alloc-note" v-model="allocateForm.note" class="field" placeholder="Opsional" />
              </div>
            </div>
            <p v-if="allocateForm.source === allocateForm.destination" class="text-xs text-rose-600">
              Sumber dan tujuan harus berbeda.
            </p>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="!canAllocate || allocateMutation.isPending.value">
            Simpan alokasi
          </button>
        </form>
      </div>

      <div v-if="editingPocket" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/40 px-4" @click.self="editingPocket = null">
        <form class="w-full max-w-sm rounded-[28px] bg-white p-6 shadow-2xl" @submit.prevent="pocketUpdateMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">Edit pocket</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="editingPocket = null">
              <X :size="20" />
            </button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="label" for="edit-pocket-name">Nama pocket</label>
              <input id="edit-pocket-name" v-model="pocketEditForm.name" class="field" required />
            </div>
            <div>
              <label class="label">Warna</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="color in colors"
                  :key="color"
                  type="button"
                  class="size-8 rounded-full ring-2 transition"
                  :class="pocketEditForm.color === color ? 'ring-ink-900 ring-offset-2' : 'ring-transparent'"
                  :style="{ background: color }"
                  @click="pocketEditForm.color = color"
                />
              </div>
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="pocketUpdateMutation.isPending.value">
            Simpan perubahan
          </button>
          <button
            type="button"
            class="mt-3 w-full py-2 text-xs font-semibold text-rose-600 hover:underline"
            :disabled="pocketArchiveMutation.isPending.value"
            @click="pocketArchiveMutation.mutate(editingPocket.id)"
          >
            Arsipkan pocket
          </button>
        </form>
      </div>
    </Teleport>
  </div>
</template>
