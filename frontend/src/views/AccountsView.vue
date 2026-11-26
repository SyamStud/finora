<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { MoreHorizontal, Plus, Wallet, X } from 'lucide-vue-next'
import MastercardMark from '../components/MastercardMark.vue'
import CardChipMark from '../components/CardChipMark.vue'
import CurrencyInput from '../components/CurrencyInput.vue'
import { accountsApi, pocketsApi } from '../api'
import { useToastStore } from '../stores/toast'
import { rupiah } from '../utils/format'
import type { Account, AccountType } from '../types'

const router = useRouter()
const queryClient = useQueryClient()
const toast = useToastStore()
const showCreate = ref(false)
const editingAccount = ref<Account | null>(null)
const accountEditForm = ref({ name: '', type: 'bank' as AccountType, currency: 'IDR', color: '#1b4b4a' })

const activeTab = ref<'card' | 'pocket'>('card')
const accountsQuery = useQuery({ queryKey: ['accounts'], queryFn: () => accountsApi.list() })
const accounts = computed(() => accountsQuery.data.value ?? [])

const pocketsQuery = useQuery({
  queryKey: ['pockets'],
  queryFn: () => pocketsApi.all(),
  enabled: computed(() => activeTab.value === 'pocket'),
})
const pockets = computed(() => pocketsQuery.data.value ?? [])

const form = ref({
  name: '',
  type: 'bank' as AccountType,
  currency: 'IDR',
  initial_balance: '0',
  color: '#1b4b4a',
  icon: 'wallet',
})

const types = [
  { value: 'cash', label: 'Tunai' },
  { value: 'bank', label: 'Bank' },
  { value: 'e_wallet', label: 'E-Wallet' },
  { value: 'personal_wallet', label: 'Dompet Pribadi' },
  { value: 'savings', label: 'Tabungan' },
  { value: 'investment_account', label: 'Akun Investasi' },
  { value: 'other', label: 'Lainnya' },
]

const colors = ['#1b4b4a', '#2e6d66', '#e8694f', '#f48066', '#3b4d4c', '#8fc2b8', '#ffc2ae']

