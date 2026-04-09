<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger', 'outline-danger', 'outline', 'ghost', 'ghost-inverse', 'inverse'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'button'
  },
  href: {
    type: String,
    default: null
  }
});

const classes = computed(() => {
  const baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2';

  const variantClasses = {
    primary: 'rounded-lg text-white bg-blue-600 shadow-sm hover:-translate-y-0.5 hover:bg-blue-500 hover:shadow-lg focus-visible:outline-blue-500',
    secondary: 'rounded-lg text-white bg-slate-900 shadow-sm hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-lg focus-visible:outline-slate-900',
    danger: 'rounded-lg text-white bg-rose-600 shadow-sm hover:-translate-y-0.5 hover:bg-rose-500 hover:shadow-lg focus-visible:outline-rose-500',
    'outline-danger': 'rounded-lg text-rose-600 border border-rose-600 hover:bg-rose-50 hover:-translate-y-0.5 hover:shadow-sm focus-visible:outline-rose-500',
    outline: 'rounded-lg text-slate-600 border border-slate-200 hover:bg-slate-50 hover:-translate-y-0.5 hover:shadow-sm focus-visible:outline-slate-500',
    ghost: 'rounded-lg text-slate-500 bg-slate-50 hover:text-slate-700 focus-visible:outline-blue-500',
    'ghost-inverse': 'rounded-lg text-slate-200 hover:text-white focus-visible:outline-white',
    inverse: 'rounded-lg border border-white/30 bg-white/10 text-white shadow-sm hover:-translate-y-0.5 hover:bg-white/20 hover:shadow-lg focus-visible:outline-white'
  };

  const sizeClasses = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
  };

  const disabledClasses = props.disabled || props.loading ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';

  const linkClasses = props.href ? 'cursor-pointer' : '';

  return `${baseClasses} ${variantClasses[props.variant]} ${sizeClasses[props.size]} ${disabledClasses} ${linkClasses}`;
});
</script>

<template>
  <component :is="href ? Link : 'button'" :class="classes" :type="!href ? type : undefined" :href="href" :disabled="!href && (disabled || loading)">
    <slot v-if="!loading" />
    <span v-else class="animate-spin">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </span>
  </component>
</template>
