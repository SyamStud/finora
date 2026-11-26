export function rupiah(value: string | number | null | undefined): string {
  const number = Number(value ?? 0)
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number.isFinite(number) ? number : 0)
}

export function compactRupiah(value: string | number | null | undefined): string {
  const number = Number(value ?? 0)
  if (Math.abs(number) >= 1_000_000_000) {
    return `Rp${(number / 1_000_000_000).toFixed(1).replace('.0', '')} M`
  }
  if (Math.abs(number) >= 1_000_000) {
    return `Rp${(number / 1_000_000).toFixed(1).replace('.0', '')} jt`
  }
  if (Math.abs(number) >= 1_000) {
    return `Rp${(number / 1_000).toFixed(0)} rb`
  }
  return rupiah(number)
}

export function shortDate(value: string | null | undefined): string {
  if (!value) return '—'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(value))
}

export function dateLabel(value: string | null | undefined): string {
  if (!value) return '—'
  const date = new Date(value)
  const today = new Date()
  const sameDay = date.toDateString() === today.toDateString()
  if (sameDay) return 'Hari ini'
  return shortDate(value)
}

export function percent(value: number): string {
  return `${Number(value || 0).toFixed(1)}%`
}

export function todayISO(): string {
  const now = new Date()
  return new Date(now.getTime() - now.getTimezoneOffset() * 60_000).toISOString().slice(0, 10)
}

export function monthLabel(value: string): string {
  const month = value.slice(0, 7)
  return new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(new Date(`${month}-01`))
}
