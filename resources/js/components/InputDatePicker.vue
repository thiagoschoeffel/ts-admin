<script setup>
// Utilitário seguro para comparar datas
function isSameDay(a, b) {
  if (!a || !b) return false
  if (!(a instanceof Date) || !(b instanceof Date)) return false
  return a.getFullYear() === b.getFullYear() &&
         a.getMonth() === b.getMonth() &&
         a.getDate() === b.getDate()
}
import { ref, computed, watch, onMounted, onUnmounted, defineExpose, nextTick } from 'vue'
import LocalDropdown from './LocalDropdown.vue'
import InputSelect from './InputSelect.vue'
import Button from './Button.vue'
import { ChevronRightIcon, CalendarDaysIcon, ClockIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null
  },
  range: { type: Boolean, default: false },
  withTime: { type: Boolean, default: false },
  size: { type: String, default: 'md', validator: v => ['sm','md','lg'].includes(v) },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  success: { type: Boolean, default: false },
  clearable: { type: Boolean, default: true },
  minDate: { type: String, default: null }, // YYYY-MM-DD (ou YYYY-MM-DD HH:mm)
  maxDate: { type: String, default: null },
  class: { type: String, default: '' },
  // Validação opcional de range máximo em dias (apenas quando range=true)
  maxRangeDays: { type: Number, default: null },
  // Permite digitação manual (apenas quando range=false)
  allowManualInput: { type: Boolean, default: false },
  // Permite priorizar foco deste input ao abrir modais
  autofocus: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'change'])

// Helpers de data simples, sem libs externas
const pad2 = (n) => String(n).padStart(2, '0')
const toYMD = (d) => `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`
const toYMDHM = (d) => `${toYMD(d)} ${pad2(d.getHours())}:${pad2(d.getMinutes())}`
const parseDate = (s) => {
  if (!s) return null
  if (typeof s !== 'string') return null
  // suporta 'YYYY-MM-DD' e 'YYYY-MM-DD HH:mm'
  const [datePart, timePart] = s.trim().split(' ')
  const [y,m,dd] = (datePart || '').split('-').map(Number)
  if (!y || !m || !dd) return null
  let hh = 0, mm = 0
  if (timePart) {
    const [h,mi] = timePart.split(':').map(Number)
    if (Number.isFinite(h)) hh = h
    if (Number.isFinite(mi)) mm = mi
  }
  const d = new Date(y, (m-1), dd, hh, mm, 0, 0)
  if (isNaN(d.getTime())) return null
  return d
}
const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate(), 0,0,0,0)
const endOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23,59,0,0)
const fmt2 = (n) => pad2(Number(n) || 0)
const cellKey = (cell, suffix='') => cell.placeholder ? `ph${suffix}-${cell.pi}` : `${toYMD(cell.d)}${suffix}`

const minD = computed(() => parseDate(props.minDate))
const maxD = computed(() => parseDate(props.maxDate))
const isBefore = (a, b) => a.getTime() < b.getTime()
const isAfter = (a, b) => a.getTime() > b.getTime()
const clampDate = (d) => {
  if (!d) return d
  if (minD.value && isBefore(d, minD.value)) return new Date(minD.value)
  if (maxD.value && isAfter(d, maxD.value)) return new Date(maxD.value)
  return d
}

// Estado interno de seleção (Dropdown controla abrir/fechar)
const instanceId = Math.random().toString(36).slice(2)
const rootEl = ref(null)

function getPanelEl() {
  return dropdownRef.value?.panel?.value || null
}

function isInsideDropdown(el) {
  if (!el) return false
  const root = rootEl.value
  const panel = getPanelEl()
  if (root && root.contains(el)) return true
  if (panel && panel.contains(el)) return true
  return false
}

let suppressBlurClose = false
let suppressBlurTimeout = null
function markInternalInteraction() {
  suppressBlurClose = true
  if (suppressBlurTimeout) clearTimeout(suppressBlurTimeout)
  suppressBlurTimeout = setTimeout(() => {
    suppressBlurClose = false
    suppressBlurTimeout = null
  }, 120)
}

