<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import InputSelect from '@/components/InputSelect.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import Button from '@/components/Button.vue';
import DataTable from '@/components/DataTable.vue';
import Pagination from '@/components/Pagination.vue';
import { route as ziggyRoute } from '@/ziggy-client';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import InventoryMovementDetailsModal from '@/components/inventory/InventoryMovementDetailsModal.vue';

const props = defineProps({
    rawMaterials: { type: Array, default: () => [] },
    silos: { type: Array, default: () => [] },
    paginator: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, last_page: 1, per_page: 25 }) },
    filters: { type: Object, default: () => ({}) },
});

const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;
const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');

// Permissões específicas para inventory
const canViewInventory = computed(() => {
    if (isAdmin.value) return true;
    return user.value?.permissions?.inventory_movements?.view || false;
});

const canCreateInventory = computed(() => {
    if (isAdmin.value) return true;
    return user.value?.permissions?.inventory_movements?.create || false;
});

const canUpdateInventory = computed(() => {
    if (isAdmin.value) return true;
    return user.value?.permissions?.inventory_movements?.update || false;
});

const filters = ref({
    item_type: props.filters.item_type || '',
    direction: props.filters.direction || '',
});
const period = ref({
    start: props.filters?.from || null,
    end: props.filters?.to || null,
});
const paginator = computed(() => props.paginator || { data: [], total: 0, current_page: 1, last_page: 1, per_page: 25 });
const rows = computed(() => paginator.value.data || []);
const loading = ref(false);

// Estado da modal de detalhes
const details = ref({ open: false, movementId: null });
const openDetails = (movement) => { details.value.movementId = movement.id; details.value.open = true; };

const applyFilters = () => {
    loading.value = true;
    const q = {};
    if (filters.value.item_type) q.item_type = filters.value.item_type;
    if (filters.value.direction) q.direction = filters.value.direction;
    q.from = period.value?.start || null;
    q.to = period.value?.end || null;
    router.get(route('inventory.movements.index'), q, { preserveState: true, replace: true, onFinish: () => { loading.value = false; } });
};

const resetFilters = () => {
    filters.value = { item_type: 'raw_material', direction: '' };
    period.value = { start: null, end: null };
    applyFilters();
};

const columns = [
    {
        header: 'ID', key: 'id', component: 'button', props: (row) => ({
            type: 'button',
            class: (canViewInventory.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900'),
            disabled: !canViewInventory.value,
            onClick: canViewInventory.value ? () => openDetails(row) : undefined
        })
    },
    {
        header: 'Quando', key: 'occurred_at', formatter: (v) => {
            const date = new Date(v);
            return date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    },
    {
        header: 'Item', key: 'item_type', formatter: (v) => {
            const types = {
                'raw_material': 'Matéria-prima',
                'block': 'Bloco',
                'molded': 'Moldado'
            };
            return types[v] || v;
        }
    },
    {
        header: 'Direção', key: 'direction', formatter: (v) => {
            const directions = {
                'in': 'Entrada',
                'out': 'Saída',
                'adjust': 'Ajuste'
            };
            return directions[v] || v;
        }
    },
    {
        header: 'Local', key: 'location_type', formatter: (v, row) => {
            const locationTypes = {
                'silo': 'Silo',
                'almoxarifado': 'Almoxarifado',
                'none': 'Nenhum'
            };
            const typeLabel = locationTypes[row.location_type] || row.location_type;
            return row.location_id ? `${typeLabel} #${row.location_id}` : typeLabel;
        }
    },
    {
        header: 'Quantidade', key: 'quantity', formatter: (v, row) => {
            const formatted = Number(v).toLocaleString('pt-BR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 3
            });
            return `${formatted} ${row.unit}`;
        }
    },
    { header: 'Ref.', key: 'reference_type', formatter: (v, row) => v ? `${String(v).split('\\\\').pop()}#${row.reference_id}` : '—' },
    { header: 'Obs', key: 'notes', formatter: (v) => v || '—' },
];

const actions = computed(() => {
    const acts = [];
    if (canUpdateInventory.value) {
        acts.push({
            key: 'edit',
            label: 'Editar',
            icon: 'pencil',
            component: 'a',
            props: (row) => ({ href: route('inventory.movements.edit', row.id), class: 'menu-panel-link' })
        });
    }
    if (acts.length === 0) {
        acts.push({ key: 'none', label: 'Sem ações', class: 'menu-panel-link pointer-events-none text-slate-400' });
    }
    return acts;
});

const handleAction = async ({ action, item }) => {
    // Ações de edição são tratadas via links diretos
    // Não há mais ações que precisam de tratamento especial
};
</script>

<template>

    <Head title="Movimentos de Estoque" />
    <AdminLayout>
        <section class="card space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
                        <HeroIcon name="command-line" class="h-7 w-7 text-slate-700" />
                        Movimentos de Estoque
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">Filtros e listagem de movimentos do estoque.</p>
                </div>
                <Button v-if="canCreateInventory" variant="primary" :href="route('inventory.movements.create')">Novo
                    movimento</Button>
            </div>

            <form @submit.prevent="applyFilters" class="space-y-6">
                <div class="flex flex-col sm:flex-row gap-4 w-full">
                    <label class="form-label w-full sm:w-1/3">
                        Item
                        <InputSelect v-model="filters.item_type" :options="[
                            { value: '', label: 'Todos' },
                            { value: 'raw_material', label: 'Matéria-prima' },
                            { value: 'block', label: 'Bloco' },
                            { value: 'molded', label: 'Moldado' },
                        ]" :placeholder="null" />
                    </label>
                    <label class="form-label w-full sm:w-1/3">
                        Direção
                        <InputSelect v-model="filters.direction" :options="[
                            { value: '', label: 'Todas' },
                            { value: 'in', label: 'Entrada' },
                            { value: 'out', label: 'Saída' },
                            { value: 'adjust', label: 'Ajuste' },
                        ]" :placeholder="null" />
                    </label>
                    <label class="form-label w-full sm:w-1/3">
                        Período
                        <InputDatePicker v-model="period" :range="true" />
                    </label>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :loading="loading">
                        <HeroIcon name="funnel" class="h-5 w-5" />
                        <span v-if="!loading">Filtrar</span>
                        <span v-else>Filtrando…</span>
                    </Button>
                    <Button type="button" variant="ghost" @click="resetFilters">Limpar filtros</Button>
                </div>
            </form>

            <div class="flex items-center justify-end">
                <PerPageSelector :current="paginator.per_page ?? paginator.perPage ?? 25" />
            </div>

            <DataTable :columns="columns" :data="rows" row-key="id" :actions="actions" @action="handleAction" />
            <Pagination v-if="paginator && paginator.total" :paginator="paginator" />
        </section>

        <InventoryMovementDetailsModal v-model="details.open" :movement-id="details.movementId" />
    </AdminLayout>
</template>
