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
import MoldTypeDetailsModal from '@/components/moldTypes/MoldTypeDetailsModal.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';
import { formatQuantity } from '@/utils/formatters.js';

const props = defineProps({
  moldTypes: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.mold_types?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.mold_types?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.mold_types?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.mold_types?.view);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get(route('mold-types.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; status.value = ''; submitFilters(); };

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, moldType: null });

const confirmDelete = (moldType) => {
  deleteState.value = { open: true, processing: false, moldType };
};

const performDelete = async () => {
  if (!deleteState.value.moldType) return;

  deleteState.value.processing = true;
  try {
    await router.delete(route('mold-types.destroy', deleteState.value.moldType.id), {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, moldType: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
    console.error('Erro ao excluir tipo de moldado:', error);
  }
};

// Estado para modal de detalhes
const details = ref({ open: false, moldTypeId: null });
const openDetails = (moldType) => { details.value.moldTypeId = moldType.id; details.value.open = true; };

// DataTable configuration
const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (moldType) => ({
      type: 'button',
      class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(moldType)
    })
  },
  {
    header: 'Quantidade de peças por pacote (UND)',
    key: 'pieces_per_package',
    formatter: (value) => value ? formatQuantity(value) : '-'
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (moldType) => ({
      variant: moldType.status === 'active' ? 'success' : 'danger'
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
      props: (moldType) => ({
        href: route('mold-types.edit', moldType.id),
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
    <Head title="Tipos de Moldados" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Tipos de Moldados
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os tipos de moldados cadastrados no sistema.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('mold-types.create')">Novo tipo de moldado</Button>
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
        <PerPageSelector :current="moldTypes.per_page ?? moldTypes.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="moldTypes.data"
        :actions="actions"
        empty-message="Nenhum tipo de moldado encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="moldTypes && moldTypes.total" :paginator="moldTypes" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir tipo de moldado"
                  :message="deleteState.moldType ? `Deseja realmente remover ${deleteState.moldType.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <MoldTypeDetailsModal
      v-model="details.open"
      :mold-type-id="details.moldTypeId"
    />
  </AdminLayout>
</template>