function handleClickOutside(e) {
  if (!isInsideDropdown(e.target) && dropdownRef.value && dropdownRef.value.isOpen) {
    dropdownRef.value.isOpen = false
  }
}

// Fallback global: se algum foco for para fora e este datepicker estiver aberto, fecha
function handleGlobalFocusIn(e) {
  if (!dropdownRef.value || !dropdownRef.value.isOpen) return
  const target = e.target
  if (target !== inputEl.value && !isInsideDropdown(target)) {
    dropdownRef.value.isOpen = false
  }
}

// Visualização de mês(es)
const today = new Date()
const viewMonth = ref(new Date(today.getFullYear(), today.getMonth(), 1)) // primeiro mês

// Seleções
const singleDate = ref(null) // Date | null
const singleH = ref(0)
const singleM = ref(0)

const rangeStart = ref(null) // Date | null
const rangeEnd = ref(null)
const hoverDate = ref(null)
const startH = ref(0), startM = ref(0)
const endH = ref(0), endM = ref(0)

// (moved below refs declaration) Reforça a seleção quando o dropdown abre

if (!props.range) {
  watch([singleH, singleM], () => {
    // Não emitir automaticamente se estiver em modo de edição manual
    if (isEditing.value) return
    if (singleDate.value) commitSingle()
  })
}

if (props.range) {
  watch([startH, startM, endH, endM], () => {
    if (rangeStart.value && rangeEnd.value) commitRange()
  })
}

function syncFromModel() {
  if (props.range) {
    const startS = props.modelValue?.start || null
    const endS = props.modelValue?.end || null
    const sd = parseDate(startS)
    const ed = parseDate(endS)
    rangeStart.value = sd
    rangeEnd.value = ed
    if (sd) { startH.value = sd.getHours(); startM.value = sd.getMinutes() }
    if (ed) { endH.value = ed.getHours(); endM.value = ed.getMinutes() }
    // Centraliza visualização
    if (sd) viewMonth.value = new Date(sd.getFullYear(), sd.getMonth(), 1)
    else if (ed) viewMonth.value = new Date(ed.getFullYear(), ed.getMonth(), 1)
  } else {
    const sd = parseDate(props.modelValue)
    singleDate.value = sd
    if (sd) { singleH.value = sd.getHours(); singleM.value = sd.getMinutes() }
    if (sd) viewMonth.value = new Date(sd.getFullYear(), sd.getMonth(), 1)
  }
}

// Classe do input (replica padrão dos inputs)
const inputClasses = computed(() => {
  const base = [
    'border','border-slate-300','rounded-lg','transition-colors','duration-200',
  ]
  if (props.disabled) base.push('bg-gray-100')
  else base.push('bg-white')
  if (!props.disabled) base.push('focus:outline-none','focus:ring-2','focus:ring-blue-500','focus:border-blue-500')
  if (props.size === 'lg') base.push('px-6','py-3','text-base')
  else if (props.size === 'sm') base.push('px-3','py-1.5','text-xs')
  else base.push('px-4','py-2','text-sm')
  if (props.disabled) base.push('cursor-not-allowed','text-slate-500')
  if (props.error) base.push('border-red-500','focus:border-red-500','focus:ring-red-500')
  else if (props.success) base.push('border-green-500','focus:border-green-500','focus:ring-green-500')
  // espaço para ícones/botões internos
  base.push('pr-9', 'w-full')
  return base.join(' ')
})
const finalClasses = computed(() => [inputClasses.value, props.class || ''].filter(Boolean).join(' '))

// Expose focus method to parent components
const inputEl = ref(null)
const dropdownRef = ref(null)

// Inicializar com modelValue
onMounted(() => {
  syncFromModel()
  document.addEventListener('focusin', handleGlobalFocusIn, true)
  window.addEventListener('tsadmin:datepicker-open', (e) => {
    const other = e?.detail?.id
    if (other && other !== instanceId && dropdownRef.value && dropdownRef.value.isOpen) {
      dropdownRef.value.isOpen = false
    }
  })
})

