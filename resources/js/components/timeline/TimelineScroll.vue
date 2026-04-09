<template>
  <div
    class="relative w-full max-w-full"
    :dir="dir"
    role="region"
    aria-roledescription="timeline"
    :aria-label="ariaLabel"
  >
    <!-- Seta esquerda -->
    <TimelineArrow
      v-if="showLeftArrow"
      direction="left"
      :enabled="showLeftArrow"
      :aria-label="ariaPrevLabel"
      @click="handleArrowClick('left')"
    />

    <!-- Viewport -->
    <div
      ref="viewport"
      class="timeline-viewport overflow-hidden w-full max-w-full min-w-0"
      :class="{ 'cursor-grab': touchHandlers.isCurrentlyDragging }"
      @wheel="onWheel"
      @mousedown="handleMouseDown"
      @touchstart.passive="handleTouchStart"
      @touchmove.passive="handleTouchMove"
      @touchend.passive="handleTouchEnd"
      @keydown="handleKeydown"
      tabindex="0"
      style="outline:none;"
    >
      <div
        ref="track"
        class="timeline-track flex flex-nowrap min-w-0 w-max items-end"
        :class="[gap, padding]"
        :style="[trackStyle, timelineLineStyle]"
      >
        <slot />
      </div>
    </div>

    <!-- Seta direita -->
    <TimelineArrow
      v-if="showRightArrow"
      direction="right"
      :enabled="showRightArrow"
      :aria-label="ariaNextLabel"
      @click="handleArrowClick('right')"
    />
  </div>
</template>

<script setup>
import { watch, nextTick, computed } from 'vue';
import TimelineArrow from './TimelineArrow.vue';
import { useTimelineScroll } from '../../composables/useTimelineScroll.js';
import { useTimelineNavigation } from '../../composables/useTimelineNavigation.js';
import { useTimelineTouch } from '../../composables/useTimelineTouch.js';
import { useTimelineMouse } from '../../composables/useTimelineMouse.js';

const props = defineProps({
  ariaLabel: { type: String, default: 'Linha do tempo de interações' },
  ariaPrevLabel: { type: String, default: 'Rolar para a esquerda' },
  ariaNextLabel: { type: String, default: 'Rolar para a direita' },
  scrollStep: { type: Number, default: 320 },
  dir: { type: String, default: 'ltr' },

  // Configurações de comportamento
  arrowMargin: { type: Number, default: 20 },
  inertiaDecay: { type: Number, default: 0.92 },
  minVelocity: { type: Number, default: 2 },
  enableWheelScroll: { type: Boolean, default: true },
  enableTouchDrag: { type: Boolean, default: true },
  enableMouseDrag: { type: Boolean, default: true },
  enableKeyboardNav: { type: Boolean, default: true },

  // Configurações visuais
  gap: { type: String, default: 'gap-8' },
  padding: { type: String, default: 'py-2' },
  lineColor: { type: String, default: '#e2e8f0' },
  markerColor: { type: String, default: '#3b82f6' },
  markerSize: { type: String, default: '8px' },
  lineHeight: { type: String, default: '2px' },
  verticalLineHeight: { type: String, default: '1rem' },
});

// Composable principal para scroll
const {
  viewport,
  track,
  showLeftArrow,
  showRightArrow,
  trackStyle,
  updateArrows,
  scrollBy,
  scrollToEdge,
} = useTimelineScroll({
  arrowMargin: props.arrowMargin,
});

// Composable para navegação
const {
  onKeydown: navigationKeydown,
} = useTimelineNavigation({
  scrollStep: props.scrollStep,
  scrollBy,
  scrollToEdge,
});

// Composable para touch/drag
const {
  onTouchStart,
  onTouchMove,
  onTouchEnd,
  isDragging,
} = useTimelineTouch({
  viewport,
  updateArrows,
  inertiaDecay: props.inertiaDecay,
  minVelocity: props.minVelocity,
});

