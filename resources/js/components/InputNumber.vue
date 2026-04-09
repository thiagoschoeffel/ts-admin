<script setup>
import { ref, computed, onMounted, watch } from 'vue'

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
    min: {
        type: [String, Number],
        default: null
    },
    max: {
        type: [String, Number],
        default: null
    },
    step: {
        type: [String, Number],
        default: null
    },
    precision: {
        type: Number,
        default: 2
    },
    formatted: {
        type: Boolean,
        default: false
    },
    allowNegative: {
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

const emit = defineEmits(['update:modelValue', 'input', 'blur', 'focus', 'change', 'commit', 'enter'])

const inputRef = ref(null)
const displayValue = ref('')
const rawDigits = ref('')

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

    // Alinhamento para valores formatados
    if (props.formatted) {
        baseClasses.push('text-right')
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

// Inicializa o valor quando o componente é montado
onMounted(() => {
    if (props.formatted) {
        const numericValue = Number(props.modelValue)
        if (isNaN(numericValue)) {
            displayValue.value = ''
        } else if (props.precision === 0) {
            displayValue.value = numericValue.toString()
        } else {
            displayValue.value = numericValue.toLocaleString('pt-BR', { minimumFractionDigits: props.precision, maximumFractionDigits: props.precision })
        }
    } else {
        displayValue.value = props.modelValue
    }
})

// Atualiza quando o modelValue muda externamente
watch(() => props.modelValue, (newValue) => {
    // Always reflect external modelValue changes in the display,
    // even when focused (needed for arrow key adjustments triggered by parent)
    if (props.formatted) {
        const numericValue = Number(newValue)
        if (isNaN(numericValue)) {
            displayValue.value = ''
        } else if (props.precision === 0) {
            displayValue.value = numericValue.toString()
        } else {
            displayValue.value = numericValue.toLocaleString('pt-BR', { minimumFractionDigits: props.precision, maximumFractionDigits: props.precision })
        }
    } else {
        displayValue.value = newValue
    }
})

const handleInput = (event) => {
    if (props.formatted) {
        let value = event.target.value

        // Allow minus sign at the beginning if allowNegative is true
        if (props.allowNegative && value.startsWith('-')) {
            value = '-' + value.slice(1).replace(/\D/g, "")
        } else {
            value = value.replace(/\D/g, "")
        }

        if (value === '' || (props.allowNegative && value === '-')) {
            displayValue.value = value
            rawDigits.value = ''
            emit('update:modelValue', value === '-' ? '' : value)
            emit('input', event)
            return
        }

        value = value.slice(0, 9) // limit digits (1 extra for minus sign)

        const isNegative = props.allowNegative && value.startsWith('-')
        const digitsOnly = isNegative ? value.slice(1) : value

        const numericValue = parseFloat(digitsOnly) / Math.pow(10, props.precision)
        const finalValue = isNegative ? -numericValue : numericValue

        if (props.precision === 0) {
            displayValue.value = finalValue.toString()
        } else {
            displayValue.value = finalValue.toLocaleString('pt-BR', { minimumFractionDigits: props.precision, maximumFractionDigits: props.precision })
        }

        rawDigits.value = digitsOnly

        emit('update:modelValue', Number(finalValue.toFixed(props.precision)))
    } else {
        // Comportamento normal do input number
        let value = event.target.value

        // Allow empty values
        if (value === '') {
            emit('update:modelValue', '')
            emit('input', event)
            return
        }

        // Convert to number and apply precision if needed
        const numericValue = parseFloat(value)
        if (!isNaN(numericValue)) {
            const roundedValue = Number(numericValue.toFixed(props.precision))
            emit('update:modelValue', roundedValue)
        } else {
            emit('update:modelValue', value)
        }
    }

    emit('input', event)
}

const handleBlur = (event) => {
    if (props.formatted) {
        // Garante formatação correta ao perder foco
        if (displayValue.value && !isNaN(parseFloat(displayValue.value.replace(/\./g, '').replace(',', '.')))) {
            const numericValue = parseFloat(displayValue.value.replace(/\./g, '').replace(',', '.'))
            if (props.precision === 0) {
                displayValue.value = numericValue.toString()
            } else {
                displayValue.value = numericValue.toLocaleString('pt-BR', { minimumFractionDigits: props.precision, maximumFractionDigits: props.precision })
            }
            // Emit commit with the numeric value rounded to precision
            emit('commit', Number(numericValue.toFixed(props.precision)))
        } else if (displayValue.value === '-' && props.allowNegative) {
            // Handle case where only minus sign was entered
            displayValue.value = ''
            emit('commit', '')
        }
    } else {
        // Ensure proper formatting on blur
        if (event.target.value !== '' && !isNaN(event.target.value)) {
            const numericValue = parseFloat(event.target.value)
            if (!isNaN(numericValue)) {
                const rounded = Number(numericValue.toFixed(props.precision))
                event.target.value = rounded
                // Emit commit with the numeric value rounded to precision
                emit('commit', rounded)
            }
        }
    }
    emit('blur', event)
}

const handleFocus = (event) => {
    try { setTimeout(() => inputRef.value?.select?.(), 0) } catch (_) { }
    emit('focus', event)
}

const handleChange = (event) => {
    emit('change', event)
}

// Internal key handling: arrows to adjust, enter/escape to blur
const handleKeydown = (event) => {
    if (props.readonly || props.disabled) return

    if (event.key === 'Enter' || event.key === 'Escape') {
        event.preventDefault()
        if (event.key === 'Enter') emit('enter')
        inputRef.value?.blur()
        return
    }

    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
        event.preventDefault()
        const direction = event.key === 'ArrowUp' ? 1 : -1
        let step = props.step !== null ? Number(props.step) : (props.formatted ? 0.01 : 1)
        if (!isFinite(step) || step <= 0) step = 0.01
        const current = Number(props.modelValue || 0)
        let next = current + direction * step
        if (props.min !== null && Number.isFinite(Number(props.min))) {
            next = Math.max(next, Number(props.min))
        }
        if (props.max !== null && Number.isFinite(Number(props.max))) {
            next = Math.min(next, Number(props.max))
        }
        next = Number(next.toFixed(props.precision))
        emit('update:modelValue', next)
        // displayValue will sync via watcher
    }
}

// Expose focus method
defineExpose({
    focus: () => { inputRef.value?.focus?.(); try { inputRef.value?.select?.() } catch (_) { } },
    blur: () => inputRef.value?.blur()
})
</script>

<template>
    <input ref="inputRef" :type="formatted ? 'text' : 'number'" :value="formatted ? displayValue : modelValue"
        :placeholder="placeholder" :required="required" :disabled="disabled" :readonly="readonly"
        :min="formatted ? null : min" :max="formatted ? null : max" :step="formatted ? null : step"
        :inputmode="formatted ? 'decimal' : 'decimal'" :class="finalClasses" @input="handleInput"
        @keydown="handleKeydown" @blur="handleBlur" @focus="handleFocus" @change="handleChange" />
</template>

<style scoped>
/* Additional styles can be added here if needed */
</style>
