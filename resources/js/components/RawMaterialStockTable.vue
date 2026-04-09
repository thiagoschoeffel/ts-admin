<script setup>
import { ref, watch, computed } from 'vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
    period: { type: Object, required: true },
    data: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const parseNumber = (value) => {
    if (typeof value === 'string') {
        return Number(value.replace(/,/g, '.'));
    }
    return Number(value || 0);
};

const formatNumber = (n, decimals = 2) => {
    const num = parseNumber(n);
    const safeDecimals = Math.max(0, Math.min(20, Number.isFinite(decimals) ? decimals : 2));
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: safeDecimals,
        maximumFractionDigits: safeDecimals
    }).format(num);
};

const formatInteger = (n) => {
    const num = parseNumber(n);
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
};

const columns = [
    { header: 'Tipo', key: 'raw_material_name', class: 'font-medium' },
    { header: 'Inicial (kg)', key: 'initial_kg', formatter: formatNumber, class: 'text-right' },
    { header: 'Entradas (kg)', key: 'input_kg', formatter: formatNumber, class: 'text-right' },
    { header: 'Requisições (kg)', key: 'requested_kg', formatter: formatNumber, class: 'text-right' },
    { header: 'Saldo (kg)', key: 'balance_kg', formatter: formatNumber, class: 'text-right font-semibold text-slate-900' },
];

const totals = computed(() => {
    if (!props.data || props.data.length === 0) return {};
    const sums = {
        initial_kg: props.data.reduce((sum, item) => sum + parseNumber(item.initial_kg), 0),
        input_kg: props.data.reduce((sum, item) => sum + parseNumber(item.input_kg), 0),
        requested_kg: props.data.reduce((sum, item) => sum + parseNumber(item.requested_kg), 0),
        balance_kg: props.data.reduce((sum, item) => sum + parseNumber(item.balance_kg), 0),
    };
    return sums;
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mt-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Estoque atual de matéria-prima</h3>
        <div class="datatable relative">
            <div class="datatable-scroll overflow-x-auto overflow-y-hidden">
                <table class="min-w-full table-auto table">
                    <thead>
                        <tr>
                            <th v-for="column in columns" :key="column.key || column.header" :class="[
                                'dt-cell px-3 py-3 text-left text-sm font-semibold text-slate-600',
                                column.class
                            ]">
                                {{ column.header }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in data" :key="item.raw_material_id">
                            <td v-for="column in columns" :key="column.key || column.header" :class="[
                                'dt-cell px-3 py-3 text-sm text-slate-800',
                                column.class
                            ]">
                                {{ column.formatter ? column.formatter(item[column.key], item) : item[column.key] }}
                            </td>
                        </tr>
                        <tr v-if="!data || data.length === 0">
                            <td :colspan="columns.length" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum
                                registro encontrado.</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50">
                            <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900">
                                TOTAL</td>
                            <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                {{ formatNumber(totals.initial_kg) }}</td>
                            <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                {{ formatNumber(totals.input_kg) }}</td>
                            <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                {{ formatNumber(totals.requested_kg) }}</td>
                            <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                {{ formatNumber(totals.balance_kg) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>
