<script setup lang="ts">
import { computed, ref } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import {
  ArcElement, CategoryScale, Chart as ChartJS, Filler,
  Legend, LineElement, LinearScale, PointElement, Tooltip,
} from 'chart.js'
import { Doughnut, Line } from 'vue-chartjs'
import {
  ArrowDownLeft,
  ArrowRight,
  ArrowUpRight,
  Eye,
  EyeOff,
  Landmark,
  ListFilter,
  MoreHorizontal,
  Plus,
  Wallet,
} from 'lucide-vue-next'
import { dashboardApi } from '../api'
import { compactRupiah, monthLabel, rupiah, shortDate } from '../utils/format'
import type { CashFlowPoint, CategorySpend } from '../types'

ChartJS.register(
  ArcElement, CategoryScale, Filler, Legend,
  LineElement, LinearScale, PointElement, Tooltip,
)

const period = ref('year')
const showAmounts = ref(true)

const dashboardQuery = useQuery({
  queryKey: computed(() => ['dashboard', period.value]),
  queryFn: () => dashboardApi.get(period.value),
})

const data = computed(() => dashboardQuery.data.value)
const summary = computed(() => data.value?.summary)

function money(value: string | number): string {
  return showAmounts.value ? rupiah(value) : 'Rp ••••••••'
}

function smallMoney(value: string | number): string {
  return showAmounts.value ? compactRupiah(value) : 'Rp ••••'
}

const flowData = computed(() => {
  const points: CashFlowPoint[] = data.value?.charts.cash_flow ?? []
  const labels = points.map((point) => monthLabel(point.label.slice(0, 7)))
  return {
    labels,
    datasets: [
      {
        label: 'Pemasukan',
        data: points.map((p) => p.income),
        borderColor: '#2e6d66',
        backgroundColor: 'rgba(46, 109, 102, 0.10)',
        borderWidth: 2.5,
        pointRadius: 0,
        pointHoverRadius: 5,
        tension: 0.45,
        fill: true,
      },
      {
        label: 'Pengeluaran',
        data: points.map((p) => p.expense),
        borderColor: '#f48066',
        backgroundColor: 'rgba(244, 128, 102, 0.08)',
        borderWidth: 2.5,
        pointRadius: 0,
        pointHoverRadius: 5,
        tension: 0.45,
        fill: true,
      },
    ],
  }
})

const spendData = computed(() => {
  const items: CategorySpend[] = data.value?.charts.expense_by_category ?? []
  return {
    labels: items.map((item) => item.name),
    datasets: [{
      data: items.map((item) => Number(item.value)),
      backgroundColor: ['#1b4b4a', '#f48066', '#3f887f', '#ffc2ae', '#5fa49a', '#e8694f'],
      borderWidth: 0,
    }],
  }
})

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { intersect: false, mode: 'index' as const },
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#8a9a98', font: { size: 10 } } },
    y: {
      beginAtZero: true,
      grid: { color: '#f3ece6' },
      ticks: { color: '#a6b3b1', font: { size: 10 }, callback: (value: string | number) => compactRupiah(value) },
    },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '70%',
  plugins: {
    legend: { position: 'bottom' as const, labels: { boxWidth: 9, font: { size: 10 }, color: '#3b4d4c' } },
  },
}

function openAdd(): void {
  window.dispatchEvent(new Event('finora:quick-add'))
}

const periods = [
  { value: 'today', label: 'Hari ini' },
  { value: 'week', label: 'Minggu ini' },
  { value: 'month', label: 'Bulan ini' },
  { value: 'year', label: 'Yearly' },
]
</script>

