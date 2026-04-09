<script setup>
import { onMounted, watch, ref } from 'vue';

const props = defineProps({
  modelValue: { type: [Boolean, String, Number, Array], default: false },
  trueValue: { type: [Boolean, String, Number], default: true },
  falseValue: { type: [Boolean, String, Number], default: false },
  value: { type: [String, Number, Boolean], default: undefined },
  indeterminate: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  name: { type: String, default: undefined },
  id: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue', 'change']);

const inputEl = ref(null);

const isArrayModel = () => Array.isArray(props.modelValue);

function isChecked() {
  if (isArrayModel()) {
    return props.modelValue.includes(props.value);
  }
  return props.modelValue === props.trueValue;
}

function onChange(e) {
  const checked = e.target.checked;
  if (isArrayModel()) {
    const arr = [...props.modelValue];
    const val = props.value;
    const idx = arr.indexOf(val);
    if (checked && idx === -1) arr.push(val);
    if (!checked && idx !== -1) arr.splice(idx, 1);
    emit('update:modelValue', arr);
    emit('change', arr);
  } else {
    const val = checked ? props.trueValue : props.falseValue;
    emit('update:modelValue', val);
    emit('change', val);
  }
}

onMounted(() => {
  if (inputEl.value) inputEl.value.indeterminate = !!props.indeterminate;
});

watch(() => props.indeterminate, (v) => {
  if (inputEl.value) inputEl.value.indeterminate = !!v;
});
</script>

<template>
  <label class="inline-flex items-center gap-2 cursor-pointer">
    <input :id="id"
           :name="name"
           ref="inputEl"
           type="checkbox"
           class="peer sr-only"
           :checked="isChecked()"
           :disabled="disabled"
           :value="value"
           @change="onChange" />

    <!-- Visual box aligned with Switch colors/effects but remains a square checkbox -->
    <span class="relative inline-flex h-5 w-5 items-center justify-center rounded-md border border-slate-300 bg-slate-50 text-white transition
                peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-blue-500/60
                peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-disabled:opacity-60">
      <!-- Checkmark -->
      <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 opacity-0 transition-opacity" :class="{ 'opacity-100': isChecked() && !indeterminate }">
        <path d="M6 10.5l2.5 2.5L14 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <!-- Indeterminate mark -->
      <span v-if="indeterminate" class="absolute block h-0.5 w-3 bg-white"></span>
    </span>

    <span class="text-sm font-medium text-slate-700"><slot /></span>
  </label>
</template>
