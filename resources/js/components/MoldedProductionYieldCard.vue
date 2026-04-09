<template>
    <div class="bg-white rounded-lg border border-slate-200 p-6 w-full flex flex-col items-center">
        <h2 class="text-lg font-semibold text-slate-900 mb-2">Aproveitamento da produção dos moldados</h2>
        <p class="text-sm text-slate-600 mb-4">Aproveitamento/Perca</p>
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <span class="text-2xl font-bold text-green-700">{{ fmt(success) }}%</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
                <span class="text-2xl font-bold text-red-700">{{ fmt(fail) }}%</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    produced: { type: Number, required: true },
    scrap: { type: Number, required: true },
});

const total = computed(() => props.produced + props.scrap);
const success = computed(() => total.value > 0 ? (props.produced / total.value) * 100 : 0);
const fail = computed(() => total.value > 0 ? (props.scrap / total.value) * 100 : 0);
const fmt = n => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
</script>

<style scoped></style>