watch(() => props.modelValue, (newVal, oldVal) => {
  syncFromModel()
})

function selectAllSoon() {
  try {
    // First tick: after focus settles
    setTimeout(() => {
      try {
        const el = inputEl.value
        if (!el) return
        const wasRo = el.readOnly
        if (wasRo) el.readOnly = false
        el.select && el.select()
        if (wasRo) el.readOnly = true
      } catch (_) {}
    }, 0)
    // Second tick: after any value updates/toggles that might override selection
    setTimeout(() => {
      try {
        const el = inputEl.value
        if (!el) return
        const wasRo = el.readOnly
        if (wasRo) el.readOnly = false
        el.select && el.select()
        if (wasRo) el.readOnly = true
      } catch (_) {}
    }, 50)
  } catch (_) {}
}
defineExpose({
  focus: () => { inputEl.value?.focus?.(); selectAllSoon() }
})

// Reforça a seleção quando o dropdown abre (alguns browsers mexem na seleção ao abrir popups)
watch(() => dropdownRef.value?.isOpen, (open) => {
  if (open) {
    document.addEventListener('mousedown', handleClickOutside, true)
    window.dispatchEvent(new CustomEvent('tsadmin:datepicker-open', { detail: { id: instanceId } }))
    if (document.activeElement === inputEl.value) {
      selectAllSoon()
    }
  } else {
    document.removeEventListener('mousedown', handleClickOutside, true)
  }
})

onUnmounted(() => {
  document.removeEventListener('focusin', handleGlobalFocusIn, true)
  document.removeEventListener('mousedown', handleClickOutside, true)
  if (suppressBlurTimeout) {
    clearTimeout(suppressBlurTimeout)
    suppressBlurTimeout = null
  }
})

// Labels/formatos
const weekDays = ['D','S','T','Q','Q','S','S']
const monthNames = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro']

const formatDateBR = (d) => `${pad2(d.getDate())}/${pad2(d.getMonth()+1)}/${d.getFullYear()}`
const formatTimeBR = (h,m) => `${pad2(h)}:${pad2(m)}`

const displayValue = computed(() => {
  if (props.range) {
    if (!rangeStart.value && !rangeEnd.value) return ''
    const left = rangeStart.value ? (props.withTime ? `${formatDateBR(rangeStart.value)} ${formatTimeBR(startH.value, startM.value)}` : formatDateBR(rangeStart.value)) : ''
    const right = rangeEnd.value ? (props.withTime ? `${formatDateBR(rangeEnd.value)} ${formatTimeBR(endH.value, endM.value)}` : formatDateBR(rangeEnd.value)) : ''
    return [left, right].filter(Boolean).join(' — ')
  } else {
    if (!singleDate.value) return ''
    return props.withTime
      ? `${formatDateBR(singleDate.value)} ${formatTimeBR(singleH.value, singleM.value)}`
      : `${formatDateBR(singleDate.value)}`
  }
})

// Dias do mês, com placeholders iniciais para alinhar a semana
function monthDays(firstOfMonth) {
  const first = new Date(firstOfMonth.getFullYear(), firstOfMonth.getMonth(), 1)
  const startWeekday = first.getDay() // 0-dom .. 6-sab
  const daysInMonth = new Date(first.getFullYear(), first.getMonth()+1, 0).getDate()
  const cells = []
  // placeholders (sem exibir números de meses adjacentes)
  for (let i = 0; i < startWeekday; i++) {
    cells.push({ placeholder: true, pi: i })
  }
  // dias do mês corrente
  for (let day = 1; day <= daysInMonth; day++) {
    const d = new Date(first.getFullYear(), first.getMonth(), day)
    const isToday = toYMD(d) === toYMD(today)
    let disabled = false
    if (minD.value && isBefore(d, startOfDay(minD.value))) disabled = true
    if (maxD.value && isAfter(d, endOfDay(maxD.value))) disabled = true
    cells.push({ d, isToday, disabled, placeholder: false })
  }
  return cells
}

