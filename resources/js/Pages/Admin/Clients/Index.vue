<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import ClientDetailsModal from '@/components/clients/ClientDetailsModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  clients: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.clients?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.clients?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.clients?.delete);

const search = ref(props.filters.search || '');
const personType = ref(props.filters.person_type || '');
const status = ref(props.filters.status || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get('/admin/clients', { search: search.value, person_type: personType.value, status: status.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; personType.value = ''; status.value = ''; submitFilters(); };

const deleteState = ref({ open: false, processing: false, client: null });
const confirmDelete = (client) => { deleteState.value = { open: true, processing: false, client }; };
const performDelete = async () => {
  if (!deleteState.value.client) return;
  deleteState.value.processing = true;
  try { await router.delete(`/admin/clients/${deleteState.value.client.id}`); }
  finally { deleteState.value.processing = false; deleteState.value.open = false; deleteState.value.client = null; }
};

const details = ref({ open: false, clientId: null });
const openDetails = (client) => { details.value.clientId = client.id; details.value.open = true; };

// DataTable configuration
const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (client) => ({
      type: 'button',
      class: (isAdmin.value || user.value?.permissions?.clients?.view) ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(client)
    })
  },
  {
    header: 'Tipo',
    key: 'person_type',
    formatter: (value) => value === 'company' ? 'Jurídica' : 'Física'
  },
  {
    header: 'Documento',
    key: 'formatted_document'
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (client) => ({
      variant: client.status === 'active' ? 'success' : 'danger'
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
      props: (client) => ({
        href: `/admin/clients/${client.id}/edit`,
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
    <Head title="Clientes" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="identification" outline class="h-7 w-7 text-slate-700" />
            Clientes
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os cadastros de clientes existentes ou adicione novos registros.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('clients.create')">Novo cliente</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar por nome ou documento
            <InputText v-model="search" placeholder="Digite para buscar" />
          </label>
          <label class="form-label">
            Tipo de pessoa
            <InputSelect v-model="personType" :options="[
              { value: '', label: 'Todos' },
              { value: 'individual', label: 'Pessoa Física' },
              { value: 'company', label: 'Pessoa Jurídica' }
            ]" placeholder="" />
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
        <PerPageSelector :current="clients.per_page ?? clients.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="clients.data"
        :actions="actions"
        empty-message="Nenhum cliente cadastrado até o momento."
        @action="handleTableAction"
      />

      <Pagination v-if="clients && clients.total" :paginator="clients" />
    </section>

    <ClientDetailsModal v-model="details.open" :client-id="details.clientId" />
    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir cliente"
                  :message="deleteState.client ? `Deseja realmente remover ${deleteState.client.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />
  </AdminLayout>
</template>

<style scoped>
/* Usa estilos globais definidos em resources/css/app.css para menu-trigger e menu-panel-link */
</style>
