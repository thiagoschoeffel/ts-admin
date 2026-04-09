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
import AlmoxarifadoDetailsModal from '@/components/almoxarifados/AlmoxarifadoDetailsModal.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  almoxarifados: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.almoxarifados?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.almoxarifados?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.almoxarifados?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.almoxarifados?.view);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get(route('almoxarifados.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; status.value = ''; submitFilters(); };

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, almoxarifado: null });

const confirmDelete = (almoxarifado) => {
  deleteState.value = { open: true, processing: false, almoxarifado };
};

const performDelete = async () => {
  if (!deleteState.value.almoxarifado) return;

  deleteState.value.processing = true;
  try {
    await router.delete(route('almoxarifados.destroy', deleteState.value.almoxarifado.id), {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, almoxarifado: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
    console.error('Erro ao excluir almoxarifado:', error);
  }
};

// Estado para modal de detalhes
const details = ref({ open: false, almoxarifadoId: null });
const openDetails = (almoxarifado) => { details.value.almoxarifadoId = almoxarifado.id; details.value.open = true; };

// DataTable configuration
const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (almoxarifado) => ({
      type: 'button',
      class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(almoxarifado)
    })
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (almoxarifado) => ({
      variant: almoxarifado.status === 'active' ? 'success' : 'danger'
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
      props: (almoxarifado) => ({
        href: route('almoxarifados.edit', almoxarifado.id),
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
    <Head title="Almoxarifados" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="view-columns" outline class="h-7 w-7 text-slate-700" />
            Almoxarifados
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os almoxarifadoes cadastrados no sistema.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('almoxarifados.create')">Novo almoxarifado</Button>
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
        <PerPageSelector :current="almoxarifados.per_page ?? almoxarifados.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="almoxarifados.data"
        :actions="actions"
        empty-message="Nenhum almoxarifado encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="almoxarifados && almoxarifados.total" :paginator="almoxarifados" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir almoxarifado"
                  :message="deleteState.almoxarifado ? `Deseja realmente remover ${deleteState.almoxarifado.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <AlmoxarifadoDetailsModal
      v-model="details.open"
      :almoxarifado-id="details.almoxarifadoId"
    />
  </AdminLayout>
</template>
