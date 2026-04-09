<template>
  <div :class="containerClasses">
    <div
      :class="fillClasses"
      :style="{ width: `${percentage}%` }"
      role="progressbar"
      :aria-valuenow="percentage"
      aria-valuemin="0"
      aria-valuemax="100"
    >
      <div v-if="animated && percentage > 0" :class="shimmerClasses"></div>
    </div>
    <span v-if="showLabel" :class="labelClasses">
      {{ percentage }}%
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  percentage: {
    type: Number,
    required: true,
    validator: (value) => value >= 0 && value <= 100
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'success', 'warning', 'danger', 'info', 'secondary'].includes(value)
  },
  showLabel: {
    type: Boolean,
    default: true
  },
  animated: {
    type: Boolean,
    default: false
  }
});

const containerClasses = computed(() => {
  const baseClasses = 'relative w-full bg-gray-200 rounded-lg overflow-hidden';
  const sizeClasses = {
    xs: 'h-2',
    sm: 'h-3',
    md: 'h-4',
    lg: 'h-5',
    xl: 'h-6'
  };
  return `${baseClasses} ${sizeClasses[props.size]}`;
});

const fillClasses = computed(() => {
  const baseClasses = 'relative h-full transition-all duration-300 ease-out rounded-[inherit] overflow-hidden';
  const colorClasses = {
    primary: 'bg-blue-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    danger: 'bg-red-500',
    info: 'bg-cyan-500',
    secondary: 'bg-gray-500'
  };
  return `${baseClasses} ${colorClasses[props.color]}`;
});

const shimmerClasses = computed(() => {
  return [
    'absolute inset-0 rounded-[inherit] pointer-events-none',
    'shimmer-overlay'
  ].join(' ');
});

const labelClasses = computed(() => {
  const baseClasses = 'absolute inset-0 flex items-center justify-center text-xs font-semibold text-gray-700';
  const sizeClasses = {
    xs: 'px-1',
    sm: 'px-1',
    md: 'px-1',
    lg: 'px-2',
    xl: 'px-2'
  };
  return `${baseClasses} ${sizeClasses[props.size]}`;
});
</script>

<style>
@keyframes shimmer-slide {
  to { transform: translate3d(300%, 0, 0); }
}

.shimmer-overlay {
  position: absolute;
  inset: 0;
  overflow: hidden;
  border-radius: inherit;
  -webkit-mask-image: linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%);
  mask-image: linear-gradient(to right, transparent 0%, black 6%, black 94%, transparent 100%);
}

.shimmer-overlay::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 65%;
  background-image: linear-gradient(
    90deg,
    rgba(255,255,255,0) 0%,
    rgba(255,255,255,0.10) 25%,
    rgba(255,255,255,0.20) 40%,
    rgba(255,255,255,0.20) 60%,
    rgba(255,255,255,0.10) 75%,
    rgba(255,255,255,0) 100%
  );
  filter: saturate(120%);
  transform: translate3d(-120%, 0, 0);
  animation: shimmer-slide 1.6s linear infinite;
  will-change: transform;
}
</style>
