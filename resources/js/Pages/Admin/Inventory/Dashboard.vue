// ...existing code...
<script setup>
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import InputDatePicker from '@/components/InputDatePicker.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import ReservationsBarChart from '@/components/ReservationsBarChart.vue';
import ProductionByMaterialBarChart from '@/components/ProductionByMaterialBarChart.vue';
import BlocksProducedByDayChart from '@/components/BlocksProducedByDayChart.vue';
import BlockProductionTable from '@/components/BlockProductionTable.vue';
import MoldedProductionAndScrapChart from '@/components/MoldedProductionAndScrapChart.vue';
import MoldedProductionRanking from '@/components/MoldedProductionRanking.vue';
import MoldedProductionYieldCard from '@/components/MoldedProductionYieldCard.vue';
import RawMaterialStockTable from '@/components/RawMaterialStockTable.vue';
import { useToasts } from '@/components/toast/useToasts.js';
import axios from 'axios';
import { route } from '@/ziggy-client';

const fmt = (n, decimals = 2) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(Number(n || 0));
const { error: toastError } = useToasts();

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, required: true },
    siloLoads: { type: Array, default: () => [] },
});

const todayIso = new Date().toISOString().split('T')[0];
const thirtyDaysAgoIso = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
const initialStart = props.filters?.from || thirtyDaysAgoIso;
const initialEnd = props.filters?.to || todayIso;

const period = ref({
    start: initialStart,
    end: initialEnd
});
// Período aplicado (usado pelos componentes filhos)
const appliedPeriod = ref({
    start: initialStart,
    end: initialEnd
});
const loading = ref(false);
const summary = ref(props.summary);
const silos = ref(props.siloLoads);

const rawMaterialStock = ref([]);
const rawMaterialStockLoading = ref(false);

const fetchRawMaterialStock = async () => {
    rawMaterialStockLoading.value = true;
    try {
        const params = {};
        if (appliedPeriod.value.start) params.from = appliedPeriod.value.start;
        if (appliedPeriod.value.end) params.to = appliedPeriod.value.end;
        const res = await axios.get(route('inventory.raw-material-stock'), { params });
        let data = res.data?.data;
        if (Array.isArray(data)) {
            rawMaterialStock.value = data;
        } else if (data && typeof data === 'object') {
            rawMaterialStock.value = Object.values(data);
        } else {
            rawMaterialStock.value = [];
        }
    } catch (error) {
        console.error('Erro ao buscar estoque de matéria-prima:', error);
        rawMaterialStock.value = [];
    } finally {
        rawMaterialStockLoading.value = false;
    }
};

const blockStock = ref([]);
const blockStockLoading = ref(false);

const fetchBlockStock = async () => {
    blockStockLoading.value = true;
    try {
        const params = {};
        if (appliedPeriod.value.start) params.from = appliedPeriod.value.start;
        if (appliedPeriod.value.end) params.to = appliedPeriod.value.end;
        const res = await axios.get(route('inventory.block-stock'), { params });
        let data = res.data?.data;
        if (Array.isArray(data)) {
            blockStock.value = data;
        } else if (data && typeof data === 'object') {
            blockStock.value = Object.values(data);
        } else {
            blockStock.value = [];
        }
    } catch (error) {
        console.error('Erro ao buscar estoque de blocos:', error);
        blockStock.value = [];
    } finally {
        blockStockLoading.value = false;
    }
};

const moldedStock = ref([]);
const moldedStockLoading = ref(false);

const fetchMoldedStock = async () => {
    moldedStockLoading.value = true;
    try {
        const params = {};
        if (appliedPeriod.value.start) params.from = appliedPeriod.value.start;
        if (appliedPeriod.value.end) params.to = appliedPeriod.value.end;
        const res = await axios.get(route('inventory.molded-stock'), { params });
        let data = res.data?.data;
        if (Array.isArray(data)) {
            moldedStock.value = data;
        } else if (data && typeof data === 'object') {
            moldedStock.value = Object.values(data);
        } else {
            moldedStock.value = [];
        }
    } catch (error) {
        console.error('Erro ao buscar estoque de moldados:', error);
        moldedStock.value = [];
    } finally {
        moldedStockLoading.value = false;
    }
};