// Composable para mouse/drag
const {
  onMouseDown,
  isCurrentlyDragging: isMouseDragging,
} = useTimelineMouse({
  viewport,
  updateArrows,
  inertiaDecay: props.inertiaDecay,
  minVelocity: props.minVelocity,
});

// Computed para estilos dinâmicos
const timelineLineStyle = computed(() => ({
  '--timeline-line-color': props.lineColor,
  '--timeline-marker-color': props.markerColor,
  '--timeline-marker-size': props.markerSize,
  '--timeline-line-height': props.lineHeight,
  '--timeline-vertical-line-height': props.verticalLineHeight,
}));

// Expor handlers para o template
const touchHandlers = {
  onTouchStart,
  onTouchMove,
  onTouchEnd,
  isCurrentlyDragging: () => isDragging.value || isMouseDragging.value,
};

const navigationHandlers = {
  onKeydown: navigationKeydown,
};

/**
 * Handler para cliques nas setas
 */
function handleArrowClick(direction) {
  const delta = direction === 'left' ? -props.scrollStep : props.scrollStep;
  scrollBy(delta);
}

/**
 * Handler para scroll com roda do mouse
 */
function onWheel(e) {
  if (!props.enableWheelScroll || !viewport.value) return;

  // Previne o scroll da página quando o mouse está na timeline
  e.preventDefault();
  e.stopPropagation();

  if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
    viewport.value.scrollLeft += e.deltaX;
  } else {
    viewport.value.scrollLeft += e.deltaY * 1.2;
  }
  updateArrows();
}

/**
 * Handler para touch start
 */
function handleTouchStart(e) {
  if (!props.enableTouchDrag) return;
  touchHandlers.onTouchStart(e);
}

/**
 * Handler para touch move
 */
function handleTouchMove(e) {
  if (!props.enableTouchDrag) return;
  touchHandlers.onTouchMove(e);
}

/**
 * Handler para touch end
 */
function handleTouchEnd(e) {
  if (!props.enableTouchDrag) return;
  touchHandlers.onTouchEnd(e);
}

/**
 * Handler para mouse down
 */
function handleMouseDown(e) {
  if (!props.enableMouseDrag) return;
  onMouseDown(e);
}

/**
 * Handler para navegação por teclado
 */
function handleKeydown(e) {
  if (!props.enableKeyboardNav) return;
  navigationHandlers.onKeydown(e);
}

// Watchers
watch(() => [props.dir], () => nextTick(updateArrows));
</script>

<style scoped>
.timeline-viewport {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.timeline-viewport::-webkit-scrollbar {
  display: none;
}

/* Linha conectora horizontal contínua */
.timeline-track::after {
  content: '';
  position: absolute;
  bottom: 5px; /* Centro da linha alinhado com centro dos marcadores */
  left: 0;
  right: 0;
  height: var(--timeline-line-height, 2px);
  background-color: var(--timeline-line-color, #e2e8f0);
  z-index: 1;
}

/* Marcadores e traços verticais para cada item */
.timeline-track > * {
  position: relative;
  padding-bottom: 1.5rem; /* Mais espaço entre card e linha */
}

.timeline-track > *::after {
  content: '';
  position: absolute;
  bottom: 0px; /* Começa no topo da linha */
  left: 50%;
  transform: translateX(-50%);
  width: var(--timeline-line-height, 2px);
  height: var(--timeline-vertical-line-height, 1rem);
  background-color: var(--timeline-line-color, #e2e8f0);
  z-index: 2;
}

.timeline-track > *::before {
  content: '';
  position: absolute;
  bottom: -5px; /* Marcador mais abaixo, sobre a linha horizontal */
  left: 50%;
  transform: translateX(-50%);
  width: var(--timeline-marker-size, 8px);
  height: var(--timeline-marker-size, 8px);
  background-color: var(--timeline-marker-color, #3b82f6);
  border: 2px solid white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  z-index: 4; /* Acima de tudo */
}
</style>
