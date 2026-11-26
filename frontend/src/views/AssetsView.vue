<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowUpRight, Coins, Plus, RefreshCw, Trash2, TrendingUp, X } from 'lucide-vue-next'
import CurrencyInput from '../components/CurrencyInput.vue'
import { assetsApi, fundsApi } from '../api'
import type { FundNav } from '../api'
import { useToastStore } from '../stores/toast'
import { percent, rupiah } from '../utils/format'

const queryClient = useQueryClient()
const toast = useToastStore()
const showCreate = ref(false)
const navInfo = ref<FundNav | null>(null)
const checkingNav = ref(false)
const movementMode = ref<'topup' | 'withdraw' | null>(null)
const movementAmount = ref('')
const movementNav = ref<FundNav | null>(null)

const form = ref({
  units: '',
  average_buy_price: '',
})

const PRODUCT_NAME = 'Sucorinvest Money Market Fund'
const PRODUCT_TYPE = 'mutual_fund'

const query = useQuery({ queryKey: ['assets'], queryFn: () => assetsApi.list() })
const assets = computed(() => query.data.value ?? [])
const asset = computed(() => assets.value[0] ?? null)

const movementsQuery = useQuery({
  queryKey: computed(() => ['asset-movements', asset.value?.id]),
  queryFn: () => assetsApi.movements(asset.value!.id),
  enabled: computed(() => Boolean(asset.value)),
})
const movements = computed(() => movementsQuery.data.value ?? [])
const totalValue = computed(() => assets.value.reduce((sum, item) => sum + Number(item.current_value), 0))
const totalProfit = computed(() => assets.value.reduce((sum, item) => sum + Number(item.profit_loss), 0))

const types = [
  { value: 'mutual_fund', label: 'Reksa Dana' },
  { value: 'gold', label: 'Emas' },
  { value: 'stock', label: 'Saham' },
  { value: 'deposit', label: 'Deposito' },
  { value: 'other', label: 'Lainnya' },
]

const typeStyles: Record<string, { bg: string; color: string }> = {
  mutual_fund: { bg: '#f0f7f5', color: '#1b4b4a' },
  gold: { bg: '#fef3c7', color: '#92400e' },
  stock: { bg: '#e0f2fe', color: '#075985' },
  deposit: { bg: '#ccfbf1', color: '#115e59' },
  other: { bg: '#f1f5f9', color: '#334155' },
}

function typeStyle(type: string) {
  return typeStyles[type] ?? typeStyles.other!
}

