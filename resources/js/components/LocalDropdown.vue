<template>
  <div v-show="isOpen"
       class="dropdown-panel"
       :class="openClass"
       ref="panel"
       :style="panelStyle">
    <slot :close="forceClose"></slot>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  openClass: { type: String, default: 'is-open' },
  minWidth: { type: Number, default: 280 },
  zIndex: { type: Number, default: 2000 },
})

const isOpen = ref(false)
const panel = ref(null)
const panelStyle = ref({})

function addPositionListeners() {
  window.addEventListener('scroll', onScroll, true)
  window.addEventListener('resize', onResize, true)
}

function removePositionListeners() {
  window.removeEventListener('scroll', onScroll, true)
  window.removeEventListener('resize', onResize, true)
}

function onScroll() {
  if (isOpen.value) updatePanelPosition()
}

function onResize() {
  if (isOpen.value) updatePanelPosition()
}

function updatePanelPosition() {
  const trigger = panel.value?.parentElement?.querySelector('input')
  if (!trigger) return
  const rect = trigger.getBoundingClientRect()
  const minWidth = props.minWidth
  const viewportWidth = window.innerWidth

  // Usar apenas minWidth, deixando o conteúdo definir a largura natural
  let width = minWidth

  // Alinhar à esquerda do input se houver espaço, senão à direita
  let left = rect.left
  if (rect.left + width <= viewportWidth - 8) {
    left = rect.left
  } else if (rect.right - width >= 8) {
    left = rect.right - width
  } else {
    left = Math.max(8, viewportWidth - width - 8)
  }

  // Posição vertical: abaixo do input ou acima se não houver espaço
  let top = rect.bottom + 8
  const panelHeight = panel.value ? panel.value.offsetHeight : 320
  const viewportHeight = window.innerHeight
  if (top + panelHeight > viewportHeight - 8 && rect.top - panelHeight - 8 > 0) {
    top = rect.top - panelHeight - 8
  }
  if (top < 8) top = 8

  panelStyle.value = {
    position: 'fixed',
    left: left + 'px',
    top: top + 'px',
    minWidth: minWidth + 'px',
    width: 'auto', // Largura automática baseada no conteúdo
    zIndex: props.zIndex,
  }
}


function open() {
  isOpen.value = true
  nextTick(() => {
    updatePanelPosition()
    addPositionListeners()
  })
}

function forceClose() {
  isOpen.value = false
  removePositionListeners()
}

function close() {
  isOpen.value = false
  removePositionListeners()
}

defineExpose({
  isOpen,
  open,
  close,
  panel,
})

watch(isOpen, (val) => {
  if (val) {
    nextTick(() => {
      updatePanelPosition()
      addPositionListeners()
    })
  } else {
    removePositionListeners()
  }
})

onBeforeUnmount(() => {
  removePositionListeners()
})
</script>

<style scoped>
.dropdown-panel {
  /* Estilos exatamente iguais ao menu-panel do Dropdown.vue original */
  border-radius: 0.75rem; /* rounded-xl */
  border: 1px solid rgb(226 232 240); /* border-slate-200 */
  background: white;
  padding: 0.5rem; /* p-2 */
  /* shadow-xl + ring-1 ring-slate-900/5 combinados */
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1), 0 0 0 1px rgb(15 23 42 / 0.05);
}

.dropdown-panel.is-open {
  display: block;
}
</style>
