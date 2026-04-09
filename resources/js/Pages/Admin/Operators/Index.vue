<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import OperatorDetailsModal from '@/components/operators/OperatorDetailsModal.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  operators: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  sectors: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.operators?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.operators?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.operators?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.operators?.view);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const sectorId = ref(props.filters.sector_id || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get(route('operators.index'), { search: search.value, sector_id: sectorId.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; sectorId.value = ''; submitFilters(); };

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, operator: null });

const confirmDelete = (operator) => {
  deleteState.value = { open: true, processing: false, operator };
};

const performDelete = async () => {
  if (!deleteState.value.operator) return;

  deleteState.value.processing = true;
  try {
    await router.delete(route('operators.destroy', deleteState.value.operator.id), {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, operator: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
    console.error('Erro ao excluir operador:', error);
  }
};

// Estado para modal de detalhes
const details = ref({ open: false, operatorId: null });
const openDetails = (operator) => { details.value.operatorId = operator.id; details.value.open = true; };

// DataTable configuration
const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (operator) => ({
      type: 'button',
      class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(operator)
    })
  },
  {
    header: 'Setor',
    key: 'sector',
    formatter: (value) => value?.name || 'Setor não informado'
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (operator) => ({
      variant: operator.status === 'active' ? 'success' : 'danger'
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
      props: (operator) => ({
        href: route('operators.edit', operator.id),
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

const handleTableAction = ({ action, item }) => {
  if (action.key === 'delete') {
    confirmDelete(item);
  }
};
</script>

<template>
  <AdminLayout>
    <Head title="Operadores" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="user-group" outline class="h-7 w-7 text-slate-700" />
            Operadores
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os operadores cadastrados no sistema.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('operators.create')">Novo operador</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar por nome
            <InputText v-model="search" placeholder="Digite para buscar" />
          </label>
          <label class="form-label">
            Setor
            <InputSelect
              v-model="sectorId"
              :options="[
                { value: '', label: 'Todos' },
                ...props.sectors.map(sector => ({ value: sector.id, label: sector.name }))
              ]"
              placeholder=""
            />
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
        <PerPageSelector :current="operators.per_page ?? operators.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="operators.data"
        :actions="actions"
        empty-message="Nenhum operador encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="operators && operators.total" :paginator="operators" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir operador"
                  :message="deleteState.operator ? `Deseja realmente remover ${deleteState.operator.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <OperatorDetailsModal
      v-model="details.open"
      :operator-id="details.operatorId"
    />
  </AdminLayout>
</template>