const fetchSummary = async () => {
    try {
        const params = {};
        if (appliedPeriod.value.start) params.from = appliedPeriod.value.start;
        if (appliedPeriod.value.end) params.to = appliedPeriod.value.end;
        const res = await axios.get(route('inventory.summary'), { params });
        // Corrige para aceitar tanto res.data quanto res.data.data
        if (res.data && typeof res.data === 'object') {
            if (res.data.data && typeof res.data.data === 'object') {
                summary.value = res.data.data;
            } else {
                summary.value = res.data;
            }
        } else {
            summary.value = {};
        }
    } catch (error) {
        console.error('Erro ao buscar resumo:', error);
        summary.value = {};
    }
};

const fetchSiloLoads = async () => {
    try {
        const params = {};
        if (appliedPeriod.value.start) params.from = appliedPeriod.value.start;
        if (appliedPeriod.value.end) params.to = appliedPeriod.value.end;
        const res = await axios.get(route('inventory.silos.load'), { params });
        silos.value = res.data?.data || [];
    } catch (error) {
        console.error('Erro ao buscar cargas dos silos:', error);
        silos.value = [];
    }
};

const totalSilos = computed(() => silos.value.length);
const totalMateriaisEmSilos = computed(() => silos.value.reduce((acc, s) => acc + (s.materials?.length || 0), 0));
const totalCargaSilos = computed(() => silos.value.reduce((acc, s) => acc + s.materials?.reduce((sum, m) => sum + m.balance_kg, 0) || 0, 0));

const totalInitial = computed(() => {
    if (!Array.isArray(blockStock.value)) return 0;
    return blockStock.value.reduce((sum, item) => sum + (item.initial_units || 0), 0);
});
const totalInput = computed(() => {
    if (!Array.isArray(blockStock.value)) return 0;
    return blockStock.value.reduce((sum, item) => sum + (item.input_units || 0), 0);
});
const totalOutput = computed(() => {
    if (!Array.isArray(blockStock.value)) return 0;
    return blockStock.value.reduce((sum, item) => sum + (item.output_units || 0), 0);
});
const totalBalance = computed(() => {
    if (!Array.isArray(blockStock.value)) return 0;
    return blockStock.value.reduce((sum, item) => sum + (item.balance_units || 0), 0);
});
const totalCubicMeters = computed(() => {
    if (!Array.isArray(blockStock.value)) return 0;
    return blockStock.value.reduce((sum, item) => sum + (item.cubic_meters || 0), 0);
});

const moldedTotalInitial = computed(() => {
    if (!Array.isArray(moldedStock.value)) return 0;
    return moldedStock.value.reduce((sum, item) => sum + (item.initial_units || 0), 0);
});
const moldedTotalInput = computed(() => {
    if (!Array.isArray(moldedStock.value)) return 0;
    return moldedStock.value.reduce((sum, item) => sum + (item.input_units || 0), 0);
});
const moldedTotalOutput = computed(() => {
    if (!Array.isArray(moldedStock.value)) return 0;
    return moldedStock.value.reduce((sum, item) => sum + (item.output_units || 0), 0);
});
const moldedTotalBalance = computed(() => {
    if (!Array.isArray(moldedStock.value)) return 0;
    return moldedStock.value.reduce((sum, item) => sum + (item.balance_units || 0), 0);
});

const fetchData = () => {
    // Validar período máximo de 60 dias
    const start = new Date(period.value.start);
    const end = new Date(period.value.end);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 60) {
        toastError('O período selecionado não pode exceder 60 dias. O filtro foi redefinido para os últimos 30 dias.');

        // Resetar para últimos 30 dias
        const hoje = new Date();
        const trintaDiasAtras = new Date(hoje);
        trintaDiasAtras.setDate(hoje.getDate() - 30);

        period.value = {
            start: trintaDiasAtras.toISOString().split('T')[0],
            end: hoje.toISOString().split('T')[0]
        };

        return;
    }

    // Aplicar o período selecionado
    appliedPeriod.value = { ...period.value };

    loading.value = true;
    // Atualizar todos os dados
    Promise.all([
        fetchSummary(),
        fetchRawMaterialStock(),
        fetchBlockStock(),
        fetchMoldedStock(),
        fetchSiloLoads()
    ]).finally(() => {
        loading.value = false;
    });
};

// Removido watcher automático - dados só atualizam ao clicar em "Atualizar"
// watch(period, () => {
//     fetchSummary();
//     fetchRawMaterialStock();
//     fetchBlockStock();
//     fetchMoldedStock();
//     fetchSiloLoads();
// }, { deep: true });

onMounted(() => {
    fetchData();
});

