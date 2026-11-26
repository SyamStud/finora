import { apiFetch } from './client'
import type {
  Account,
  Asset,
  AssetMovement,
  AssetValuation,
  AuthResponse,
  Budget,
  Category,
  DashboardData,
  Pocket,
  PocketAllocation,
  PendingTransaction,
  Paginated,
  Transaction,
  Transfer,
  User,
} from '../types'

export type ResourceQuery = Record<string, string | number | boolean | undefined>

/**
 * Laravel API Resource selalu membungkus payload dengan key `data`.
 * Endpoint paginated juga punya `data`, tapi sudah dikonsumsi utuh lewat
 * `Paginated<T>` — jadi helper ini hanya dipakai untuk endpoint non-paginated.
 */
async function resource<T>(
  path: string,
  options: Parameters<typeof apiFetch>[1] = {},
): Promise<T> {
  const response = await apiFetch<T | { data: T }>(path, options)
  if (
    response !== null &&
    typeof response === 'object' &&
    'data' in response &&
    Object.keys(response).length === 1
  ) {
    return (response as { data: T }).data
  }
  return response as T
}

export const authApi = {
  login: (email: string, password: string) =>
    apiFetch<AuthResponse>('/login', { method: 'POST', body: { email, password } }),
  register: (name: string, email: string, password: string, password_confirmation: string) =>
    apiFetch<AuthResponse>('/register', {
      method: 'POST',
      body: { name, email, password, password_confirmation },
    }),
  me: () => apiFetch<{ user: User }>('/me'),
  logout: () => apiFetch<{ message: string }>('/logout', { method: 'POST' }),
}

export const accountsApi = {
  list: (query?: ResourceQuery) => resource<Account[]>('/accounts', { query }),
  show: (id: string) => resource<Account>(`/accounts/${id}`),
  create: (body: Partial<Account>) =>
    resource<Account>('/accounts', { method: 'POST', body }),
  update: (id: string, body: Partial<Account>) =>
    resource<Account>(`/accounts/${id}`, { method: 'PATCH', body }),
  archive: (id: string) =>
    apiFetch<{ message: string }>(`/accounts/${id}`, { method: 'DELETE' }),
}

export const pocketsApi = {
  all: () => resource<Pocket[]>('/pockets'),
  list: (accountId: string) => resource<Pocket[]>(`/accounts/${accountId}/pockets`),
  create: (accountId: string, body: Partial<Pocket>) =>
    resource<Pocket>(`/accounts/${accountId}/pockets`, { method: 'POST', body }),
  update: (id: string, body: Partial<Pocket>) =>
    resource<Pocket>(`/pockets/${id}`, { method: 'PATCH', body }),
  archive: (id: string) => apiFetch<{ message: string }>(`/pockets/${id}`, { method: 'DELETE' }),
  allocations: (accountId: string) =>
    resource<PocketAllocation[]>(`/accounts/${accountId}/allocations`),
  allocate: (body: Record<string, unknown>) =>
    resource<PocketAllocation>('/pockets/allocate', { method: 'POST', body }),
}

export const categoriesApi = {
  list: (query?: ResourceQuery) => resource<Category[]>('/categories', { query }),
  create: (body: Partial<Category>) =>
    resource<Category>('/categories', { method: 'POST', body }),
  update: (id: string, body: Partial<Category>) =>
    resource<Category>(`/categories/${id}`, { method: 'PATCH', body }),
  archive: (id: string) =>
    apiFetch<{ message: string }>(`/categories/${id}`, { method: 'DELETE' }),
}

export const transactionsApi = {
  list: (query?: ResourceQuery) => apiFetch<Paginated<Transaction>>('/transactions', { query }),
  create: (body: Record<string, unknown>) =>
    resource<Transaction>('/transactions', { method: 'POST', body }),
  update: (id: string, body: Record<string, unknown>) =>
    resource<Transaction>(`/transactions/${id}`, { method: 'PATCH', body }),
  remove: (id: string) =>
    apiFetch<{ message: string }>(`/transactions/${id}`, { method: 'DELETE' }),
}

export const transfersApi = {
  list: (query?: ResourceQuery) => apiFetch<Paginated<Transfer>>('/transfers', { query }),
  create: (body: Record<string, unknown>) =>
    resource<Transfer>('/transfers', { method: 'POST', body }),
  remove: (id: string) =>
    apiFetch<{ message: string }>(`/transfers/${id}`, { method: 'DELETE' }),
}

export const assetsApi = {
  list: (query?: ResourceQuery) => resource<Asset[]>('/assets', { query }),
  create: (body: Record<string, unknown>) =>
    resource<Asset>('/assets', { method: 'POST', body }),
  update: (id: string, body: Record<string, unknown>) =>
    resource<Asset>(`/assets/${id}`, { method: 'PATCH', body }),
  disable: (id: string) =>
    apiFetch<{ message: string }>(`/assets/${id}`, { method: 'DELETE' }),
  valuations: (id: string) => resource<AssetValuation[]>(`/assets/${id}/valuations`),
  recordValuation: (id: string, body: Record<string, unknown>) =>
    resource<AssetValuation>(`/assets/${id}/valuations`, { method: 'POST', body }),
  topUp: (id: string, amount: string) =>
    resource<Asset>(`/assets/${id}/top-up`, { method: 'POST', body: { amount } }),
  withdraw: (id: string, amount: string) =>
    resource<Asset>(`/assets/${id}/withdraw`, { method: 'POST', body: { amount } }),
  movements: (id: string) => resource<AssetMovement[]>(`/assets/${id}/movements`),
}

export const budgetsApi = {
  list: () => resource<Budget[]>('/budgets'),
  create: (body: Record<string, unknown>) =>
    resource<Budget>('/budgets', { method: 'POST', body }),
  update: (id: string, body: Record<string, unknown>) =>
    resource<Budget>(`/budgets/${id}`, { method: 'PATCH', body }),
  remove: (id: string) =>
    apiFetch<{ message: string }>(`/budgets/${id}`, { method: 'DELETE' }),
}

export interface FundNav {
  name: string
  code: string
  nav: number
  date: string
  source: string
  cached: boolean
}

export const fundsApi = {
  nav: () => apiFetch<FundNav>('/funds/nav'),
  refreshPrice: (assetId: string) =>
    resource<Asset>(`/assets/${assetId}/refresh-price`, { method: 'POST' }),
}

export const pendingApi = {
  list: () => resource<PendingTransaction[]>('/pending-transactions'),
  update: (id: string, body: Record<string, unknown>) =>
    resource<PendingTransaction>(`/pending-transactions/${id}`, { method: 'PATCH', body }),
  approve: (id: string) =>
    resource<Transaction>(`/pending-transactions/${id}/approve`, { method: 'POST' }),
  reject: (id: string) =>
    apiFetch<{ message: string }>(`/pending-transactions/${id}/reject`, { method: 'POST' }),
}

export const dashboardApi = {
  get: (period = 'month', from?: string, to?: string) =>
    apiFetch<DashboardData>('/dashboard', { query: { period, from, to } }),
}
