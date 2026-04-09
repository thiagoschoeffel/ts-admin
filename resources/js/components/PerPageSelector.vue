<script setup>
import { onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import InputSelect from './InputSelect.vue';

const props = defineProps({
  current: { type: Number, default: 10 },
  options: { type: Array, default: () => [10, 25, 50, 100] },
  remember: { type: Boolean, default: true },
  paramName: { type: String, default: 'per_page' },
  label: { type: String, default: 'Itens por página' },
});

const allowed = computed(() => props.options.map(n => Number(n)).filter(n => Number.isFinite(n)));
const value = computed(() => allowed.value.includes(Number(props.current)) ? Number(props.current) : 10);

function storageKey() {
  try { return `dt:per_page:${props.paramName}:${window.location.pathname}`; }
  catch { return `dt:per_page:${props.paramName}:generic`; }
}

function updatePerPage(newValue) {
  const n = Number(newValue);
  if (!allowed.value.includes(n)) return;
  try {
    if (props.remember && typeof localStorage !== 'undefined') {
      localStorage.setItem(storageKey(), String(n));
    }
  } catch (_) {}

  try {
    const url = new URL(window.location.href);
    url.searchParams.set(props.paramName, String(n));
    const params = Object.fromEntries(url.searchParams.entries());
    router.get(url.pathname, params, { preserveScroll: true, replace: true });
  } catch (_) {
    const q = {}; q[props.paramName] = String(n);
    router.get(window.location.pathname || '', q, { preserveScroll: true, replace: true });
  }
}

onMounted(() => {
  // If URL has no per_page but we remember a valid one, apply it
  try {
    const url = new URL(window.location.href);
    const hasParam = url.searchParams.has(props.paramName);
    if (!hasParam && props.remember && typeof localStorage !== 'undefined') {
      const saved = Number(localStorage.getItem(storageKey()));
      if (allowed.value.includes(saved) && saved !== value.value) {
        url.searchParams.set(props.paramName, String(saved));
        const params = Object.fromEntries(url.searchParams.entries());
        router.get(url.pathname, params, { preserveScroll: true, replace: true });
      }
    }
  } catch (_) {}
});
</script>

<template>
  <div class="flex items-center gap-2">
    <label for="per-page-top" class="text-sm font-semibold text-slate-600 whitespace-nowrap">{{ label }}</label>
    <InputSelect
      id="per-page-top"
      aria-label="Itens por página"
      :model-value="value"
      :options="allowed.map(n => ({ value: n, label: String(n) }))"
      :placeholder="''"
      size="sm"
      class="w-24"
      @change="(e) => updatePerPage(e.target.value)"
    />
  </div>
</template>

<style scoped>
</style>

