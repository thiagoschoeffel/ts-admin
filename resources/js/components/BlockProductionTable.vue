<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { route } from '@/ziggy-client';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
    period: { type: Object, default: () => ({ start: null, end: null }) }
});

const data = ref([]);
const loading = ref(true);
const emptyMessage = 'Nenhum registro encontrado.';

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (props.period.start) params.from = props.period.start;
        if (props.period.end) params.to = props.period.end;
        const res = await axios.get(route('inventory.block.production-by-type-and-dimensions'), { params });
        // Add unique key to each item for DataTable
        data.value = (res.data.data || []).map(item => ({
            ...item,
            uniqueKey: `${item.block_type_name}-${item.length_mm}-${item.width_mm}-${item.height_mm}`
        }));
    } catch (error) {
        console.error('Erro ao buscar produção de blocos por tipo e dimensões:', error);
        data.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch(() => props.period, fetchData, { deep: true });

// Computed property para calcular os totais
const totals = computed(() => {
    if (!data.value || data.value.length === 0) return {};

    return {
        total_units: data.value.reduce((sum, item) => sum + parseNumber(item.total_units), 0),
        total_m3: data.value.reduce((sum, item) => sum + parseNumber(item.total_m3), 0),
        virgin_mp_kg: data.value.reduce((sum, item) => sum + parseNumber(item.virgin_mp_kg), 0),
        recycled_mp_kg: data.value.reduce((sum, item) => sum + parseNumber(item.recycled_mp_kg), 0)
    };
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
    const num = Number(n || 0);
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
};

const columns = [
    {
        header: 'Tipo de Bloco',
        key: 'block_type_name',
        class: 'font-medium text-left'
    },
    {
        header: 'Comprimento (mm)',
        key: 'length_mm',
        formatter: formatInteger,
        class: 'text-center text-left'
    },
    {
        header: 'Largura (mm)',
        key: 'width_mm',
        formatter: formatInteger,
        class: 'text-center text-left'
    },
    {
        header: 'Altura (mm)',
        key: 'height_mm',
        formatter: formatInteger,
        class: 'text-center text-left'
    },
    {
        header: 'Quantidade (un)',
        key: 'total_units',
        formatter: formatInteger,
        class: 'text-right font-medium'
    },
    {
        header: 'Volume (m³)',
        key: 'total_m3',
        formatter: (value) => formatNumber(value, 3),
        class: 'text-right'
    },
    {
        header: 'MP Virgem (kg)',
        key: 'virgin_mp_kg',
        formatter: formatNumber,
        class: 'text-right'
    },
    {
        header: 'MP Reciclada (kg)',
        key: 'recycled_mp_kg',
        formatter: formatNumber,
        class: 'text-right'
    }
];
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Produção de blocos por tipo e dimensões</h3>
        <div v-if="loading" class="py-8 text-center text-slate-500">Carregando...</div>
        <div v-else>
            <div class="datatable relative">
                <div class="datatable-scroll overflow-x-auto overflow-y-hidden">
                    <table class="min-w-full table-auto table">
                        <thead>
                            <tr>
                                <th v-for="column in columns" :key="column.key || column.header" :class="[
                                    'dt-cell px-3 py-3 text-left text-sm font-semibold text-slate-800',
                                    column.class
                                ]">
                                    {{ column.header }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in data" :key="item.uniqueKey">
                                <td v-for="column in columns" :key="column.key || column.header" :class="[
                                    'dt-cell px-3 py-3 text-sm text-slate-800',
                                    column.class
                                ]">
                                    <template v-if="column.cellRenderer">
                                        <component :is="column.cellRenderer(item, data.indexOf(item))" />
                                    </template>
                                    <component v-else-if="column.component" :is="column.component"
                                        v-bind="column.props ? column.props(item) : {}">
                                        {{ column.formatter ? column.formatter(item[column.key], item) :
                                            item[column.key] }}
                                    </component>
                                    <template v-else>
                                        {{ column.formatter ? column.formatter(item[column.key], item) :
                                            item[column.key] }}
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="!data || data.length === 0">
                                <td :colspan="columns.length" class="px-4 py-6 text-center text-sm text-slate-800">{{
                                    emptyMessage }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="data && data.length > 0">
                            <tr class="bg-slate-50">
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900">
                                    TOTAL
                                </td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-center">
                                    -
                                </td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-center">
                                    -
                                </td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-center">
                                    -
                                </td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                    {{ formatInteger(totals.total_units) }}
                                </td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                    {{ formatNumber(totals.total_m3, 3) }}
                                </td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                    {{ formatNumber(totals.virgin_mp_kg) }}
                                </td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">
                                    {{ formatNumber(totals.recycled_mp_kg) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.table {
    border-collapse: separate;
    border-spacing: 0;
}

.dt-cell {
    white-space: nowrap;
    text-align: left;
    vertical-align: middle;
}
</style>
