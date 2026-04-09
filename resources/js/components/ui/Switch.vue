<script setup>
const props = defineProps({
  modelValue: { type: [Boolean, String, Number], default: false },
  trueValue: { type: [Boolean, String, Number], default: true },
  falseValue: { type: [Boolean, String, Number], default: false },
  disabled: { type: Boolean, default: false },
  name: { type: String, default: undefined },
  id: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isChecked = () => props.modelValue === props.trueValue;

function onChange(e) {
  const checked = e.target.checked;
  const val = checked ? props.trueValue : props.falseValue;
  emit('update:modelValue', val);
  emit('change', val);
}
</script>

<template>
  <label class="relative inline-flex h-7 w-12 cursor-pointer items-center">
    <input :id="id"
           :name="name"
           type="checkbox"
           class="peer sr-only"
           :checked="isChecked()"
           :disabled="disabled"
           @change="onChange">
    <span class="pointer-events-none block h-full w-full rounded-full bg-slate-300 transition peer-checked:bg-blue-600 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-blue-500/60"></span>
    <span class="pointer-events-none absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
  </label>
</template>