const monthA = computed(() => monthDays(viewMonth.value))
const nextMonth = computed(() => new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth()+1, 1))
const monthB = computed(() => props.range ? monthDays(nextMonth.value) : [])
const gridClass = computed(() => ['grid gap-3', props.range ? 'grid-cols-2' : 'grid-cols-1'].join(' '))
const hours = Array.from({ length: 24 }, (_, i) => i)
const minutes = Array.from({ length: 60 }, (_, i) => i)
const hourOptions = computed(() => hours.map(h => ({ value: h, label: fmt2(h) })))
const minuteOptions = computed(() => minutes.map(m => ({ value: m, label: fmt2(m) })))

function prevMonth() { viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth()-1, 1) }
function nextMonthFn() { viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth()+1, 1) }

// Lógica de seleção
function onSelectDay(day) {
  if (day.disabled) return
  if (props.range) {
    if (!rangeStart.value || (rangeStart.value && rangeEnd.value)) {
      rangeStart.value = startOfDay(day.d)
      startH.value = props.withTime ? startH.value : 0
      startM.value = props.withTime ? startM.value : 0
      rangeEnd.value = null
      hoverDate.value = null
      return
    }
    // definindo fim
    const candidateEnd = startOfDay(day.d)
    if (candidateEnd.getTime() < rangeStart.value.getTime()) {
      // inverte
      rangeEnd.value = rangeStart.value
      endH.value = startH.value
      endM.value = startM.value
      rangeStart.value = candidateEnd
      startH.value = props.withTime ? startH.value : 0
      startM.value = props.withTime ? startM.value : 0
    } else {
      rangeEnd.value = candidateEnd
      endH.value = props.withTime ? endH.value : 0
      endM.value = props.withTime ? endM.value : 0
    }
    if (!props.withTime) {
      // emitir automaticamente quando não há tempo
      commitRange()
    }
  } else {
    singleDate.value = startOfDay(day.d)

    if (!props.withTime) {
      // emite imediatamente quando não há seleção de horário
      const output = toYMD(singleDate.value)
      emit('update:modelValue', output)
      emit('change', output)
    } else {
      // Com horário: atualiza a data mas mantém a hora atual e emite
      commitSingle()
    }
  }
}

function inSelectedRange(d) {
  if (!props.range) return false
  const s = rangeStart.value, e = rangeEnd.value
  if (!s && !e) return false
  const time = startOfDay(d).getTime()
  if (s && e) return time >= startOfDay(s).getTime() && time <= startOfDay(e).getTime()
  if (s && hoverDate.value) {
    const start = Math.min(startOfDay(s).getTime(), startOfDay(hoverDate.value).getTime())
    const end = Math.max(startOfDay(s).getTime(), startOfDay(hoverDate.value).getTime())
    return time >= start && time <= end
  }
  return false
}

// Commit/emit
function commitSingle() {
  if (!singleDate.value) return
  const d = new Date(singleDate.value)
  d.setHours(props.withTime ? Number(singleH.value)||0 : 0, props.withTime ? Number(singleM.value)||0 : 0, 0, 0)
  const out = props.withTime ? toYMDHM(d) : toYMD(d)

  // Reset manual editing mode when selecting from calendar
  if (isManual.value) {
    isEditing.value = false
    userInput.value = displayValue.value
  }

  emit('update:modelValue', out)
  emit('change', out)
}

function commitRange() {
  if (!rangeStart.value || !rangeEnd.value) return
  const s = new Date(rangeStart.value)
  const e = new Date(rangeEnd.value)
  // Se houver maxRangeDays definido, impedir commit quando exceder
  if (props.maxRangeDays && maxRangeExceeded.value) return
  if (props.withTime) {
    s.setHours(Number(startH.value)||0, Number(startM.value)||0, 0, 0)
    e.setHours(Number(endH.value)||0, Number(endM.value)||0, 0, 0)
  } else {
    s.setHours(0,0,0,0)
    e.setHours(0,0,0,0)
  }
  const out = props.withTime
    ? { start: toYMDHM(s), end: toYMDHM(e) }
    : { start: toYMD(s), end: toYMD(e) }
  emit('update:modelValue', out)
  emit('change', out)
}

