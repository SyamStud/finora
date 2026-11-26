<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string
    id?: string
    placeholder?: string
    prefix?: string
    required?: boolean
    disabled?: boolean
  }>(),
  { prefix: '', placeholder: '0' },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const digits = computed(() => String(props.modelValue ?? '').replace(/\D/g, ''))

const display = computed(() =>
  digits.value ? new Intl.NumberFormat('id-ID').format(Number(digits.value)) : '',
)

function onInput(event: Event): void {
  const target = event.target as HTMLInputElement
  const raw = target.value.replace(/\D/g, '')
  emit('update:modelValue', raw)
  target.value = raw ? new Intl.NumberFormat('id-ID').format(Number(raw)) : ''
}
</script>

<template>
  <div class="relative">
    <span v-if="prefix" class="absolute top-1/2 left-3.5 -translate-y-1/2 text-sm font-semibold text-ink-500">
      {{ prefix }}
    </span>
    <input
      :id="id"
      :value="display"
      type="text"
      inputmode="numeric"
      autocomplete="off"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      class="field disabled:opacity-50"
      :class="prefix ? 'pl-10' : ''"
      @input="onInput"
    />
  </div>
</template>
