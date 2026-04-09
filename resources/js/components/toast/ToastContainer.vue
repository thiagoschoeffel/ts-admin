<script setup>
import { computed } from 'vue';
import { useToasts, removeToast } from '@/components/toast/useToasts';

const { toasts } = useToasts();

const classes = (type) => {
  switch (type) {
    case 'success':
      return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    case 'error':
      return 'border-rose-200 bg-rose-50 text-rose-700';
    case 'warning':
      return 'border-amber-200 bg-amber-50 text-amber-800';
    default:
      return 'border-slate-200 bg-white text-slate-800';
  }
};

const barClass = (type) => {
  switch (type) {
    case 'success':
      return 'bg-emerald-600';
    case 'error':
      return 'bg-rose-600';
    case 'warning':
      return 'bg-amber-600';
    default:
      return 'bg-blue-600';
  }
};
</script>

<template>
  <div class="fixed right-4 top-4 z-[1200] flex w-full max-w-sm flex-col gap-3">
    <div v-for="t in toasts" :key="t.id" class="overflow-hidden rounded-xl border shadow-lg ring-1 ring-slate-900/5" :class="classes(t.type)">
      <div class="flex items-start gap-3 p-3">
        <div class="flex-1">
          <p v-if="t.title" class="text-sm font-semibold">{{ t.title }}</p>
          <p class="text-sm" v-text="t.message" />
        </div>
        <button type="button" class="rounded-md p-1 cursor-pointer" :class="{
          'text-emerald-500 hover:text-emerald-700': t.type === 'success',
          'text-rose-500 hover:text-rose-700': t.type === 'error',
          'text-amber-500 hover:text-amber-700': t.type === 'warning',
          'text-blue-500 hover:text-blue-700': t.type === 'info' || !t.type
        }" @click="removeToast(t.id)">✕</button>
      </div>
      <div class="h-1 w-full" :style="{ animationDuration: (t.duration||4000) + 'ms' }" :class="['toast-progress', barClass(t.type)]"></div>
    </div>
  </div>
</template>

<style scoped>
@keyframes toastprogress { from { width: 100% } to { width: 0% } }
.toast-progress { animation-name: toastprogress; animation-timing-function: linear; animation-fill-mode: forwards; }
</style>

