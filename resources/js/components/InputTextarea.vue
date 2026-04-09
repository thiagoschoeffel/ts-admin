<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  rows: {
    type: [String, Number],
    default: 4
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
  maxlength: {
    type: [String, Number],
    default: null
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

const textareaClasses = computed(() => {
  const baseClasses = [
    'w-full',
    'border',
    'border-slate-300',
    'rounded-lg',
    'transition-colors',
    'duration-200',
    'min-h-36',
    'resize-y'
  ]

  // Background - white by default, gray-100 when disabled
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
  const classes = [textareaClasses.value]
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
  try { setTimeout(() => event.target?.select?.(), 0) } catch (_) {}
  emit('focus', event)
}

const handleChange = (event) => {
  emit('change', event)
}
</script>

<template>
  <textarea
    :value="modelValue"
    :rows="rows"
    :placeholder="placeholder"
    :required="required"
    :disabled="disabled"
    :readonly="readonly"
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
