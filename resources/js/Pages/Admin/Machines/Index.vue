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
import MachineDetailsModal from '@/components/machines/MachineDetailsModal.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  machines: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  sectors: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.machines?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.machines?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.machines?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.machines?.view);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const sectorId = ref(props.filters.sector_id || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get(route('machines.index'), { search: search.value, status: status.value, sector_id: sectorId.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; status.value = ''; sectorId.value = ''; submitFilters(); };

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, machine: null });

const confirmDelete = (machine) => {
  deleteState.value = { open: true, processing: false, machine };
};

const performDelete = async () => {
  if (!deleteState.value.machine) return;

  deleteState.value.processing = true;
  try {
    await router.delete(route('machines.destroy', deleteState.value.machine.id), {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, machine: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
    console.error('Erro ao excluir máquina:', error);
  }
};

// Estado para modal de detalhes
const details = ref({ open: false, machineId: null });
const openDetails = (machine) => { details.value.machineId = machine.id; details.value.open = true; };

// DataTable configuration
const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (machine) => ({
      type: 'button',
      class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(machine)
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
    props: (machine) => ({
      variant: machine.status === 'active' ? 'success' : 'danger'
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
      props: (machine) => ({
        href: route('machines.edit', machine.id),
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
    <Head title="Máquinas" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="cpu-chip" outline class="h-7 w-7 text-slate-700" />
            Máquinas
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie as máquinas cadastradas no sistema.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('machines.create')">Nova máquina</Button>
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
        <PerPageSelector :current="machines.per_page ?? machines.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="machines.data"
        :actions="actions"
        empty-message="Nenhuma máquina encontrada."
        @action="handleTableAction"
      />

      <Pagination v-if="machines && machines.total" :paginator="machines" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir máquina"
                  :message="deleteState.machine ? `Deseja realmente remover ${deleteState.machine.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <MachineDetailsModal
      v-model="details.open"
      :machine-id="details.machineId"
    />
  </AdminLayout>
</template>