const movementMutation = useMutation({
  mutationFn: () => {
    if (!asset.value || movementMode.value === null) throw new Error('Aset tidak ditemukan.')
    const fn = movementMode.value === 'topup' ? assetsApi.topUp : assetsApi.withdraw
    return fn(asset.value.id, movementAmount.value)
  },
  onSuccess: async () => {
    await queryClient.invalidateQueries({ queryKey: ['assets'] })
    await queryClient.invalidateQueries({ queryKey: ['asset-movements'] })
    await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    movementMode.value = null
    movementAmount.value = ''
    toast.success('Pergerakan aset berhasil dicatat.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => assetsApi.disable(id),
  onSuccess: async () => {
    await queryClient.invalidateQueries({ queryKey: ['assets'] })
    await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    pendingDelete.value = null
    toast.success('Aset dihapus.')
  },
  onError: (error: Error) => {
    pendingDelete.value = null
    toast.error(error.message)
  },
})

async function openMovement(mode: 'topup' | 'withdraw'): Promise<void> {
  movementMode.value = mode
  movementAmount.value = ''
  try {
    movementNav.value = await fundsApi.nav()
  } catch {
    movementNav.value = null
  }
}

const estimatedUnits = computed(() => {
  const value = Number(movementAmount.value)
  const nav = Number(movementNav.value?.nav ?? 0)
  return nav > 0 ? (value / nav).toLocaleString('id-ID', { maximumFractionDigits: 4 }) : '—'
})

const pendingDelete = ref<string | null>(null)

function confirmDelete(): void {
  if (pendingDelete.value) deleteMutation.mutate(pendingDelete.value)
}

async function checkNav(): Promise<void> {
  checkingNav.value = true
  try {
    navInfo.value = await fundsApi.nav()
    toast.success('NAV terbaru berhasil diambil.')
  } catch (error) {
    toast.error(error instanceof Error ? error.message : 'Gagal mengambil NAV.')
  } finally {
    checkingNav.value = false
  }
}

const createMutation = useMutation({
  mutationFn: () => assetsApi.create({
    name: PRODUCT_NAME,
    type: PRODUCT_TYPE,
    provider: 'Sucorinvest Asset Management',
    units: form.value.units,
    average_buy_price: form.value.average_buy_price,
  }),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    showCreate.value = false
    form.value = { units: '', average_buy_price: '' }
    toast.success('Aset ditambahkan. Harga terkini diambil dari NAV Sucorinvest.')
  },
  onError: (error: Error) => toast.error(error.message),
})

</script>

<template>
  <div class="space-y-5">
    <section class="hero-card">
      <div class="relative flex items-start justify-between">
        <div>
          <p class="text-[11px] font-semibold tracking-wide text-brand-100 uppercase">Nilai portofolio</p>
          <p class="mt-2 text-2xl font-extrabold tracking-tight md:text-3xl">{{ rupiah(totalValue) }}</p>
          <p class="mt-2 text-[11px] font-semibold" :class="totalProfit >= 0 ? 'text-brand-100' : 'text-rose-200'">
            {{ totalProfit >= 0 ? '+' : '' }}{{ rupiah(totalProfit) }} keuntungan
          </p>
        </div>
        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white/15"><TrendingUp :size="21" /></span>
      </div>
    </section>

    <section class="card flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-[11px] font-semibold text-ink-500">Harga terbaru · Sucorinvest Money Market Fund</p>
        <p v-if="navInfo" class="mt-1 text-lg font-extrabold text-ink-900">
          {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(navInfo.nav) }}
          <span class="ml-1 text-[11px] font-medium text-ink-500">/ unit · {{ navInfo.date }}</span>
        </p>
        <p v-else class="mt-1 text-xs text-ink-500">Belum cek NAV. Sumber: Bibit (RD1657).</p>
      </div>
      <button class="btn-secondary !px-3 !py-2 !text-xs" :disabled="checkingNav" @click="checkNav">
        <RefreshCw :size="14" :class="checkingNav ? 'animate-spin' : ''" />
        {{ checkingNav ? 'Mengecek...' : 'Cek NAV' }}
      </button>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-sm font-extrabold text-ink-900">Produk investasi saya</h2>
      <div class="flex flex-wrap gap-2">
        <template v-if="asset">
          <button class="btn-primary !px-3 !py-2 !text-xs" @click="openMovement('topup')"><Plus :size="14" /> Tambah nilai</button>
          <button class="btn-secondary !px-3 !py-2 !text-xs" @click="openMovement('withdraw')"><ArrowUpRight :size="14" /> Withdraw</button>
        </template>
        <button v-else class="btn-primary !px-3 !py-2 !text-xs" @click="showCreate = true"><Plus :size="15" /> Tambah aset</button>
      </div>
    </div>

    <div v-if="query.isPending.value" class="app-card h-40 animate-pulse" />
    <div v-else class="grid gap-3 md:grid-cols-2">
      <article v-for="asset in assets" :key="asset.id" class="app-card">
        <div class="flex items-start gap-3">
          <span
            class="grid size-11 shrink-0 place-items-center rounded-2xl"
            :style="{ background: typeStyle(asset.type).bg, color: typeStyle(asset.type).color }"
          >
            <Coins :size="20" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-extrabold text-ink-900">{{ asset.name }}</p>
            <p class="truncate text-[11px] text-ink-500">
              {{ types.find((item) => item.value === asset.type)?.label ?? asset.type }}
              <span v-if="asset.provider"> · {{ asset.provider }}</span>
            </p>
          </div>
          <div class="flex shrink-0 gap-1">
            <button
              class="grid size-8 place-items-center rounded-full bg-[#fdf9f5] text-ink-500 transition hover:bg-accent-50 hover:text-accent-500"
              aria-label="Hapus aset"
              @click="pendingDelete = asset.id"
            >
              <Trash2 :size="14" />
            </button>
          </div>
        </div>

        <div class="mt-4">
          <p class="text-[11px] font-medium text-ink-500">Nilai sekarang</p>
          <p class="mt-0.5 text-xl font-extrabold tracking-tight text-ink-900">{{ rupiah(asset.current_value) }}</p>
        </div>

        <div class="mt-3 grid grid-cols-3 gap-2 rounded-2xl bg-slate-50/80 p-3">
          <div>
            <p class="text-[10px] text-ink-500">Modal Investasi</p>
            <p class="mt-0.5 truncate text-xs font-bold text-ink-900">{{ rupiah(asset.invested_amount) }}</p>
          </div>
          <div>
            <p class="text-[10px] text-ink-500">Keuntungan</p>
            <p class="mt-0.5 truncate text-xs font-bold" :class="Number(asset.profit_loss) >= 0 ? 'text-brand-700' : 'text-rose-500'">
              {{ Number(asset.profit_loss) >= 0 ? '+' : '' }}{{ rupiah(asset.profit_loss) }}
            </p>
          </div>
          <div>
            <p class="text-[10px] text-ink-500">Imbal Hasil</p>
            <p class="mt-0.5 text-xs font-bold" :class="Number(asset.profit_loss) >= 0 ? 'text-brand-700' : 'text-rose-500'">
              {{ percent(asset.return_percentage) }}
            </p>
          </div>
        </div>

        <div class="mt-3 text-[11px] text-ink-500">
          {{ asset.units }} unit · beli @ {{ rupiah(asset.average_buy_price) }}
        </div>
      </article>

      <p v-if="!assets.length" class="app-card py-12 text-center text-sm text-ink-500 md:col-span-2">
        Belum ada produk investasi. Tambahkan reksa dana pertama Anda.
      </p>
    </div>

    <section v-if="asset" class="card">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-extrabold text-ink-900">Riwayat pergerakan</h2>
        <span class="text-[11px] text-ink-500">Termasuk NAV yang dipakai</span>
      </div>
      <div class="space-y-2">
        <div v-for="item in movements" :key="item.id" class="flex items-center gap-3 rounded-2xl bg-[#fdf9f5] px-3 py-2.5">
          <span class="grid size-9 shrink-0 place-items-center rounded-full" :class="item.type === 'topup' ? 'bg-brand-50 text-brand-700' : 'bg-accent-50 text-accent-500'">
            <Plus v-if="item.type === 'topup'" :size="16" />
            <ArrowUpRight v-else :size="16" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-ink-900">{{ item.type === 'topup' ? 'Tambah nilai' : 'Withdraw' }}</p>
            <p class="text-[11px] text-ink-500">{{ item.movement_date }} · NAV {{ rupiah(item.nav) }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-extrabold text-ink-900">{{ rupiah(item.amount) }}</p>
            <p class="text-[10px] text-ink-500">{{ item.units }} unit</p>
          </div>
        </div>
        <p v-if="!movements.length" class="py-6 text-center text-xs text-ink-500">Belum ada pergerakan.</p>
      </div>
    </section>

    <Teleport to="body">
      <div v-if="showCreate" class="fixed inset-0 z-40 flex items-end justify-center bg-ink-900/40 sm:items-center" @click.self="showCreate = false">
        <form class="max-h-[92dvh] w-full overflow-y-auto rounded-t-[28px] bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-[28px]" @submit.prevent="createMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">Tambah aset</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="showCreate = false"><X :size="20" /></button>
          </div>
          <div class="mb-4 rounded-2xl bg-brand-50 p-3">
            <p class="text-xs font-extrabold text-brand-800">{{ PRODUCT_NAME }}</p>
            <p class="mt-0.5 text-[11px] text-brand-700">Reksa Dana Pasar Uang · Sucorinvest Asset Management</p>
          </div>
          <div class="space-y-4">
            <div>
              <label class="label" for="asset-units">Jumlah unit</label>
              <input id="asset-units" v-model="form.units" type="number" min="0" step="any" class="field" placeholder="1250" required />
            </div>
            <div>
              <label class="label" for="asset-buy">Harga beli per unit</label>
              <CurrencyInput id="asset-buy" v-model="form.average_buy_price" prefix="Rp" required />
            </div>
            <p class="text-[11px] text-ink-500">
              Harga saat ini dan keuntungan dihitung otomatis dari NAV Sucorinvest terbaru.
            </p>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="createMutation.isPending.value">Simpan aset</button>
        </form>
      </div>

      <div v-if="movementMode" class="fixed inset-0 z-40 flex items-end justify-center bg-ink-900/30 sm:items-center" @click.self="movementMode = null">
        <form class="w-full max-w-sm rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl" @submit.prevent="movementMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">{{ movementMode === 'topup' ? 'Tambah nilai' : 'Withdraw' }}</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="movementMode = null"><X :size="20" /></button>
          </div>

          <div class="mb-4 rounded-2xl bg-brand-50 p-3 text-[11px] text-brand-800">
            <p class="font-extrabold">NAV Sucorinvest per {{ movementNav?.date ?? '—' }}</p>
            <p v-if="movementNav" class="mt-0.5">{{ rupiah(movementNav.nav) }} / unit</p>
            <p v-else class="mt-0.5">NAV belum berhasil dimuat.</p>
          </div>

          <div>
            <label class="label" for="movement-amount">Nominal</label>
            <CurrencyInput id="movement-amount" v-model="movementAmount" prefix="Rp" required />
          </div>
          <p class="mt-3 text-[11px] text-ink-500">
            Estimasi: <span class="font-bold text-brand-800">{{ estimatedUnits }}</span> unit akan {{ movementMode === 'topup' ? 'ditambahkan' : 'ditarik' }}.
          </p>

          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="movementMutation.isPending.value || !movementNav">
            {{ movementMutation.isPending.value ? 'Memproses...' : 'Simpan pergerakan' }}
          </button>
        </form>
      </div>

      <div v-if="pendingDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/30 px-4" @click.self="pendingDelete = null">
        <div class="w-full max-w-xs rounded-3xl bg-white p-6 text-center shadow-2xl">
          <span class="mx-auto grid size-12 place-items-center rounded-full bg-accent-50 text-accent-500"><Trash2 :size="22" /></span>
          <h3 class="mt-4 text-lg font-extrabold text-ink-900">Hapus aset?</h3>
          <p class="mt-1 text-xs text-ink-500">Aset akan dinonaktifkan dan tidak dihitung lagi dalam kekayaan.</p>
          <div class="mt-6 grid grid-cols-2 gap-2">
            <button class="btn-secondary" @click="pendingDelete = null">Batal</button>
            <button class="rounded-2xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-accent-400 disabled:opacity-50" :disabled="deleteMutation.isPending.value" @click="confirmDelete">
              {{ deleteMutation.isPending.value ? 'Menghapus...' : 'Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