function clearValue() {
  if (props.range) {
    rangeStart.value = null
    rangeEnd.value = null
    hoverDate.value = null
    emit('update:modelValue', { start: null, end: null })
    emit('change', { start: null, end: null })
  } else {
    singleDate.value = null
    // Limpar também o userInput quando em modo manual
    if (isManual.value) {
      userInput.value = ''
      isEditing.value = false
    }
    emit('update:modelValue', null)
    emit('change', null)
  }
}

const effectivePlaceholder = computed(() => {
  if (props.placeholder) return props.placeholder
  return props.range ? (props.withTime ? 'Selecionar período e horário' : 'Selecionar período') : (props.withTime ? 'Selecionar data e horário' : 'Selecionar data')
})

// Para navegação por hover no range
function onHoverDay(day) { hoverDate.value = day?.d || null }

// Clique em um dia: aplica seleção e decide fechamento do painel
function handleDayClick(cell, close) {
  onSelectDay(cell)
  // Só fecha dropdown se for seleção simples sem hora
  if (!props.range && !props.withTime) {
    nextTick(() => close && close())
    return
  }
  // Para range sem hora, fecha apenas quando ambos os lados estão selecionados
  if (props.range && !props.withTime && rangeStart.value && rangeEnd.value) {
    nextTick(() => close && close())
    return
  }
  // Para os demais casos (com hora), não fecha automaticamente
}

function applyRange(close) {
  commitRange()
  if (dropdownRef.value && dropdownRef.value.isOpen) {
    dropdownRef.value.isOpen = false
  }
}

function applySingle(close) {
  commitSingle()
  if (dropdownRef.value && dropdownRef.value.isOpen) {
    dropdownRef.value.isOpen = false
  }
}

// Validação: range máximo em dias
const maxRangeExceeded = computed(() => {
  if (!props.range || !props.maxRangeDays) return false
  if (!rangeStart.value || !rangeEnd.value) return false
  const ms = endOfDay(rangeEnd.value).getTime() - startOfDay(rangeStart.value).getTime()
  const days = ms / (1000 * 60 * 60 * 24)
  return days > props.maxRangeDays
})

// Entrada manual (apenas quando range=false e allowManualInput=true)
const isManual = computed(() => props.allowManualInput && !props.range)
const userInput = ref('')
const isEditing = ref(false)

watch([displayValue, isManual], () => {
  if (!isManual.value) return
  if (!isEditing.value) userInput.value = displayValue.value
})

// Watch userInput para atualizar calendário em tempo real (sem emitir)
watch(userInput, (newVal) => {
  if (!isManual.value || !isEditing.value) return

  const d = parsePtBrManual(newVal)

  if (d) {
    // Atualiza APENAS a visualização do calendário (sem emitir)
    viewMonth.value = new Date(d.getFullYear(), d.getMonth(), 1)

    // Atualiza os valores internos SEM disparar o commit
    // (o isEditing já bloqueia o watch de singleH/singleM)
    singleDate.value = startOfDay(d)
    if (props.withTime) {
      singleH.value = d.getHours()
      singleM.value = d.getMinutes()
    }
  }
})

