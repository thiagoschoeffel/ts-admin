<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { formatPriceInput, initializePriceDisplay, parseFormattedValue } from '@/utils/formatters'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  placeholder: {
    type: String,
    default: 'R$ 0,00'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  error: {
    type: Boolean,
    default: false
  },
  success: {
    type: Boolean,
    default: false
  },
  class: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'input', 'blur', 'focus', 'change'])

const inputRef = ref(null)
const displayValue = ref('')
const rawDigits = ref('')

// Classes de tamanho
const sizeClasses = {
  sm: 'px-3 py-1.5 text-xs',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base'
}

// Classes base
const baseClasses = [
  'border',
  'border-slate-300',
  'rounded-lg',
  'transition-colors',
  'duration-200',
  'text-right' // Alinha o texto à direita para valores monetários
]

// Classes finais
const inputClasses = computed(() => {
  const classes = [...baseClasses]

  // Background
  if (props.disabled) {
    classes.push('bg-gray-100')
  } else {
    classes.push('bg-white')
  }

  // Focus styles
  if (!props.disabled) {
    classes.push(
      'focus:outline-none',
      'focus:ring-2',
      'focus:ring-blue-500',
      'focus:border-blue-500'
    )
  }

  // Size
  classes.push(sizeClasses[props.size])

  // Disabled state
  if (props.disabled) {
    classes.push('cursor-not-allowed', 'text-slate-500')
  }

  // State variations
  if (props.error) {
    classes.push('border-red-500', 'focus:border-red-500', 'focus:ring-red-500')
  } else if (props.success) {
    classes.push('border-green-500', 'focus:border-green-500', 'focus:ring-green-500')
  }

  return classes.join(' ')
})

const finalClasses = computed(() => {
  const classes = [inputClasses.value]
  if (props.class) {
    classes.push(props.class)
  }
  return classes.join(' ')
})

// Inicializa o valor de exibição quando o componente é montado
onMounted(() => {
  displayValue.value = initializePriceDisplay(props.modelValue, rawDigits)
})

// Atualiza quando o modelValue muda externamente
watch(() => props.modelValue, (newValue) => {
  if (document.activeElement !== inputRef.value) {
    displayValue.value = initializePriceDisplay(newValue, rawDigits)
  }
})

const handleInput = (event) => {
  const result = formatPriceInput(event, rawDigits)
  displayValue.value = result.formatted

  // Atualiza o valor numérico no v-model
  emit('update:modelValue', result.numeric)
  emit('input', event)
}

const handleBlur = (event) => {
  // Quando perde o foco, garante que o valor seja formatado corretamente
  if (displayValue.value && !displayValue.value.includes('R$')) {
    const numericValue = parseFormattedValue(displayValue.value)
    displayValue.value = initializePriceDisplay(numericValue, rawDigits)
  }
  emit('blur', event)
}

const handleFocus = (event) => {
  // Seleciona todo o conteúdo ao focar para facilitar edição
  try { setTimeout(() => inputRef.value?.select?.(), 0) } catch (_) {}
  emit('focus', event)
}

const handleChange = (event) => {
  emit('change', event)
}

// Expose focus method
defineExpose({
  focus: () => { inputRef.value?.focus?.(); try { inputRef.value?.select?.() } catch (_) {} },
  blur: () => inputRef.value?.blur()
})
</script>

<template>
  <input
    ref="inputRef"
    :value="displayValue"
    :placeholder="placeholder"
    :required="required"
    :disabled="disabled"
    :readonly="readonly"
    :class="finalClasses"
    @input="handleInput"
    @blur="handleBlur"
    @focus="handleFocus"
    @change="handleChange"
  />
</template>

<style scoped>
/* Additional styles can be added here if needed */
</style>
