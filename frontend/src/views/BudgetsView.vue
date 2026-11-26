<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { Plus, Trash2, Wallet, X } from 'lucide-vue-next'
import CurrencyInput from '../components/CurrencyInput.vue'
import { budgetsApi, categoriesApi } from '../api'
import { useToastStore } from '../stores/toast'
import { percent, rupiah, todayISO } from '../utils/format'

const queryClient = useQueryClient()
const toast = useToastStore()
const showCreate = ref(false)
const form = ref({ category_id: '', amount: '', period: 'monthly' as 'monthly' | 'weekly', start_date: todayISO() })

const query = useQuery({ queryKey: ['budgets'], queryFn: () => budgetsApi.list() })
const categoriesQuery = useQuery({
  queryKey: ['categories', 'expense'],
  queryFn: () => categoriesApi.list({ type: 'expense' }),
})
const budgets = computed(() => query.data.value ?? [])

const totalAmount = computed(() => budgets.value.reduce((sum, item) => sum + Number(item.amount), 0))
const totalUsed = computed(() => budgets.value.reduce((sum, item) => sum + Number(item.usage?.used ?? 0), 0))
const totalPercentage = computed(() => totalAmount.value > 0 ? Math.min(100, totalUsed.value / totalAmount.value * 100) : 0)

const ringStyle = computed(() => ({
  background: `conic-gradient(#f48066 0% ${totalPercentage.value}%, #f7e6de ${totalPercentage.value}% 100%)`,
}))

const createMutation = useMutation({
  mutationFn: () => budgetsApi.create({ ...form.value, amount: Number(form.value.amount) }),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    showCreate.value = false
    form.value = { category_id: '', amount: '', period: 'monthly', start_date: todayISO() }
    toast.success('Budget dibuat.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const deleteMutation = useMutation({
  mutationFn: (id: string) => budgetsApi.remove(id),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    toast.success('Budget dihapus.')
  },
  onError: (error: Error) => toast.error(error.message),
})

function barColor(value: number): string {
  if (value >= 100) return 'bg-accent-500'
  if (value >= 80) return 'bg-accent-400'
  return 'bg-brand-600'
}
</script>

<template>
  <div class="space-y-5">
    <section class="app-card">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-extrabold text-ink-900">Budget Management</h2>
          <p class="mt-0.5 text-[11px] text-ink-500">Pantau batas pengeluaran Anda</p>
        </div>
        <button class="btn-primary !px-3 !py-2 !text-xs" @click="showCreate = true"><Plus :size="15" /> Tambah</button>
      </div>

      <div class="mt-5 flex flex-col items-center">
        <div class="relative grid size-44 place-items-center rounded-full" :style="ringStyle">
          <div class="grid size-36 place-items-center rounded-full bg-white shadow-inner">
            <div class="text-center">
              <p class="text-[11px] text-ink-500">Total terpakai</p>
              <p class="text-2xl font-extrabold text-ink-900">{{ rupiah(totalUsed) }}</p>
              <p class="mt-0.5 text-[11px] font-semibold text-brand-700">{{ percent(totalPercentage) }}</p>
            </div>
          </div>
        </div>
        <p class="mt-4 text-xs text-ink-500">dari total budget {{ rupiah(totalAmount) }}</p>
      </div>
    </section>

    <section class="card">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-extrabold text-ink-900">Kategori</h2>
        <span class="text-xs text-ink-500">{{ budgets.length }} budget</span>
      </div>
      <div class="space-y-3">
        <div v-for="budget in budgets" :key="budget.id" class="group flex items-center gap-3">
          <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700">
            <Wallet :size="17" />
          </span>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <p class="truncate text-sm font-bold text-ink-900">{{ budget.category?.name ?? 'Kategori' }}</p>
              <p class="shrink-0 text-xs font-bold text-ink-700">{{ rupiah(budget.usage?.used ?? 0) }}</p>
            </div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
              <div class="h-full rounded-full transition-all" :class="barColor(budget.usage?.percentage ?? 0)" :style="{ width: `${Math.min(100, budget.usage?.percentage ?? 0)}%` }" />
            </div>
            <p class="mt-1 text-[10px] text-ink-500">
              {{ percent(budget.usage?.percentage ?? 0) }} · sisa {{ rupiah(budget.usage?.remaining ?? 0) }}
            </p>
          </div>
          <button class="rounded-lg p-1.5 text-ink-300 opacity-0 transition hover:bg-rose-50 hover:text-rose-500 group-hover:opacity-100" @click="deleteMutation.mutate(budget.id)">
            <Trash2 :size="15" />
          </button>
        </div>
        <p v-if="!budgets.length" class="py-8 text-center text-sm text-ink-500">Belum ada budget. Tambahkan batas pengeluaran pertama Anda.</p>
      </div>
    </section>

    <Teleport to="body">
      <div v-if="showCreate" class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4" @click.self="showCreate = false">
        <form class="w-full max-w-sm rounded-[28px] bg-white p-6 shadow-2xl" @submit.prevent="createMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">Budget baru</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="showCreate = false"><X :size="20" /></button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="label" for="budget-category">Kategori</label>
              <select id="budget-category" v-model="form.category_id" class="field" required>
                <option value="" disabled>Pilih kategori</option>
                <option v-for="category in categoriesQuery.data.value ?? []" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>
            <div>
              <label class="label" for="budget-amount">Batas</label>
              <CurrencyInput id="budget-amount" v-model="form.amount" prefix="Rp" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label" for="budget-period">Periode</label>
                <select id="budget-period" v-model="form.period" class="field">
                  <option value="monthly">Bulanan</option>
                  <option value="weekly">Mingguan</option>
                </select>
              </div>
              <div>
                <label class="label" for="budget-start">Mulai</label>
                <input id="budget-start" v-model="form.start_date" type="date" class="field" required />
              </div>
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="createMutation.isPending.value">Simpan budget</button>
        </form>
      </div>
    </Teleport>
  </div>
</template>