function parsePtBrManual(val) {
  if (!val || typeof val !== 'string') return null
  const s = val.trim()

  // Accept dd/mm/yyyy or dd/mm/yyyy HH:mm or dd/mm/yyyy HH:mm:ss
  const m = s.match(/^\s*(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?\s*$/)

  if (!m) return null
  const dd = Number(m[1]), mm = Number(m[2]), yyyy = Number(m[3])
  const hh = Number(m[4] ?? '0'), mi = Number(m[5] ?? '0')

  // Basic validation
  if (mm < 1 || mm > 12 || dd < 1 || dd > 31) return null

  const d = new Date(yyyy, mm - 1, dd, hh, mi, 0, 0)
  if (Number.isNaN(d.getTime())) return null

  return d
}

function maskManual(val) {
  // Keep only digits
  let digits = String(val || '').replace(/\D/g, '')
  const withTime = !!props.withTime
  const maxLen = withTime ? 12 : 8 // ddMMyyyyHHmm or ddMMyyyy
  digits = digits.slice(0, maxLen)

  // Build masked string progressively
  let out = ''

  // DD (2 dígitos)
  if (digits.length >= 1) {
    out = digits.slice(0, Math.min(2, digits.length))
  }

  // Add '/' after DD
  if (digits.length >= 3) {
    out = digits.slice(0, 2) + '/' + digits.slice(2, Math.min(4, digits.length))
  }

  // Add second '/' after MM
  if (digits.length >= 5) {
    out = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4, Math.min(8, digits.length))
  }

  // Se não tiver tempo ou não tiver mais dígitos, retorna
  if (!withTime || digits.length <= 8) {
    return out
  }

  // Add space then time HH:mm
  const dateStr = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4, 8)
  const timeDigits = digits.slice(8)

  if (timeDigits.length >= 1) {
    out = dateStr + ' ' + timeDigits.slice(0, Math.min(2, timeDigits.length))
  }

  if (timeDigits.length >= 3) {
    out = dateStr + ' ' + timeDigits.slice(0, 2) + ':' + timeDigits.slice(2, Math.min(4, timeDigits.length))
  }

  return out
}

function onManualCommit() {
  if (!isManual.value) return
  const d = parsePtBrManual(userInput.value)
  if (!d) return

  // Atualiza a data selecionada
  singleDate.value = startOfDay(d)
  if (props.withTime) {
    singleH.value = d.getHours()
    singleM.value = d.getMinutes()
  }

  // Atualiza a visualização do calendário para mostrar o mês correto
  viewMonth.value = new Date(d.getFullYear(), d.getMonth(), 1)

  // Reseta o modo de edição e sincroniza
  isEditing.value = false
  commitSingle()

  // Sync back to display string
  userInput.value = displayValue.value
}

function handleBlur(event) {
  setTimeout(() => {
    const active = document.activeElement
    if (dropdownRef.value && dropdownRef.value.isOpen && !isInsideDropdown(active)) {
      dropdownRef.value.isOpen = false
    }
  }, 150)
}

function openDropdown() {
  if (props.disabled) return
  dropdownRef.value?.open?.()
}

function handleFocus() {
  if (props.disabled) return
  if (!dropdownRef.value?.isOpen) selectAllSoon()
  if (isManual.value) isEditing.value = true
  window.dispatchEvent(new CustomEvent('tsadmin:datepicker-open', { detail: { id: instanceId } }))
  openDropdown()
}

function handleTriggerClick() {
  if (props.disabled) return
  openDropdown()
  delay(() => inputEl.value?.focus?.())
}

const delay = (fn, ms = 0) => setTimeout(fn, ms)
</script>

