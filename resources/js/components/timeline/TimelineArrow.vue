<template>
  <button
    type="button"
    class="timeline-arrow"
    :class="positionClass"
    :aria-label="ariaLabel"
    role="button"
    tabindex="0"
    :aria-disabled="!enabled"
    :disabled="!enabled"
    @click="handleClick"
    @keydown.enter.space="handleClick"
  >
    <svg
      class="w-6 h-6 text-slate-500"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
      :class="iconClass"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        :d="iconPath"
      />
    </svg>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  direction: {
    type: String,
    required: true,
    validator: (value) => ['left', 'right'].includes(value),
  },
  enabled: {
    type: Boolean,
    default: true,
  },
  ariaLabel: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['click']);

const positionClass = computed(() => {
  return props.direction === 'left' ? 'left-0' : 'right-0';
});

const iconClass = computed(() => {
  return props.direction === 'left' ? 'rotate-180' : '';
});

const iconPath = computed(() => {
  return 'M9 5l7 7-7 7';
});

function handleClick() {
  if (props.enabled) {
    emit('click');
  }
}
</script>

<style scoped>
.timeline-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
  background-color: rgba(255, 255, 255, 0.8);
  border-radius: 9999px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  padding: 0.25rem;
  border: 1px solid #e2e8f0;
  transition: opacity 0.15s ease-in-out;
}

.timeline-arrow:disabled {
  opacity: 0.4;
  pointer-events: none;
}

.timeline-arrow.left-0 {
  left: 0.25rem;
}

.timeline-arrow.right-0 {
  right: 0.25rem;
}
</style>
