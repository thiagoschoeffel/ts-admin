<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed, Teleport, nextTick } from 'vue';
import { registerModal, unregisterModal, getIndex, hasOpenModals } from '@/components/modalStack';
import { lockScrollIfNeeded, unlockScrollIfNeeded } from '@/components/modalLockManager';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm, md, lg, xl
  closeOnEsc: { type: Boolean, default: true },
  closeOnBackdrop: { type: Boolean, default: true },
  zBase: { type: Number, default: 1100 },
  showClose: { type: Boolean, default: true },
  lockScroll: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'open', 'close']);

const open = ref(props.modelValue);
const id = ref(null);
const container = ref(null);

const index = computed(() => (id.value ? getIndex(id.value) : -1));
const zIndexOverlay = computed(() => props.zBase + (index.value >= 0 ? index.value * 20 : 0));
const zIndexPanel = computed(() => zIndexOverlay.value + 1);

const sizes = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  '2xl': 'max-w-6xl',
};
const panelSize = computed(() => sizes[props.size] || sizes.md);

watch(() => props.modelValue, async (val) => {
  if (val === open.value) return;
  open.value = val;
  if (val) onOpen(); else onClose();
});

watch(open, (val) => emit('update:modelValue', val));

function onKeydown(e) {
  if (props.closeOnEsc && e.key === 'Escape') {
    e.stopPropagation();
    close();
  }
}

function onBackdrop(e) {
  if (!props.closeOnBackdrop) return;
  // Close if clicked on the backdrop, not on the modal content
  const modalContent = e.currentTarget.querySelector('.relative.mx-auto.w-full');
  if (modalContent && modalContent.contains(e.target)) return;
  close();
}

let appliedLock = false;

function onOpen() {
  if (!id.value) id.value = registerModal();
  document.addEventListener('keydown', onKeydown, true);
  if (props.lockScroll) {
    lockScrollIfNeeded();
  }
  emit('open');
  nextTick(() => {
    // Focus the first focusable element
    try {
      const el = container.value?.querySelector('[autofocus]') || container.value?.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
      el?.focus?.();
    } catch (_) {}
  });
}

function onClose() {
  document.removeEventListener('keydown', onKeydown, true);
  if (id.value) unregisterModal(id.value);
  id.value = null;
  if (props.lockScroll) {
    unlockScrollIfNeeded();
  }
  emit('close');
}

function close() {
  // run cleanup immediately then update local state
  try { onClose(); } catch (_) {}
  open.value = false;
}

function handleCloseClick(e) {
  close();
}

onMounted(() => { if (open.value) onOpen(); });
onBeforeUnmount(() => {
  try {
    try { onClose(); } catch (_) { /* ignore */ }
    if (props.lockScroll) {
      try { unlockScrollIfNeeded(); } catch (_) {}
    }
  } catch (_) {
    try { unlockScrollIfNeeded(); } catch (_) {}
  }
});
</script>

<template>
  <Teleport to="body">
    <div v-show="open" class="fixed inset-0 p-4 sm:p-6 md:p-8" :style="{ zIndex: zIndexOverlay }" @click="onBackdrop" aria-modal="true" role="dialog">
      <div class="absolute inset-0 bg-slate-900/50"></div>
      <div class="relative mx-auto w-full" :class="panelSize" :style="{ zIndex: zIndexPanel }">
        <div ref="container" class="rounded-xl border border-slate-200 bg-white shadow-2xl flex flex-col overflow-hidden">
          <div class="flex items-center justify-between gap-4 border-b border-slate-200 p-4">
            <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
            <button v-if="showClose" type="button" class="rounded-md p-1 text-slate-500 hover:bg-slate-100" @click="handleCloseClick" aria-label="Fechar">
              ✕
            </button>
          </div>
          <!-- content area: allow internal scrolling when content is tall -->
          <div class="p-4 overflow-auto" :style="{ maxHeight: '80vh' }">
            <slot />
          </div>
          <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-slate-200 p-3">
            <slot name="footer" :close="close" />
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
</style>
