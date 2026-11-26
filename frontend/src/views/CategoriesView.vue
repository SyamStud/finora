<script setup lang="ts">
import { computed, ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowDownLeft, ArrowUpRight, Plus, X } from 'lucide-vue-next'
import { categoriesApi } from '../api'
import { useToastStore } from '../stores/toast'

const queryClient = useQueryClient()
const toast = useToastStore()
const showCreate = ref(false)
const form = ref({ name: '', type: 'expense' as 'income' | 'expense', color: '#1b4b4a' })

const query = useQuery({ queryKey: ['categories'], queryFn: () => categoriesApi.list() })
const categories = computed(() => query.data.value ?? [])
const expenseCategories = computed(() => categories.value.filter((item) => item.type === 'expense'))
const incomeCategories = computed(() => categories.value.filter((item) => item.type === 'income'))

const createMutation = useMutation({
  mutationFn: () => categoriesApi.create(form.value),
  onSuccess: async () => {
    await queryClient.invalidateQueries()
    showCreate.value = false
    form.value = { name: '', type: 'expense', color: '#1b4b4a' }
    toast.success('Kategori ditambahkan.')
  },
  onError: (error: Error) => toast.error(error.message),
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs text-ink-500">{{ categories.length }} kategori tersedia</p>
        <p class="text-sm font-extrabold text-ink-900">Atur pengelompokan transaksi</p>
      </div>
      <button class="btn-primary" @click="showCreate = true"><Plus :size="16" /> Kategori baru</button>
    </div>

    <section class="app-card">
      <div class="mb-4 flex items-center gap-2">
        <span class="grid size-8 place-items-center rounded-xl bg-rose-50 text-rose-500"><ArrowUpRight :size="16" /></span>
        <h2 class="text-sm font-extrabold text-ink-900">Pengeluaran</h2>
      </div>
      <div class="flex flex-wrap gap-2">
        <span v-for="category in expenseCategories" :key="category.id" class="chip bg-slate-50 text-ink-700">
          <span class="size-2.5 rounded-full" :style="{ background: category.color ?? '#f43f5e' }" />
          {{ category.name }}
        </span>
        <p v-if="!expenseCategories.length" class="text-xs text-ink-500">Belum ada kategori pengeluaran.</p>
      </div>
    </section>

    <section class="app-card">
      <div class="mb-4 flex items-center gap-2">
        <span class="grid size-8 place-items-center rounded-xl bg-brand-50 text-brand-700"><ArrowDownLeft :size="16" /></span>
        <h2 class="text-sm font-extrabold text-ink-900">Pemasukan</h2>
      </div>
      <div class="flex flex-wrap gap-2">
        <span v-for="category in incomeCategories" :key="category.id" class="chip bg-slate-50 text-ink-700">
          <span class="size-2.5 rounded-full" :style="{ background: category.color ?? '#1b4b4a' }" />
          {{ category.name }}
        </span>
        <p v-if="!incomeCategories.length" class="text-xs text-ink-500">Belum ada kategori pemasukan.</p>
      </div>
    </section>

    <Teleport to="body">
      <div v-if="showCreate" class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4" @click.self="showCreate = false">
        <form class="w-full max-w-sm rounded-[28px] bg-white p-6 shadow-2xl" @submit.prevent="createMutation.mutate()">
          <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-extrabold">Kategori baru</h2>
            <button type="button" class="rounded-lg p-1.5 text-ink-500 hover:bg-slate-100" @click="showCreate = false"><X :size="20" /></button>
          </div>
          <div class="space-y-4">
            <div>
              <label class="label" for="category-name">Nama</label>
              <input id="category-name" v-model="form.name" class="field" placeholder="Contoh: Hewan" required />
            </div>
            <div>
              <label class="label" for="category-type">Tipe</label>
              <select id="category-type" v-model="form.type" class="field">
                <option value="expense">Pengeluaran</option>
                <option value="income">Pemasukan</option>
              </select>
            </div>
            <div>
              <label class="label" for="category-color">Warna</label>
              <input id="category-color" v-model="form.color" type="color" class="h-10 w-full cursor-pointer rounded-2xl border border-slate-200" />
            </div>
          </div>
          <button type="submit" class="btn-primary mt-6 w-full py-3" :disabled="createMutation.isPending.value">Simpan kategori</button>
        </form>
      </div>
    </Teleport>
  </div>
</template>
