<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick, defineExpose } from 'vue';

const props = defineProps({
  panelClass: { type: String, default: 'menu-panel' },
  openClass: { type: String, default: 'is-open' },
  offset: { type: Number, default: 8 },
  minWidth: { type: Number, default: 170 },
  portal: { type: Boolean, default: true },
  align: { type: String, default: 'end', validator: (value) => ['center', 'end'].includes(value) },
  zIndex: { type: Number, default: 1000 },
});

const isOpen = ref(false);
const root = ref(null);
const panel = ref(null);
const coords = ref({ left: 0, top: 0, width: 0 });

const toggle = () => { isOpen.value = !isOpen.value; };
const close = () => { isOpen.value = false; };
const open = () => { isOpen.value = true; };

function updatePosition() {
  if (!root.value || !panel.value) return;
  const trigger = root.value;
  const rect = trigger.getBoundingClientRect();
  const vw = window.innerWidth;
  const vh = window.innerHeight;

  // Temporarily measure intrinsic content size without stretching to viewport
  const el = panel.value;
  const prevStyle = { visibility: el.style.visibility, left: el.style.left, top: el.style.top, position: el.style.position, width: el.style.width, display: el.style.display };
  el.style.visibility = 'hidden';
  el.style.position = 'fixed';
  el.style.left = '0px';
  el.style.top = '-9999px';
  el.style.display = 'block';
  el.style.width = 'max-content';

  // Measure content width/height
  const intrinsicWidth = Math.ceil(el.offsetWidth || el.scrollWidth || 0);
  const maxAllowed = Math.max(160, vw - 16); // viewport minus margins
  // Width is clamped to viewport and at least trigger/minWidth
  const width = Math.min(maxAllowed, Math.max(props.minWidth, intrinsicWidth));
  el.style.width = `${width}px`;
  const panelHeight = el.offsetHeight;

  // Restore visibility before placing
  el.style.visibility = prevStyle.visibility || '';
  el.style.display = prevStyle.display || '';

  // Horizontal placement
  let left;
  if (props.align === 'center') {
    left = rect.left + rect.width / 2 - width / 2; // center-aligned
  } else {
    left = rect.right - width; // end-aligned (default)
  }
  const overflowRight = left + width > vw - 8;
  const overflowLeft = left < 8;
  if (overflowRight) {
    left = vw - width - 8;
  }
  if (overflowLeft) {
    left = 8;
  }

  // Vertical placement: prefer below; flip above if needed
  let top = rect.bottom + props.offset;
  const overflowBottom = top + panelHeight > vh - 8;
  const canPlaceAbove = rect.top - props.offset - panelHeight >= 8;
  if (overflowBottom && canPlaceAbove) {
    top = rect.top - props.offset - panelHeight;
  }
  // Clamp top within viewport
  if (top < 8) top = 8;
  if (top + panelHeight > vh - 8) top = Math.max(8, vh - panelHeight - 8);

  coords.value = { left, top, width };
}

const onClickDoc = (e) => {
  const withinRoot = root.value && root.value.contains(e.target);
  const withinPanel = panel.value && panel.value.contains(e.target);
  if (!withinRoot && !withinPanel) close();
};
const onEscape = (e) => { if (e.key === 'Escape') close(); };
const onScroll = () => { if (isOpen.value) updatePosition(); };
const onResize = () => { if (isOpen.value) updatePosition(); };

watch(isOpen, async (val) => {
  if (val) {
    await nextTick();
    updatePosition();
    document.addEventListener('click', onClickDoc, true);
    document.addEventListener('keydown', onEscape, true);
    window.addEventListener('scroll', onScroll, true);
    window.addEventListener('resize', onResize, true);
  } else {
    document.removeEventListener('click', onClickDoc, true);
    document.removeEventListener('keydown', onEscape, true);
    window.removeEventListener('scroll', onScroll, true);
    window.removeEventListener('resize', onResize, true);
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickDoc, true);
  document.removeEventListener('keydown', onEscape, true);
  window.removeEventListener('scroll', onScroll, true);
  window.removeEventListener('resize', onResize, true);
});

// Expose simple API for parent components
defineExpose({
  open,
  close,
  toggle,
  isOpen,
  panel,
  root,
});
</script>

<template>
  <div ref="root" class="relative inline-block">
    <slot name="trigger" :open="isOpen" :toggle="toggle"></slot>

    <!-- Teleport panel to body to avoid overflow clipping in tables -->
    <Teleport to="body" v-if="portal">
      <div v-show="isOpen"
           :class="[panelClass, isOpen ? openClass : 'hidden']"
           :style="{ position: 'fixed', left: `${coords.left}px`, top: `${coords.top}px`, width: `${coords.width}px`, zIndex: zIndex }"
           ref="panel">
        <slot :close="close" />
      </div>
    </Teleport>

    <div v-else :class="[panelClass, isOpen ? openClass : 'hidden']" ref="panel">
      <slot :close="close" />
    </div>
  </div>
</template>