<template>
  <div class="space-y-3">
    <div v-if="dashboardQuery.isPending.value" class="space-y-5">
      <div class="hero-card h-32 animate-pulse" />
      <div class="grid grid-cols-2 gap-3"><div class="card h-24 animate-pulse" /><div class="card h-24 animate-pulse" /></div>
    </div>

    <div v-else-if="dashboardQuery.isError.value" class="card text-sm text-accent-500">
      Gagal memuat data. Periksa koneksi lalu muat ulang halaman.
    </div>

    <template v-else-if="summary">
      <!-- Total net worth -->
      <section class="hero-card">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-[11px] font-semibold tracking-wide text-brand-100 uppercase">Total kekayaan</p>
            <p class="mt-1.5 text-2xl font-extrabold tracking-tight">{{ money(summary.net_worth) }}</p>
          </div>
          <button class="grid size-9 shrink-0 place-items-center rounded-full bg-white/15" @click="showAmounts = !showAmounts">
            <EyeOff v-if="showAmounts" :size="16" />
            <Eye v-else :size="16" />
          </button>
        </div>
      </section>

      <!-- Statistik -->
      <section class="grid grid-cols-2 gap-3">
        <div class="app-card !p-4">
          <div class="flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-full bg-accent-50 text-accent-500">
              <ArrowUpRight :size="17" />
            </span>
            <span class="text-[11px] font-semibold text-ink-500">Pemasukan</span>
          </div>
          <p class="mt-3 text-lg font-extrabold text-ink-900">{{ smallMoney(summary.income) }}</p>
          <p class="mt-0.5 text-[10px] text-ink-500">Total diterima</p>
        </div>
        <div class="app-card !p-4">
          <div class="flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-full bg-brand-50 text-brand-700">
              <ArrowDownLeft :size="17" />
            </span>
            <span class="text-[11px] font-semibold text-ink-500">Pengeluaran</span>
          </div>
          <p class="mt-3 text-lg font-extrabold text-ink-900">{{ smallMoney(summary.expense) }}</p>
          <p class="mt-0.5 text-[10px] text-ink-500">Total dikeluarkan</p>
        </div>
      </section>

      <!-- Income & Expenses -->
      <section class="app-card">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-sm font-extrabold text-ink-900">Income &amp; Expenses</h2>
          <select v-model="period" class="rounded-xl border border-[#ece4dd] bg-white px-3 py-1.5 text-[11px] font-semibold text-ink-700 outline-none">
            <option v-for="item in periods" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
        </div>
        <div class="mt-4 h-52">
          <Line v-if="data!.charts.cash_flow.length" :data="flowData" :options="lineOptions" />
          <div v-else class="grid h-full place-items-center text-sm text-ink-500">Belum ada transaksi.</div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <button class="btn-primary !px-3 !py-2 !text-xs" @click="openAdd"><Plus :size="14" /> Add</button>
          <RouterLink to="/transactions" class="btn-secondary !px-3 !py-2 !text-xs"><ListFilter :size="14" /> Filter</RouterLink>
          <RouterLink to="/transactions" class="btn-secondary !px-3 !py-2 !text-xs"><MoreHorizontal :size="14" /> More</RouterLink>
        </div>
      </section>

      <section class="card">
        <h2 class="mb-4 text-sm font-extrabold text-ink-900">Pengeluaran per kategori</h2>
        <div class="h-48">
          <Doughnut v-if="data!.charts.expense_by_category.length" :data="spendData" :options="doughnutOptions" />
          <div v-else class="grid h-full place-items-center text-sm text-ink-500">Belum ada pengeluaran.</div>
        </div>
      </section>

      <!-- Pocket -->
      <section class="card">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-extrabold text-ink-900">Pocket</h2>
          <RouterLink to="/accounts" class="flex items-center gap-1 text-xs font-semibold text-brand-700">Lihat semua <ArrowRight :size="13" /></RouterLink>
        </div>
        <div class="space-y-2.5">
          <RouterLink
            v-for="pocket in data!.pockets"
            :key="pocket.id"
            :to="{ name: 'account-detail', params: { id: pocket.account.id } }"
            class="flex items-center gap-3 rounded-2xl bg-[#fdf9f5] px-3 py-2.5"
          >
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white text-brand-700" :style="{ color: pocket.color ?? '#1b4b4a' }">
              <Wallet :size="17" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-bold text-ink-900">{{ pocket.name }}</p>
              <p class="text-[11px] text-ink-500">{{ pocket.account.name }}</p>
            </div>
            <p class="text-sm font-extrabold text-ink-900">{{ smallMoney(pocket.balance) }}</p>
          </RouterLink>
          <p v-if="!data!.pockets.length" class="py-6 text-center text-sm text-ink-500">Belum ada pocket.</p>
        </div>
      </section>

      <!-- Rekening -->
      <section class="card">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-extrabold text-ink-900">Rekening</h2>
          <RouterLink to="/accounts" class="flex items-center gap-1 text-xs font-semibold text-brand-700">Lihat semua <ArrowRight :size="13" /></RouterLink>
        </div>
        <div class="space-y-2.5">
          <RouterLink
            v-for="account in data!.accounts"
            :key="account.id"
            :to="{ name: 'account-detail', params: { id: account.id } }"
            class="flex items-center gap-3 rounded-2xl bg-[#fdf9f5] px-3 py-2.5"
          >
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white text-brand-800" :style="{ color: account.color ?? '#1b4b4a' }">
              <Landmark :size="17" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-bold text-ink-900">{{ account.name }}</p>
              <p class="text-[11px] text-ink-500">{{ account.type.replace('_', ' ') }}</p>
            </div>
            <p class="text-sm font-extrabold text-ink-900">{{ smallMoney(account.balance ?? 0) }}</p>
          </RouterLink>
          <p v-if="!data!.accounts.length" class="py-6 text-center text-sm text-ink-500">Belum ada rekening.</p>
        </div>
      </section>

      <!-- Recent Transaction -->
      <section class="card">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-extrabold text-ink-900">Recent Transaction</h2>
          <RouterLink to="/transactions" class="text-xs font-semibold text-brand-700">See all</RouterLink>
        </div>
        <div class="space-y-3">
          <div v-for="item in data!.recent_transactions" :key="item.id" class="flex items-center gap-2.5">
            <span
              class="grid size-9 shrink-0 place-items-center rounded-full"
              :class="item.type === 'income' ? 'bg-brand-50 text-brand-700' : 'bg-accent-50 text-accent-500'"
            >
              <ArrowDownLeft v-if="item.type === 'income'" :size="17" />
              <ArrowUpRight v-else :size="17" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-bold text-ink-900">{{ item.description }}</p>
              <p class="truncate text-[11px] text-ink-500">{{ shortDate(item.transaction_date) }}</p>
            </div>
            <p class="text-sm font-extrabold" :class="item.type === 'income' ? 'amount-positive' : 'amount-negative'">
              {{ item.type === 'income' ? '+' : '' }}{{ smallMoney(item.amount) }}
            </p>
          </div>
          <p v-if="!data!.recent_transactions.length" class="py-6 text-center text-sm text-ink-500">Belum ada transaksi.</p>
        </div>
      </section>
    </template>
  </div>
</template>