// Fallback para dados do gráfico de moldados
const moldedChartData = ref([]);
function onMoldedChartUpdate(data) {
    moldedChartData.value = Array.isArray(data) ? data : [];
}
const moldedProducedFromChart = computed(() => {
    if (!moldedChartData.value.length) return 0;
    return moldedChartData.value.reduce((sum, d) => sum + (Number(d.total_produced) || 0), 0);
});
const moldedScrapFromChart = computed(() => {
    if (!moldedChartData.value.length) return 0;
    return moldedChartData.value.reduce((sum, d) => sum + (Number(d.total_scrap) || 0), 0);
});
</script>

<template>

    <Head title="Estoque - Resumo" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Estoque - Resumo</h1>
                    <p class="text-sm text-slate-600 mt-1">Visão geral dos movimentos e cargas de estoque</p>
                </div>
                <div class="flex items-center gap-3">
                    <InputDatePicker v-model="period" range placeholder="Período" />
                    <Button :disabled="loading" :loading="loading" @click="fetchData">
                        <HeroIcon name="arrow-path" class="h-4 w-4 mr-2" />
                        Atualizar
                    </Button>
                </div>
            </div>

            <!-- Cards de Resumo -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <!-- Entrada MP -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Entrada MP</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.raw_material_input_kg || 0) }}
                            </p>
                            <p class="text-xs text-slate-500">kg</p>
                        </div>
                    </div>
                </div>
                <!-- Consumo MP -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Consumo MP</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.raw_material_consumed_kg || 0)
                                }}</p>
                            <p class="text-xs text-slate-500">kg</p>
                        </div>
                    </div>
                </div>
                <!-- Produção Blocos -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Produção Blocos</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.blocks_produced_units || 0, 0)
                                }}</p>
                            <p class="text-xs text-slate-500">unidades</p>
                        </div>
                    </div>
                </div>
                <!-- Refugos -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Refugos Blocos</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.block_loss_units || 0, 0) }}</p>
                            <p class="text-xs text-slate-500">unidades</p>
                        </div>
                    </div>
                </div>
                <!-- Refugos kg -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Refugos Blocos</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.block_loss_kg || 0) }}</p>
                            <p class="text-xs text-slate-500">kg</p>
                        </div>
                    </div>
                </div>
                <!-- Produção Blocos m³ -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Produção Blocos m³</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.blocks_produced_m3 || 0) }}</p>
                            <p class="text-xs text-slate-500">m³</p>
                        </div>
                    </div>
                </div>
                <!-- MP Virgem para Blocos -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">MP Virgem p/ Blocos</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.virgin_mp_kg_for_blocks || 0) }}
                            </p>
                            <p class="text-xs text-slate-500">kg</p>
                        </div>
                    </div>
                </div>
                <!-- MP Reciclada para Blocos -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">MP Reciclada p/ Blocos</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.recycled_mp_kg_for_blocks || 0)
                                }}
                            </p>
                            <p class="text-xs text-slate-500">kg</p>
                        </div>
                    </div>
                </div>
                <!-- Produção Moldados -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Produção Moldados</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.molded_produced_units || 0, 0)
                                }}</p>
                            <p class="text-xs text-slate-500">unidades</p>
                        </div>
                    </div>
                </div>
                <!-- Refugos Moldados -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Refugos Moldados</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.molded_loss_units || 0, 0) }}
                            </p>
                            <p class="text-xs text-slate-500">unidades</p>
                        </div>
                    </div>
                </div>
                <!-- MP Virgem p/ Moldados -->
                <div class="bg-white rounded-lg border border-slate-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">MP Virgem p/ Moldados</p>
                            <p class="text-2xl font-bold text-slate-900">{{ fmt(summary.virgin_mp_kg_for_molded || 0) }}
                            </p>
                            <p class="text-xs text-slate-500">kg</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Reservas de Matéria-Prima -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <ReservationsBarChart class="xl:col-span-1" :period="appliedPeriod" />
                <ProductionByMaterialBarChart class="xl:col-span-1" :period="appliedPeriod" />
            </div>

            <!-- Gráfico de Produção de Blocos por Dia -->
            <BlocksProducedByDayChart :period="appliedPeriod" />

            <!-- Tabela de Produção de Blocos por Tipo e Dimensões -->
            <BlockProductionTable :period="appliedPeriod" />

            <!-- Gráfico de Produção de Moldados por Dia -->
            <MoldedProductionAndScrapChart :period="appliedPeriod" @update:data="onMoldedChartUpdate" />

            <!-- Ranking de Refugos dos Moldados + Card de Aproveitamento -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="xl:col-span-1">
                    <MoldedProductionRanking :data="summary.molded_loss_ranking || []" />
                </div>
                <div class="xl:col-span-1">
                    <MoldedProductionYieldCard :produced="summary.molded_produced_units || 0"
                        :scrap="summary.molded_loss_units || 0" />
                </div>
            </div>
        </div>

        <!-- Cargas por Silo -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 mt-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Cargas por silo</h2>
                    <p class="text-sm text-slate-600 mt-1">Materiais armazenados atualmente nos silos</p>
                </div>
            </div>

            <div v-if="silos.length === 0" class="text-center py-8">
                <p class="text-slate-500">Nenhum silo cadastrado</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="silo in silos" :key="silo.silo_id" class="border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-slate-900">{{ silo.silo_name }}</h3>
                        <span class="text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded">
                            {{ silo.materials?.length || 0 }} materiais
                        </span>
                    </div>

                    <div v-if="!silo.materials || silo.materials.length === 0"
                        class="text-center py-4 text-slate-500 text-sm">
                        Sem carga registrada
                    </div>

                    <div v-else class="space-y-2">
                        <div v-for="material in silo.materials" :key="material.raw_material_id"
                            class="flex items-center justify-between py-2 px-3 bg-slate-50 rounded">
                            <span class="text-sm text-slate-700">{{ material.raw_material_name }}</span>
                            <span class="text-sm text-slate-900">
                                {{ fmt(material.balance_kg) }} kg
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- fechamento da div removido para corrigir erro de tag -->

        <!-- Tabela de estoque atual de matéria-prima -->
        <RawMaterialStockTable :period="appliedPeriod" :data="rawMaterialStock" :loading="rawMaterialStockLoading" />

        <!-- Tabela de estoque de blocos -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mt-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Estoque de blocos</h3>
            <div class="datatable relative">
                <div class="datatable-scroll overflow-auto">
                    <table class="min-w-full table-auto table">
                        <thead>
                            <tr>
                                <th class="dt-cell px-3 py-3 text-left text-sm font-semibold text-slate-600">Tipo</th>
                                <th class="dt-cell px-3 py-3 text-left text-sm font-semibold text-slate-600">Altura (mm)
                                </th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Inicial
                                    (und)</th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Entrada
                                    (und)</th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Saída
                                    (und)
                                </th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Saldo
                                    (und)
                                </th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Metros
                                    Cúbicos (m³)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in blockStock" :key="`${item.type}-${item.height_mm}`">
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 font-medium">{{ item.type }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800">{{ item.height_mm }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 text-right">{{
                                    fmt(item.initial_units, 0) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 text-right">{{ fmt(item.input_units,
                                    0) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 text-right">{{
                                    fmt(item.output_units, 0) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-right font-semibold text-slate-900">{{
                                    fmt(item.balance_units, 0) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 text-right">{{
                                    fmt(item.cubic_meters) }}</td>
                            </tr>
                            <tr v-if="!blockStock || blockStock.length === 0">
                                <td :colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum registro
                                    encontrado.</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50">
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900">TOTAL</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900">-</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(totalInitial) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(totalInput) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(totalOutput) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(totalBalance) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(totalCubicMeters) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabela de estoque de moldados -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mt-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Estoque de Moldados</h3>
            <div class="datatable relative">
                <div class="datatable-scroll overflow-auto">
                    <table class="min-w-full table-auto table">
                        <thead>
                            <tr>
                                <th class="dt-cell px-3 py-3 text-left text-sm font-semibold text-slate-600">Tipo</th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Inicial
                                    (und)</th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Entrada
                                    (und)</th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Saída
                                    (und)
                                </th>
                                <th class="dt-cell px-3 py-3 text-right text-sm font-semibold text-slate-600">Saldo
                                    (und)
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in moldedStock" :key="item.type">
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 font-medium">{{ item.type }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 text-right">{{
                                    fmt(item.initial_units, 0) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 text-right">{{ fmt(item.input_units,
                                    0) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-slate-800 text-right">{{
                                    fmt(item.output_units, 0) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm text-right font-semibold text-slate-900">{{
                                    fmt(item.balance_units, 0) }}</td>
                            </tr>
                            <tr v-if="!moldedStock || moldedStock.length === 0">
                                <td :colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum registro
                                    encontrado.</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50">
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900">TOTAL</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(moldedTotalInitial) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(moldedTotalInput) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(moldedTotalOutput) }}</td>
                                <td class="dt-cell px-3 py-3 text-sm font-semibold text-slate-900 text-right">{{
                                    fmt(moldedTotalBalance) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
