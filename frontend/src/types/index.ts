export interface User {
  id: string
  name: string
  email: string
}

export type AccountType =
  | 'cash'
  | 'bank'
  | 'e_wallet'
  | 'personal_wallet'
  | 'savings'
  | 'investment_account'
  | 'other'

export interface Pocket {
  id: string
  account_id: string
  name: string
  account?: { id: string; name: string; color: string | null } | null
  account_balance?: string
  icon: string | null
  color: string | null
  is_active: boolean
  balance?: string
}

export interface PocketAllocation {
  id: string
  source: string
  destination: string
  amount: string
  note: string | null
  allocated_at: string
}

export interface Account {
  id: string
  name: string
  type: string
  currency: string
  initial_balance: string
  balance?: string
  base_balance?: string
  pockets_balance?: string
  pockets?: Pocket[]
  pocket_count?: number
  icon: string | null
  color: string | null
  is_active: boolean
}

export interface Category {
  id: string
  name: string
  type: 'income' | 'expense'
  icon: string | null
  color: string | null
  is_active: boolean
}

export interface Party {
  id: string
  name: string
  color?: string | null
}

export interface Transaction {
  id: string
  type: 'income' | 'expense'
  amount: string
  description: string
  notes: string | null
  transaction_date: string
  input_source: string
  account: Party | null
  pocket: Party | null
  category: Party | null
  transfer_id: string | null
}

export interface PendingTransaction {
  id: string
  source: string
  package_name: string | null
  raw_text: string
  type: 'income' | 'expense'
  amount: string | null
  account: Party | null
  account_name: string | null
  category: Party | null
  category_name: string | null
  description: string | null
  transaction_date: string | null
  confidence: string | null
  status: string
  received_at: string | null
}

export interface Transfer {
  id: string
  amount: string
  fee: string
  description: string | null
  transfer_date: string
  source_account: Party | null
  destination_account: Party | null
}

export interface Asset {
  id: string
  name: string
  type: string
  provider: string | null
  units: string
  average_buy_price: string
  current_price: string
  invested_amount: string
  current_value: string
  profit_loss: string
  return_percentage: number
  currency: string
  notes: string | null
  is_active: boolean
}

export interface AssetValuation {
  id: string
  valuation_date: string
  unit_price: string
  total_value: string
  source: string
}

export interface AssetMovement {
  id: string
  type: 'topup' | 'withdraw'
  amount: string
  units: string
  nav: string
  movement_date: string
  note: string | null
}

export interface BudgetUsage {
  amount: string
  used: string
  remaining: string
  percentage: number
}

export interface Budget {
  id: string
  category: Party | null
  amount: string
  period: 'weekly' | 'monthly'
  start_date: string
  end_date: string | null
  usage: BudgetUsage | null
}

export interface Summary {
  net_worth: string
  total_cash: string
  total_assets: string
  income: string
  expense: string
  net_cash_flow: string
  savings_rate: number
}

export interface CashFlowPoint {
  label: string
  income: number
  expense: number
}

export interface CategorySpend {
  id: string
  name: string
  value: string
}

export interface DashboardData {
  period: { from: string; to: string }
  summary: Summary
  charts: {
    cash_flow: CashFlowPoint[]
    expense_by_category: CategorySpend[]
  }
  accounts: Account[]
  pockets: {
    id: string
    name: string
    color: string | null
    balance: string
    account: { id: string; name: string }
  }[]
  recent_transactions: Transaction[]
}

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  links: { next: string | null; prev: string | null }
}

export interface AuthResponse {
  user: User
  token: string
}
