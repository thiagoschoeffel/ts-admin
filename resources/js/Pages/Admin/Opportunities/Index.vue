
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import Dropdown from '@/components/Dropdown.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import OpportunityDetailsModal from '@/components/opportunities/OpportunityDetailsModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';
import ProgressBar from '@/components/ui/ProgressBar.vue';
import { formatCurrency } from '@/utils/formatters.js';

const props = defineProps({
  opportunities: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const stage = ref(props.filters.stage || '');
// Filtro de período de criação
const createdPeriod = ref({
  start: props.filters.created_from || null,
  end: props.filters.created_to || null,
});

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get('/admin/opportunities', { search: search.value, status: status.value, stage: stage.value, created_from: createdPeriod.value?.start || null, created_to: createdPeriod.value?.end || null }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; status.value = ''; stage.value = ''; createdPeriod.value = { start: null, end: null }; submitFilters(); };

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, opportunity: null });

const confirmDelete = (opportunity) => {
  deleteState.value = { open: true, processing: false, opportunity };
};

const performDelete = async () => {
  if (!deleteState.value.opportunity) return;

  deleteState.value.processing = true;
  try {
    await router.delete(`/admin/opportunities/${deleteState.value.opportunity.id}`, {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, opportunity: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
  }
};

const details = ref({ open: false, opportunityId: null });
const openDetails = (opportunity) => { details.value.opportunityId = opportunity.id; details.value.open = true; };

const getProbabilityColor = (probability) => {
  if (probability >= 80) return 'success';
  if (probability >= 50) return 'warning';
  return 'danger';
};

const columns = [
  {
    header: 'Título',
    key: 'title',
    component: 'button',
    props: (opportunity) => ({
      type: 'button',
      class: 'font-bold text-blue-600 cursor-pointer',
      onClick: () => openDetails(opportunity)
    })
  },
  {
    header: 'Lead',
    key: 'lead',
    formatter: (value) => value?.name || '—'
  },
  {
    header: 'Cliente',
    key: 'client',
    formatter: (value) => value?.name || '—'
  },
  {
    header: 'Data',
    key: 'created_at',
    formatter: (value) => {
      if (!value) return '—';
      const date = new Date(value);
      return date.toLocaleDateString('pt-BR');
    }
  },
  {
    header: 'Etapa',
    key: 'stage',
    formatter: (value) => {
      const stages = {
        new: 'Novo',
        contact: 'Contato',
        proposal: 'Proposta',
        negotiation: 'Negociação',
        won: 'Ganho',
        lost: 'Perdido'
      };
      return stages[value] || value;
    }
  },
  {
    header: 'Probabilidade',
    key: 'probability',
    component: ProgressBar,
    props: (opportunity) => ({
      percentage: opportunity.probability,
      size: 'md',
      color: getProbabilityColor(opportunity.probability),
      showLabel: true,
      animated: true
    })
  },
  {
    header: 'Valor Estimado',
    key: 'expected_value',
    formatter: (value) => formatCurrency(value)
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (opportunity) => ({
      variant: opportunity.status === 'active' ? 'success' : 'danger'
    }),
    formatter: (value) => value === 'active' ? 'Ativa' : 'Inativa'
  },
  {
    header: 'Dono',
    key: 'owner',
    formatter: (value) => value?.name || '—'
  }
];

const actions = computed(() => {
  const acts = [];
  acts.push({
    key: 'edit',
    label: 'Editar',
    icon: 'pencil',
    component: Link,
    props: (opportunity) => ({
      href: route('opportunities.edit', opportunity.id),
      class: 'menu-panel-link'
    })
  });
  acts.push({
    key: 'delete',
    label: 'Excluir',
    icon: 'trash',
    class: 'menu-panel-link text-rose-600 hover:text-rose-700'
  });
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
    <Head title="Oportunidades" />
    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="document-currency-dollar" class="h-7 w-7 text-slate-700" />
            Oportunidades
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie as oportunidades de vendas.</p>
        </div>
        <Button variant="primary" :href="route('opportunities.create')">Nova oportunidade</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar por título
            <InputText v-model="search" placeholder="Digite para buscar" />
          </label>
          <label class="form-label">
            Status
            <InputSelect v-model="status" :options="[
              { value: '', label: 'Todos' },
              { value: 'active', label: 'Ativas' },
              { value: 'inactive', label: 'Inativas' }
            ]" placeholder="" />
          </label>
          <label class="form-label">
            Etapa
            <InputSelect v-model="stage" :options="[
              { value: '', label: 'Todas' },
              { value: 'new', label: 'Novo' },
              { value: 'contact', label: 'Contato' },
              { value: 'proposal', label: 'Proposta' },
              { value: 'negotiation', label: 'Negociação' },
              { value: 'won', label: 'Ganho' },
              { value: 'lost', label: 'Perdido' }
            ]" placeholder="" />
          </label>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Período de criação
            <InputDatePicker v-model="createdPeriod" :range="true" placeholder="Selecione o período" />
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
        <PerPageSelector :current="opportunities.per_page ?? opportunities.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="opportunities.data"
        :actions="actions"
        empty-message="Nenhuma oportunidade encontrada."
        @action="handleTableAction"
      />

      <Pagination v-if="opportunities && opportunities.total" :paginator="opportunities" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir oportunidade"
                  :message="deleteState.opportunity ? `Deseja realmente remover ${deleteState.opportunity.title}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <OpportunityDetailsModal v-model="details.open" :opportunity-id="details.opportunityId" />
  </AdminLayout>
</template>
