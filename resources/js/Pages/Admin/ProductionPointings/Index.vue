<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import ProductionPointingDetailsModal from '@/components/productionPointings/ProductionPointingDetailsModal.vue';
import ProductionPointingModal from '@/components/productionPointings/ProductionPointingModal.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  productionPointings: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  blockTypes: { type: Array, default: () => [] },
  moldTypes: { type: Array, default: () => [] },
  operators: { type: Array, default: () => [] },
  silos: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.production_pointings?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.production_pointings?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.production_pointings?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.production_pointings?.view);

const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const period = ref({
  start: props.filters?.period?.from || null,
  end: props.filters?.period?.to || null,
});

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get(route('production-pointings.index'), {
    search: search.value,
    status: status.value,
    period: {
      from: period.value?.start || null,
      to: period.value?.end || null,
    },
  }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => {
  search.value = '';
  status.value = '';
  period.value = { start: null, end: null };
  submitFilters();
};

const deleteState = ref({ open: false, processing: false, productionPointing: null });

const confirmDelete = (productionPointing) => {
  deleteState.value = { open: true, processing: false, productionPointing };
};

const performDelete = async () => {
  if (!deleteState.value.productionPointing) return;

  deleteState.value.processing = true;
  try {
    await router.delete(route('production-pointings.destroy', deleteState.value.productionPointing.id), {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, productionPointing: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
    console.error('Erro ao excluir apontamento de produção:', error);
  }
};

const details = ref({ open: false, productionPointingId: null });
const openDetails = (productionPointing) => { details.value.productionPointingId = productionPointing.id; details.value.open = true; };

const formatQuantity = (value) => {
  if (value === null || value === undefined) return '—';
  const number = Number(value);
  if (Number.isNaN(number)) return String(value);
  return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number);
};

const columns = [
  {
    header: 'ID',
    key: 'id',
    component: 'button',
    props: (productionPointing) => ({
      type: 'button',
      class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(productionPointing)
    })
  },
  {
    header: 'Ficha',
    key: 'sheet_number',
    formatter: (value) => value ?? '—',
  },
  {
    header: 'Matéria-prima',
    key: 'raw_material',
    formatter: (value) => value ?? '—',
  },
  {
    header: 'Quantidade (kg)',
    key: 'quantity',
    formatter: (value) => formatQuantity(value),
  },
  {
    header: 'Início',
    key: 'started_at',
    formatter: (value) => value ?? '—',
  },
  {
    header: 'Fim',
    key: 'ended_at',
    formatter: (value) => value ?? '—',
  },
  {
    header: 'Operadores',
    key: 'operators_count',
    formatter: (value) => value ?? 0,
  },
  {
    header: 'Silos',
    key: 'silos_count',
    formatter: (value) => value ?? 0,
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (productionPointing) => ({
      variant: productionPointing.status === 'active' ? 'success' : 'danger'
    }),
    formatter: (value) => value === 'active' ? 'Ativo' : 'Inativo'
  }
];

const actions = computed(() => {
  const acts = [];
  if (canUpdate.value) {
    acts.push({
      key: 'edit',
      label: 'Editar',
      icon: 'pencil',
      component: Link,
      props: (productionPointing) => ({
        href: route('production-pointings.edit', productionPointing.id),
        class: 'menu-panel-link'
      })
    });
  }
  if (canDelete.value) {
    acts.push({
      key: 'delete',
      label: 'Excluir',
      icon: 'trash',
      class: 'menu-panel-link text-rose-600 hover:text-rose-700'
    });
  }
  if (acts.length === 0) {
    acts.push({
      key: 'no-actions',
      label: 'Nenhuma ação disponível',
      class: 'menu-panel-link pointer-events-none text-slate-400'
    });
  }
  return acts;
});

// Inline action button before dropdown
const inlineActions = (item) => ([
  {
    key: 'details',
    label: 'Apontamento de produção',
    icon: 'arrow-down-tray',
    variant: 'primary',
  }
]);

// New empty modal state (Apontamento de produção)
const ppModal = ref({ open: false, productionPointingId: null, requestSheetNumber: null });

const handleTableAction = ({ action, item }) => {
  if (action.key === 'details') {
    ppModal.value = { open: true, productionPointingId: item.id, requestSheetNumber: item.sheet_number };
    return;
  }
  if (action.key === 'delete') {
    confirmDelete(item);
  }
};
</script>

<template>
  <AdminLayout>
    <Head title="Apontamentos de Produção" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Apontamentos de Produção
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os apontamentos de produção da industrialização EPS.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('production-pointings.create')">Novo apontamento</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar por ficha ou matéria-prima
            <InputText v-model="search" placeholder="Digite para buscar" />
          </label>
          <label class="form-label">
            Status
            <InputSelect v-model="status" :options="[
              { value: '', label: 'Todos' },
              { value: 'active', label: 'Ativos' },
              { value: 'inactive', label: 'Inativos' }
            ]" placeholder="" />
          </label>
          <label class="form-label">
            Período
            <InputDatePicker v-model="period" :range="true" :withTime="true" placeholder="Selecione o período" />
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
        <PerPageSelector :current="productionPointings.per_page ?? productionPointings.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="productionPointings.data"
        :inline-actions="inlineActions"
        :actions="actions"
        empty-message="Nenhum apontamento encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="productionPointings && productionPointings.total" :paginator="productionPointings" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir apontamento"
                  :message="deleteState.productionPointing ? `Deseja realmente remover ${deleteState.productionPointing.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <ProductionPointingDetailsModal
      v-model="details.open"
      :production-pointing-id="details.productionPointingId"
    />

    <ProductionPointingModal
      v-model="ppModal.open"
      :production-pointing-id="ppModal.productionPointingId"
      :block-types="blockTypes"
      :mold-types="moldTypes"
      :operators="operators"
      :silos="silos"
      :request-sheet-number="ppModal.requestSheetNumber"
    />
  </AdminLayout>
</template>
