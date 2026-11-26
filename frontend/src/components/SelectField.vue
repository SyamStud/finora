<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'

defineProps<{
  modelValue: string
  label?: string
  id?: string
  placeholder?: string
  required?: boolean
  options: { value: string; label: string }[]
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

function onChange(event: Event): void {
  emit('update:modelValue', (event.target as HTMLSelectElement).value)
}
</script>

<template>
  <div>
    <label v-if="label" class="label" :for="id">{{ label }}</label>
    <div class="relative">
      <select
        :id="id"
        :value="modelValue"
        :required="required"
        class="field appearance-none pr-9"
        @change="onChange"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
      <ChevronDown
        :size="16"
        class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-ink-500"
      />
    </div>
  </div>
</template>
