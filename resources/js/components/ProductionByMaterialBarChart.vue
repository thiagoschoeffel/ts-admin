<script setup>
import VueApexCharts from 'vue3-apexcharts';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { route } from '@/ziggy-client';

const props = defineProps({
    period: { type: Object, default: () => ({ start: null, end: null }) }
});

const dataByMaterial = ref([]);
const loading = ref(true);

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (props.period.start) params.from = props.period.start;
        if (props.period.end) params.to = props.period.end;
        const res = await axios.get(route('inventory.production.kg-by-material-type'), { params });
        dataByMaterial.value = res.data.data || [];
    } catch (error) {
        console.error('Erro ao buscar produção (kg) por tipo de matéria-prima:', error);
        dataByMaterial.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch(() => props.period, fetchData, { deep: true });

const formatNumber = (n) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));

const chartSeries = computed(() => [{
    name: 'Produzido (kg)',
    data: dataByMaterial.value.map(r => r.total_kg)
}]);
const chartCategories = computed(() => dataByMaterial.value.map(r => r.raw_material_name));

const chartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '50%',
            endingShape: 'rounded',
            distributed: true,
        }
    },
    colors: ['#60a5fa', '#111827', '#4ade80', '#fb923c', '#6366f1', '#f472b6', '#e11d48', '#7c3aed', '#059669', '#dc2626', '#0891b2', '#ca8a04', '#be123c', '#7c2d12'],
    dataLabels: {
        enabled: true,
        formatter: formatNumber,
        style: { fontWeight: 'bold', colors: ['#111827'] }
    },
    xaxis: {
        categories: chartCategories.value,
        labels: {
            style: { colors: '#64748b', fontSize: '12px' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#64748b', fontSize: '12px' },
            formatter: formatNumber,
        },
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: value => `${formatNumber(value)} kg`,
        },
    },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 3 },
}));
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Produção por tipo de matéria-prima</h3>
        <div v-if="loading" class="py-8 text-center text-slate-500">Carregando...</div>
        <div v-else class="w-full">
            <apexchart type="bar" :options="chartOptions" :series="chartSeries" height="350" width="100%" />
        </div>
    </div>
</template>
