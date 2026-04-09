<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'secondary',
    validator: (value) => ['success', 'danger', 'warning', 'info', 'primary', 'secondary'].includes(value)
  },
  size: {
    type: String,
    default: 'sm',
    validator: (value) => ['xs', 'sm', 'md'].includes(value)
  }
});

const classes = computed(() => {
  const baseClasses = 'inline-flex items-center gap-1.5 rounded-full font-semibold';

  const variantClasses = {
    success: 'bg-green-50 text-green-700 border border-green-200',
    danger: 'bg-red-50 text-red-700 border border-red-200',
    warning: 'bg-yellow-50 text-yellow-700 border border-yellow-200',
    info: 'bg-blue-50 text-blue-700 border border-blue-200',
    primary: 'bg-blue-50 text-blue-700 border border-blue-200',
    secondary: 'bg-gray-50 text-gray-700 border border-gray-200'
  };

  const sizeClasses = {
    xs: 'px-2 py-0.5 text-xs',
    sm: 'px-2.5 py-1 text-xs',
    md: 'px-3 py-1.5 text-sm'
  };

  return `${baseClasses} ${variantClasses[props.variant]} ${sizeClasses[props.size]}`;
});
</script>

<template>
  <span :class="classes">
    <slot />
  </span>
</template>
