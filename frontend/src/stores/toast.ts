import { ref } from 'vue'
import { defineStore } from 'pinia'

export interface Toast {
  id: number
  type: 'success' | 'error'
  message: string
}

let nextId = 1

export const useToastStore = defineStore('toast', () => {
  const items = ref<Toast[]>([])

  function show(message: string, type: 'success' | 'error' = 'success'): void {
    const id = nextId++
    items.value.push({ id, type, message })
    window.setTimeout(() => dismiss(id), 4000)
  }

  function success(message: string): void {
    show(message, 'success')
  }

  function error(message: string): void {
    show(message, 'error')
  }

  function dismiss(id: number): void {
    items.value = items.value.filter((item) => item.id !== id)
  }

  return { items, show, success, error, dismiss }
})