<template>
  <div class="block w-full" ref="rootEl">
    <!-- Labels acima dos inputs -->
    <!-- Nenhum label interno, apenas o input -->
    <div class="relative inline-flex w-full" :aria-disabled="props.disabled">
      <input
        ref="inputEl"
        :value="isManual ? userInput : displayValue"
        :placeholder="effectivePlaceholder"
        :disabled="props.disabled"
        :readonly="!isManual"
        :class="finalClasses"
        :autofocus="props.autofocus"
        @input="(e) => { if (isManual) userInput = maskManual(e.target.value) }"
        @focus="handleFocus"
        @click="handleFocus"
        @keydown.enter.prevent="onManualCommit"
        @blur="handleBlur"
      />
      <CalendarDaysIcon class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
      <button v-if="clearable && !props.disabled && displayValue" type="button" tabindex="-1" class="absolute right-8 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" @click.stop="clearValue">
        <XMarkIcon class="h-5 w-5" />
      </button>
    </div>
    <!-- Dropdown apenas para o painel/calendário -->
    <LocalDropdown ref="dropdownRef" class="block w-full" :minWidth="props.range ? 560 : 280" :openClass="'is-open'" :zIndex="2000">
  <template #default="{ close }">
  <div class="p-3" @mouseleave="onHoverDay(null)" @mousedown.stop>
        <!-- Cabeçalho superior: meses e setas -->
        <div class="pb-2">
          <template v-if="props.range">
            <div class="grid grid-cols-2 items-center gap-3">
              <div class="px-1 text-sm text-slate-700">
                {{ monthNames[viewMonth.getMonth()] }} de {{ viewMonth.getFullYear() }}
              </div>
              <div class="flex items-center justify-between">
                <div class="px-1 text-sm text-slate-700">
                  {{ monthNames[nextMonth.getMonth()] }} de {{ nextMonth.getFullYear() }}
                </div>
                <div class="flex items-center gap-1">
                  <button type="button" tabindex="-1" class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-slate-100" @click.stop="prevMonth" :disabled="props.disabled">
                    <ChevronRightIcon class="h-4 w-4 rotate-180" />
                  </button>
                  <button type="button" tabindex="-1" class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-slate-100" @click.stop="nextMonthFn" :disabled="props.disabled">
                    <ChevronRightIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </template>
          <template v-else>
            <div class="flex items-center justify-between">
              <div class="px-1 text-sm text-slate-700">
                {{ monthNames[viewMonth.getMonth()] }} de {{ viewMonth.getFullYear() }}
              </div>
              <div class="flex items-center gap-1">
                <button type="button" tabindex="-1" class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-slate-100" @click.stop="prevMonth" :disabled="props.disabled">
                  <ChevronRightIcon class="h-4 w-4 rotate-180" />
                </button>
                <button type="button" tabindex="-1" class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-slate-100" @click.stop="nextMonthFn" :disabled="props.disabled">
                  <ChevronRightIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </template>
        </div>

        <div :class="gridClass">
          <!-- Calendário A -->
          <div>
            <div class="grid grid-cols-7 gap-1 px-1 pb-1 text-center text-xs text-slate-500">
              <div v-for="w in weekDays" :key="w">{{ w }}</div>
            </div>
            <div class="grid grid-cols-7 gap-1 px-1">
              <button
                v-for="cell in monthA"
                :key="cellKey(cell,'-a')"
                type="button"
                tabindex="-1"
                class="h-9 w-full rounded-md text-sm font-normal flex items-center justify-center"
                :class="[
                  cell.placeholder ? 'text-transparent pointer-events-none' : 'text-slate-800',
                  cell.isToday && !cell.placeholder ? 'ring-1 ring-blue-400' : '',
                  (props.range && !cell.placeholder && inSelectedRange(cell.d)) ? 'bg-blue-100' : '',
                  isSameDay(cell.d, hoverDate) ? 'bg-blue-200' : '',
                  (!props.range && isSameDay(cell.d, hoverDate)) ? 'bg-blue-200' : '',
                  (!props.range && isSameDay(cell.d, singleDate)) ? 'bg-blue-100' : '',
                ]"
                :disabled="cell.disabled || cell.placeholder"
                @mouseenter="onHoverDay(cell)"
                @mouseleave="onHoverDay(null)"
                @click.stop="handleDayClick(cell, close)"
              >
                {{ cell.placeholder ? '\u00A0' : cell.d.getDate() }}
              </button>
            </div>

            <!-- Tempo single ou início do range -->
            <div v-if="props.withTime && (!props.range || (props.range && rangeStart))" class="mt-2 flex items-center gap-2 text-sm">
              <ClockIcon class="h-4 w-4 text-slate-500" />
              <template v-if="!props.range">
                <div class="w-18"><InputSelect v-model="singleH" :options="hourOptions" size="sm" :optionValue="'value'" :optionLabel="'label'" :placeholder="null" tabindex="-1" /></div>
                <span>:</span>
                <div class="w-18"><InputSelect v-model="singleM" :options="minuteOptions" size="sm" :optionValue="'value'" :optionLabel="'label'" :placeholder="null" tabindex="-1" /></div>
              </template>
              <template v-else>
                <div class="text-slate-500">Início</div>
                <div class="w-18"><InputSelect v-model="startH" :options="hourOptions" size="sm" :optionValue="'value'" :optionLabel="'label'" :placeholder="null" tabindex="-1" /></div>
                <span>:</span>
                <div class="w-18"><InputSelect v-model="startM" :options="minuteOptions" size="sm" :optionValue="'value'" :optionLabel="'label'" :placeholder="null" tabindex="-1" /></div>
              </template>
            </div>
          </div>

          <!-- Calendário B (apenas range) -->
          <div v-if="props.range">
            <div class="grid grid-cols-7 gap-1 px-1 pb-1 text-center text-xs text-slate-500">
              <div v-for="w in weekDays" :key="'b'+w">{{ w }}</div>
            </div>
            <div class="grid grid-cols-7 gap-1 px-1">
              <button
                v-for="cell in monthB"
                :key="cellKey(cell,'-b')"
                type="button"
                tabindex="-1"
                class="h-9 w-full rounded-md text-sm font-normal flex items-center justify-center"
                :class="[
                  cell.placeholder ? 'text-transparent pointer-events-none' : 'text-slate-800',
                  cell.isToday && !cell.placeholder ? 'ring-1 ring-blue-400' : '',
                  (!cell.placeholder && inSelectedRange(cell.d)) ? 'bg-blue-100' : '',
                  isSameDay(cell.d, hoverDate) ? 'bg-blue-200' : '',
                  (!props.range && isSameDay(cell.d, singleDate)) ? 'bg-blue-200' : '',
                ]"
                :disabled="cell.disabled || cell.placeholder"
                @mouseenter="onHoverDay(cell)"
                @mouseleave="onHoverDay(null)"
                @click.stop="handleDayClick(cell, close)"
              >
                {{ cell.placeholder ? '\u00A0' : cell.d.getDate() }}
              </button>
            </div>

            <div v-if="props.withTime && rangeEnd" class="mt-2 flex items-center gap-2 text-sm">
              <ClockIcon class="h-4 w-4 text-slate-500" />
              <div class="text-slate-500">Fim</div>
              <div class="w-18"><InputSelect v-model="endH" :options="hourOptions" size="sm" :optionValue="'value'" :optionLabel="'label'" :placeholder="null" tabindex="-1" /></div>
              <span>:</span>
              <div class="w-18"><InputSelect v-model="endM" :options="minuteOptions" size="sm" :optionValue="'value'" :optionLabel="'label'" :placeholder="null" tabindex="-1" /></div>
            </div>
          </div>
        </div>

        <!-- Validação de range -->
        <div v-if="props.range && props.maxRangeDays && maxRangeExceeded" class="mt-3 text-xs text-rose-600">
          Período máximo de {{ props.maxRangeDays }} dias excedido. Ajuste as datas.
        </div>

        <!-- Ações -->
        <div v-if="props.withTime || props.range" class="mt-3 flex items-center justify-end gap-2">
          <Button v-if="clearable" variant="ghost" size="sm" tabindex="-1" @click.stop="clearValue">Limpar</Button>
          <Button v-if="props.range && !props.withTime" variant="primary" size="sm" tabindex="-1" :disabled="!rangeStart || !rangeEnd || maxRangeExceeded" @click.stop="applyRange(close)">Aplicar</Button>
          <Button v-else-if="props.range && props.withTime" variant="primary" size="sm" tabindex="-1" :disabled="!rangeStart || !rangeEnd || maxRangeExceeded" @click.stop="applyRange(close)">Aplicar</Button>
          <Button v-else-if="!props.range && props.withTime" variant="primary" size="sm" tabindex="-1" :disabled="!singleDate" @click.stop="applySingle(close)">Aplicar</Button>
        </div>
      </div>
      </template>
  </LocalDropdown>
  </div>
</template>

<style scoped>
/* Estilos adicionais pontuais para a grade podem ser adicionados se necessário */
</style>
