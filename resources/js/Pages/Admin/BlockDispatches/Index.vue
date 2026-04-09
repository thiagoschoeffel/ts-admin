<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputNumber from '@/components/InputNumber.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import DataTable from '@/components/DataTable.vue';
import BlockDispatchDetailsModal from '@/components/blockDispatches/BlockDispatchDetailsModal.vue';

const props = defineProps({
    dispatches: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.block_dispatches?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.block_dispatches?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.block_dispatches?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.block_dispatches?.view);

const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const productionPointingId = ref(props.filters.production_pointing_id || '');
const period = ref({
    start: props.filters?.period?.from || null,
    end: props.filters?.period?.to || null,
});

const filtering = ref(false);
const submitFilters = () => {
    filtering.value = true;
    router.get(route('block-dispatches.index'), {
        search: search.value,
        production_pointing_id: productionPointingId.value || null,
        period: { from: period.value?.start || null, to: period.value?.end || null },
    }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};

const resetFilters = () => {
    search.value = '';
    productionPointingId.value = '';
    period.value = { start: null, end: null };
    submitFilters();
};

const deleteState = ref({ open: false, processing: false, dispatch: null });
const confirmDelete = (dispatch) => {
    deleteState.value = { open: true, processing: false, dispatch };
};
const performDelete = async () => {
    if (!deleteState.value.dispatch) return;
    deleteState.value.processing = true;
    await router.delete(route('block-dispatches.destroy', deleteState.value.dispatch.id), {
        onSuccess: () => { deleteState.value = { open: false, processing: false, dispatch: null }; },
        onError: () => { deleteState.value.processing = false; },
    });
};

const details = ref({ open: false, dispatchId: null });
const openDetails = (dispatch) => { details.value.dispatchId = dispatch.id; details.value.open = true; };

const columns = [
    {
        header: 'ID',
        key: 'id',
        component: 'button',
        props: (dispatch) => ({
            type: 'button',
            class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
            disabled: !canView.value,
            onClick: canView.value ? () => openDetails(dispatch) : undefined,
        }),
        formatter: (v) => v ?? '—',
    },
    { header: 'Data/Hora', key: 'dispatched_at', formatter: (v) => v ?? '—' },
    { header: 'OF', key: 'manufacturing_order_number', formatter: (v) => v ?? '—' },
    {
        header: 'Requisição',
        key: 'production_pointing_id',
        formatter: (v, row) => row?.sheet_number ? `#${v} (Ficha ${row.sheet_number})` : `#${v}`,
    },
    { header: 'Itens', key: 'items_count', formatter: (v) => v ?? 0 },
];

const actions = computed(() => {
    const acts = [];
    if (canUpdate.value) {
        acts.push({
            key: 'edit',
            label: 'Editar',
            icon: 'pencil',
            component: Link,
            props: (dispatch) => ({
                href: route('block-dispatches.edit', dispatch.id),
                class: 'menu-panel-link',
            }),
        });
    }
    if (canDelete.value) {
        acts.push({
            key: 'delete',
            label: 'Excluir',
            icon: 'trash',
            class: 'menu-panel-link text-rose-600 hover:text-rose-700',
        });
    }
    if (acts.length === 0) {
        acts.push({ key: 'no-actions', label: 'Nenhuma ação disponível', class: 'menu-panel-link pointer-events-none text-slate-400' });
    }
    return acts;
});

const handleTableAction = ({ action, item }) => {
    if (action.key === 'delete') {
        confirmDelete(item);
    }
};
</script>

<template>
    <AdminLayout>

        <Head title="Saída de Blocos" />

        <section class="card space-y-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
                        <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
                        Saída de Blocos
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">Gerencie as saídas (baixas) de blocos.</p>
                </div>
                <Button v-if="canCreate" variant="primary" :href="route('block-dispatches.create')">Nova saída</Button>
            </div>

            <form @submit.prevent="submitFilters" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="form-label">
                        Buscar (OF/ID)
                        <InputText v-model="search" placeholder="Ex.: OF-123 ou 10" />
                    </label>
                    <label class="form-label">
                        Requisição (ID)
                        <InputNumber v-model="productionPointingId" :formatted="true" :precision="0" :min="1" :step="1"
                            placeholder="0" />
                    </label>
                    <label class="form-label">
                        Período (saída)
                        <InputDatePicker v-model="period" :range="true" :withTime="true"
                            placeholder="Selecione o período" />
                    </label>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button type="submit" variant="primary" :loading="filtering">
                        <HeroIcon name="funnel" class="h-5 w-5" />
                        <span v-if="!filtering">Filtrar</span>
                        <span v-else>Filtrando…</span>
                    </Button>
                    <Button type="button" variant="ghost" @click="resetFilters">Limpar filtros</Button>
                </div>
            </form>

            <div class="flex items-center justify-end">
                <PerPageSelector :current="dispatches.per_page ?? dispatches.perPage ?? 10" />
            </div>

            <DataTable :columns="columns" :data="dispatches.data" :actions="actions"
                empty-message="Nenhuma saída encontrada." @action="handleTableAction" />

            <Pagination v-if="dispatches && dispatches.total" :paginator="dispatches" />
        </section>

        <ConfirmModal v-model="deleteState.open" :processing="deleteState.processing" title="Excluir saída de blocos"
            :message="deleteState.dispatch ? `Deseja realmente remover a saída #${deleteState.dispatch.id}?` : ''"
            confirm-text="Excluir" variant="danger" @confirm="performDelete" />

        <BlockDispatchDetailsModal v-model="details.open" :block-dispatch-id="details.dispatchId" />
    </AdminLayout>
</template>
