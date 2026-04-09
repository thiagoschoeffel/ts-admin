<script setup>
import { computed, ref } from 'vue'
import HeroIcon from '@/components/icons/HeroIcon.vue'

const emit = defineEmits(['update:modelValue', 'change', 'blur', 'focus'])

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  placeholder: {
    type: String,
    default: 'Selecione...'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
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
  // Option properties
  optionValue: {
    type: String,
    default: 'value'
  },
  optionLabel: {
    type: String,
    default: 'label'
  },
  class: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: ''
  },
  tabindex: {
    type: [String, Number],
    default: undefined
  }
})

const selectRef = ref(null)

const selectClasses = computed(() => {
  const baseClasses = [
    'w-full',
    'border',
    'border-slate-300',
    'rounded-lg',
    'transition-colors',
    'duration-200',
    'appearance-none',
    'pr-10'
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
  const classes = [selectClasses.value]
  if (props.class) {
    classes.push(props.class)
  }
  return classes.join(' ')
})

const handleChange = (event) => {
  const value = event.target.value
  // Convert to appropriate type if needed
  let finalValue = value
  if (value === '') {
    finalValue = null
  } else if (typeof props.modelValue === 'number') {
    finalValue = Number(value)
  } else if (typeof props.modelValue === 'boolean') {
    finalValue = value === 'true'
  }

  emit('update:modelValue', finalValue)
  emit('change', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleFocus = (event) => {
  try {
    // Select current option text by focusing the select element.
    // Native select doesn't support text range selection, but focusing is enough for consistency.
    setTimeout(() => selectRef.value?.focus?.(), 0)
  } catch (_) {}
  emit('focus', event)
}

const getOptionValue = (option) => {
  if (typeof option === 'string' || typeof option === 'number') {
    return option
  }
  return option[props.optionValue]
}

const getOptionLabel = (option) => {
  if (typeof option === 'string' || typeof option === 'number') {
    return option
  }
  return option[props.optionLabel]
}

// Expose focus method
defineExpose({
  focus: () => { selectRef.value?.focus?.() },
  blur: () => selectRef.value?.blur()
})
</script>

<template>
  <div class="relative w-full">
    <select
      ref="selectRef"
      :id="id"
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      :tabindex="tabindex"
      :class="finalClasses"
      @change="handleChange"
      @blur="handleBlur"
      @focus="handleFocus"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="getOptionValue(option)"
        :value="getOptionValue(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>
    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none">
      <HeroIcon name="chevron-down" class="h-4 w-4 text-slate-400" />
    </div>
  </div>
</template>

<style scoped>
/* Additional styles can be added here if needed */
</style>