const accountUpdateMutation = useMutation({
  mutationFn: () => accountsApi.update(editingAccount.value!.id, accountEditForm.value),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    await queryClient.invalidateQueries({ queryKey: ['pockets'] })
    editingAccount.value = null
    toast.success('Rekening diperbarui.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const accountArchiveMutation = useMutation({
  mutationFn: (id: string) => accountsApi.archive(id),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    await queryClient.invalidateQueries({ queryKey: ['pockets'] })
    editingAccount.value = null
    toast.success('Rekening diarsipkan.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const createMutation = useMutation({
  mutationFn: () => accountsApi.create(form.value),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    await queryClient.invalidateQueries({ queryKey: ['pockets'] })
    showCreate.value = false
    form.value = { name: '', type: 'bank', currency: 'IDR', initial_balance: '0', color: '#1b4b4a', icon: 'wallet' }
    toast.success('Rekening berhasil dibuat.')
  },
  onError: (error: Error) => toast.error(error.message),
})

const totalBalance = computed(() =>
  accounts.value.reduce((sum, item) => sum + Number(item.balance ?? 0), 0),
)

function openAccount(id: string): void {
  void router.push({ name: 'account-detail', params: { id } })
}

function lastFour(id: string): string {
  return id.replace(/-/g, '').slice(-4).toUpperCase()
}

function openAccountEdit(account: Account): void {
  accountEditForm.value = {
    name: account.name,
    type: account.type as AccountType,
    currency: account.currency,
    color: account.color ?? '#1b4b4a',
  }
  editingAccount.value = account
}
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-end justify-between">
      <div>
        <p class="text-[11px] font-semibold text-ink-500">Total saldo</p>
        <p class="text-2xl font-extrabold text-ink-900">{{ rupiah(totalBalance) }}</p>
      </div>
      <button class="btn-primary !px-3 !py-2 !text-xs" @click="showCreate = true">
        <Plus :size="15" /> Rekening baru
      </button>
    </div>

    <div class="grid grid-cols-2 gap-1 rounded-2xl bg-white p-1 shadow-sm">
      <button
        v-for="tab in [
          { key: 'card' as const, label: 'Card' },
          { key: 'pocket' as const, label: 'Pocket' },
        ]"
        :key="tab.key"
        class="rounded-xl py-2 text-xs font-extrabold transition"
        :class="activeTab === tab.key ? 'bg-brand-800 text-white' : 'text-ink-500'"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <template v-if="activeTab === 'card'">
    <h2 class="text-sm font-extrabold text-ink-900">Kartu saya</h2>

    <div v-if="accountsQuery.isPending.value" class="h-44 animate-pulse rounded-3xl bg-white/70" />
    <div v-else-if="accountsQuery.isError.value" class="card text-sm text-accent-500">Gagal memuat rekening.</div>

    <div v-else class="grid gap-3 md:grid-cols-2">
      <article
        v-for="account in accounts"
        :key="account.id"
        class="bank-card cursor-pointer"
        :style="{ backgroundColor: account.color ?? '#1b4b4a' }"
        @click="openAccount(account.id)"
      >
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="text-[9px] font-semibold tracking-widest text-white/60 uppercase">Nama rekening</p>
            <p class="mt-0.5 text-sm font-extrabold">{{ account.name }}</p>
          </div>
          <MastercardMark />
        </div>

        <div class="mt-3">
          <p class="text-[9px] font-semibold tracking-widest text-white/60 uppercase">Saldo</p>
          <p class="mt-0.5 text-xl font-extrabold tracking-tight">{{ rupiah(account.balance) }}</p>
        </div>

        <div class="mt-auto flex items-end justify-between gap-3">
          <p class="font-mono text-[13px] font-semibold tracking-[0.18em]">
            •••• •••• •••• {{ lastFour(account.id) }}
          </p>
          <div class="flex shrink-0 items-center gap-1">
            <CardChipMark />
            <button class="rounded-full p-1.5 transition hover:bg-white/15" aria-label="Edit rekening" @click.stop="openAccountEdit(account)">
              <MoreHorizontal :size="18" />
            </button>
          </div>
        </div>
      </article>

      <p v-if="!accounts.length" class="card py-12 text-center text-sm text-ink-500 md:col-span-2">
        Belum ada rekening. Buat rekening pertama Anda.
      </p>
    </div>
    </template>

    <template v-else>
      <h2 class="text-sm font-extrabold text-ink-900">Pocket saya</h2>
      <div v-if="pocketsQuery.isPending.value" class="app-card h-40 animate-pulse" />
      <div v-else class="space-y-2.5">
        <button
          v-for="pocket in pockets"
          :key="pocket.id"
          class="app-card flex w-full items-center gap-3 text-left transition hover:-translate-y-0.5"
          @click="openAccount(pocket.account_id)"
        >
          <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700">
            <Wallet :size="17" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-ink-900">{{ pocket.name }}</p>
            <p class="text-[11px] text-ink-500">di {{ pocket.account?.name ?? 'Rekening' }}</p>
          </div>
          <p class="text-sm font-extrabold text-ink-900">{{ rupiah(pocket.balance) }}</p>
        </button>
        <p v-if="!pockets.length" class="app-card py-10 text-center text-sm text-ink-500">
          Belum ada pocket. Buat pocket dari halaman detail rekening.
        </p>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="showCreate" class="fixed inset-0 z-40 flex items-end justify-center bg-ink-900/30 sm:items-center" @click.self="showCreate = false">
        <form class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:max-w-md sm:rounded-3xl" @submit.prevent="createMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">Rekening baru</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="showCreate = false"><X :size="20" /></button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="label" for="account-name">Nama rekening</label>
              <input id="account-name" v-model="form.name" class="field" placeholder="BCA, GoPay, Cash..." required />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label" for="account-type">Tipe</label>
                <select id="account-type" v-model="form.type" class="field">
                  <option v-for="item in types" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
              </div>
              <div>
                <label class="label" for="account-currency">Mata uang</label>
                <input id="account-currency" v-model="form.currency" class="field" maxlength="3" required />
              </div>
            </div>
            <div>
              <label class="label" for="account-balance">Saldo awal</label>
              <CurrencyInput id="account-balance" v-model="form.initial_balance" prefix="Rp" required />
            </div>
            <div>
              <label class="label">Warna kartu</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="color in colors"
                  :key="color"
                  type="button"
                  class="size-8 rounded-full ring-2 transition"
                  :class="form.color === color ? 'ring-ink-900 ring-offset-2' : 'ring-transparent'"
                  :style="{ background: color }"
                  @click="form.color = color"
                />
              </div>
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="createMutation.isPending.value">Buat rekening</button>
        </form>
      </div>

      <div v-if="editingAccount" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/30 px-4" @click.self="editingAccount = null">
        <form class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl" @submit.prevent="accountUpdateMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">Edit rekening</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="editingAccount = null"><X :size="20" /></button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="label" for="edit-account-name">Nama rekening</label>
              <input id="edit-account-name" v-model="accountEditForm.name" class="field" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label" for="edit-account-type">Tipe</label>
                <select id="edit-account-type" v-model="accountEditForm.type" class="field">
                  <option v-for="item in types" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
              </div>
              <div>
                <label class="label" for="edit-account-currency">Mata uang</label>
                <input id="edit-account-currency" v-model="accountEditForm.currency" class="field" maxlength="3" required />
              </div>
            </div>
            <div>
              <label class="label">Warna kartu</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="color in colors"
                  :key="color"
                  type="button"
                  class="size-8 rounded-full ring-2 transition"
                  :class="accountEditForm.color === color ? 'ring-ink-900 ring-offset-2' : 'ring-transparent'"
                  :style="{ background: color }"
                  @click="accountEditForm.color = color"
                />
              </div>
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="accountUpdateMutation.isPending.value">Simpan perubahan</button>
          <button type="button" class="mt-3 w-full py-2 text-xs font-semibold text-accent-500 hover:underline" :disabled="accountArchiveMutation.isPending.value" @click="accountArchiveMutation.mutate(editingAccount.id)">Arsipkan rekening</button>
        </form>
      </div>
    </Teleport>
  </div>
</template>
