<script setup>
import VueApexCharts from 'vue3-apexcharts';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { route } from '@/ziggy-client';

const props = defineProps({
    period: { type: Object, default: () => ({ start: null, end: null }) }
});

const dataByDay = ref([]);
const loading = ref(true);

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (props.period.start) params.from = props.period.start;
        if (props.period.end) params.to = props.period.end;
        const res = await axios.get(route('inventory.blocks.produced-by-day'), { params });
        dataByDay.value = res.data.data || [];
    } catch (error) {
        console.error('Erro ao buscar blocos produzidos por dia:', error);
        dataByDay.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch(() => props.period, fetchData, { deep: true });

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    // Aceita apenas formato YYYY-MM-DD ou Date
    let d;
    if (typeof dateStr === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        d = new Date(dateStr + 'T00:00:00');
    } else if (dateStr instanceof Date) {
        d = dateStr;
    } else {
        // Tenta parsear, mas retorna string original se falhar
        d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
    }
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(d);
};

const safeFormatDate = (val) => {
    // Só formata se for data válida, senão retorna string original
    if (typeof val === 'string' && /^\d{2}\/\d{2}\/\d{4}$/.test(val)) return val;
    if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) return formatDate(val);
    return val;
};

const formatNumber = (n) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(n || 0));

const chartSeries = computed(() => [{
    name: 'Blocos Produzidos',
    data: dataByDay.value.map(r => r.total_units)
}]);
const chartCategories = computed(() => dataByDay.value.map(r => formatDate(r.day)));

const chartOptions = computed(() => ({
    chart: {
        type: 'line',
        height: 350,
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    colors: ['#2563eb'],
    dataLabels: { enabled: true, formatter: formatNumber },
    stroke: { curve: 'smooth', width: 3 },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 3 },
    xaxis: {
        categories: chartCategories.value,
        labels: {
            style: { colors: '#64748b', fontSize: '12px' },
            formatter: safeFormatDate,
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
        x: {
            formatter: safeFormatDate,
        },
        y: {
            formatter: value => `${formatNumber(value)} unidades`,
        },
    },
    markers: {
        size: 4,
        colors: ['#2563eb'],
        strokeColors: '#fff',
        strokeWidth: 2,
        hover: { size: 6 },
    },
}));
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Produção de blocos por dia</h3>
        <div v-if="loading" class="py-8 text-center text-slate-500">Carregando...</div>
        <div v-else class="w-full">
            <apexchart type="line" :options="chartOptions" :series="chartSeries" height="350" width="100%" />
        </div>
    </div>
</template>
