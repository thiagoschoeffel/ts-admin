<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  placeholder: {
    type: String,
    default: ''
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
  autocomplete: {
    type: String,
    default: ''
  },
  inputmode: {
    type: String,
    default: ''
  },
  maxlength: {
    type: [String, Number],
    default: ''
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

const inputClasses = computed(() => {
  const baseClasses = [
    'border',
    'border-slate-300',
    'rounded-lg',
    'transition-colors',
    'duration-200'
  ]

  // Background - white by default, gray-200 when disabled
  if (props.disabled) {
    baseClasses.push('bg-gray-100')
  } else {
    baseClasses.push('bg-white')
  }

  // Add focus styles only if not disabled
  if (!props.disabled) {
    baseClasses.push(
      'focus:outline-none',
      'focus:ring-2',
      'focus:ring-blue-500',
      'focus:border-blue-500'
    )
  }

  // Size variations - matching Button component sizes
  if (props.size === 'lg') {
    baseClasses.push('px-6', 'py-3', 'text-base')
  } else if (props.size === 'sm') {
    baseClasses.push('px-3', 'py-1.5', 'text-xs')
  } else {
    // md is default
    baseClasses.push('px-4', 'py-2', 'text-sm')
  }

  // Disabled state
  if (props.disabled) {
    baseClasses.push('cursor-not-allowed', 'text-slate-500')
  }

  // State variations
  if (props.error) {
    baseClasses.push('border-red-500', 'focus:border-red-500', 'focus:ring-red-500')
  } else if (props.success) {
    baseClasses.push('border-green-500', 'focus:border-green-500', 'focus:ring-green-500')
  }

  return baseClasses.join(' ')
})

const finalClasses = computed(() => {
  const classes = [inputClasses.value]
  if (props.class) {
    classes.push(props.class)
  }
  return classes.join(' ')
})

const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
  emit('input', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleFocus = (event) => {
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
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :required="required"
    :disabled="disabled"
    :readonly="readonly"
    :autocomplete="autocomplete"
    :inputmode="inputmode"
    :maxlength="maxlength"
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
