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
import BlockTypeDetailsModal from '@/components/blockTypes/BlockTypeDetailsModal.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';
import { formatQuantity } from '@/utils/formatters.js';

const props = defineProps({
  blockTypes: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.block_types?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.block_types?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.block_types?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.block_types?.view);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get(route('block-types.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; status.value = ''; submitFilters(); };

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, blockType: null });

const confirmDelete = (blockType) => {
  deleteState.value = { open: true, processing: false, blockType };
};

const performDelete = async () => {
  if (!deleteState.value.blockType) return;

  deleteState.value.processing = true;
  try {
    await router.delete(route('block-types.destroy', deleteState.value.blockType.id), {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, blockType: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
    console.error('Erro ao excluir tipo de bloco:', error);
  }
};

// Estado para modal de detalhes
const details = ref({ open: false, blockTypeId: null });
const openDetails = (blockType) => { details.value.blockTypeId = blockType.id; details.value.open = true; };

// DataTable configuration
const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (blockType) => ({
      type: 'button',
      class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(blockType)
    })
  },
  {
    header: 'Matéria-Prima Virgem (%)',
    key: 'raw_material_percentage',
    formatter: (value) => value ? `${formatQuantity(value)}%` : '-'
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (blockType) => ({
      variant: blockType.status === 'active' ? 'success' : 'danger'
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
      props: (blockType) => ({
        href: route('block-types.edit', blockType.id),
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
    <Head title="Tipos de Blocos" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Tipos de Blocos
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os tipos de blocos cadastrados no sistema.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('block-types.create')">Novo tipo de bloco</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar por nome
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
        <PerPageSelector :current="blockTypes.per_page ?? blockTypes.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="blockTypes.data"
        :actions="actions"
        empty-message="Nenhum tipo de bloco encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="blockTypes && blockTypes.total" :paginator="blockTypes" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir tipo de bloco"
                  :message="deleteState.blockType ? `Deseja realmente remover ${deleteState.blockType.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <BlockTypeDetailsModal
      v-model="details.open"
      :block-type-id="details.blockTypeId"
    />
  </AdminLayout>
</template>