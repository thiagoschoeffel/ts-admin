<template>
    <div class="bg-white rounded-lg border border-slate-200 p-6 w-full">
        <h2 class="text-lg font-semibold text-slate-900 mb-2">Ranking de refugo dos moldados</h2>
        <p class="text-sm text-slate-600 mb-4">Motivo/Quantidade</p>
        <div v-if="ranking.length === 0" class="text-center py-8">
            <p class="text-slate-500">Nenhum refugo registrado neste período</p>
        </div>
        <div v-else v-for="(item, idx) in ranking" :key="item.code" class="flex items-center mb-2">
            <div class="flex items-center mr-3">
                <span class="text-2xl font-bold text-slate-900">{{ idx + 1 }}º</span>
            </div>
            <div class="flex flex-col">
                <span class="font-semibold text-slate-700">{{ item.code }} - {{ item.reason }}</span>
                <span class="text-lg font-bold text-slate-900">{{ fmt(item.quantity, 0) }} und</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
    data: { type: Array, required: true },
});
const fmt = (n, decimals = 2) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(Number(n || 0));
const ranking = computed(() => {
    return [...props.data]
        .sort((a, b) => b.quantity - a.quantity)
        .slice(0, 3);
});
</script>

<style scoped></style>
