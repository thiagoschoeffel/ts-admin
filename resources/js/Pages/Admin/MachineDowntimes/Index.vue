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
import MachineDowntimeDetailsModal from '@/components/machineDowntimes/MachineDowntimeDetailsModal.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  downtimes: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  machines: { type: Array, default: () => [] },
  reasons: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.machine_downtimes?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.machine_downtimes?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.machine_downtimes?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.machine_downtimes?.view);

const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const machineId = ref(props.filters.machine_id || '');
const reasonId = ref(props.filters.reason_id || '');
const period = ref({
  start: props.filters.period?.from || '',
  end: props.filters.period?.to || '',
});

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get(route('machine_downtimes.index'), {
    search: search.value,
    status: status.value,
    machine_id: machineId.value,
    reason_id: reasonId.value,
    period: {
      from: period.value?.start || null,
      to: period.value?.end || null,
    },
  }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; status.value = ''; machineId.value = ''; reasonId.value=''; period.value = { start: '', end: '' }; submitFilters(); };

const deleteState = ref({ open: false, processing: false, item: null });
const confirmDelete = (item) => { deleteState.value = { open: true, processing: false, item }; };
const performDelete = async () => {
  if (!deleteState.value.item) return;
  deleteState.value.processing = true;
  try {
    await router.delete(route('machine_downtimes.destroy', deleteState.value.item.id), {
      onSuccess: () => { deleteState.value = { open: false, processing: false, item: null }; },
      onError: () => { deleteState.value.processing = false; }
    });
  } catch (e) { deleteState.value.processing = false; }
};

const details = ref({ open: false, id: null });
const openDetails = (row) => { details.value.id = row.id; details.value.open = true; };

const columns = [
  { header: 'Máquina', key: 'machine_name', component: 'button', props: (row) => ({ type:'button', class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900', onClick: () => openDetails(row) }) },
  { header: 'Motivo', key: 'reason_name' },
  { header: 'Início', key: 'started_at_formatted' },
  { header: 'Fim', key: 'ended_at_formatted' },
  { header: 'Duração', key: 'duration' },
  { header: 'Status', key: 'status', component: Badge, props: (r) => ({ variant: r.status === 'active' ? 'success' : 'danger' }), formatter: (v) => v === 'active' ? 'Ativo' : 'Inativo' },
];

const actions = computed(() => {
  const acts = [];
  if (canUpdate.value) {
    acts.push({ key: 'edit', label: 'Editar', icon: 'pencil', component: Link, props: (row) => ({ href: route('machine_downtimes.edit', row.id), class: 'menu-panel-link' }) });
  }
  if (canDelete.value) {
    acts.push({ key: 'delete', label: 'Excluir', icon: 'trash', class: 'menu-panel-link text-rose-600 hover:text-rose-700' });
  }
  if (acts.length === 0) acts.push({ key: 'no-actions', label: 'Nenhuma ação disponível', class: 'menu-panel-link pointer-events-none text-slate-400' });
  return acts;
});

const handleTableAction = ({ action, item }) => { if (action.key === 'delete') confirmDelete(item); };
</script>

<template>
  <AdminLayout>
    <Head title="Paradas de Máquina" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="pause-circle" class="h-7 w-7 text-slate-700" />
            Paradas de Máquina
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie as paradas de máquina.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('machine_downtimes.create')">Nova parada</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar
            <InputText v-model="search" placeholder="Busque por máquina, motivo ou observações" />
          </label>
          <label class="form-label">
            Máquina
            <InputSelect v-model="machineId" :placeholder="''" :options="[{ value: '', label: 'Todas' }, ...machines.map(m => ({ value: m.id, label: m.name }))]" />
          </label>
          <label class="form-label">
            Motivo
            <InputSelect v-model="reasonId" :placeholder="''" :options="[{ value: '', label: 'Todos' }, ...reasons.map(r => ({ value: r.id, label: r.name }))]" />
          </label>
          <label class="form-label lg:col-span-2">
            Período
            <InputDatePicker v-model="period" :range="true" :withTime="true" placeholder="Selecione o período" />
          </label>
          <label class="form-label">
            Status
            <InputSelect v-model="status" :placeholder="''" :options="[{ value: '', label: 'Todos' }, { value: 'active', label: 'Ativos' }, { value: 'inactive', label: 'Inativos' }]" />
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
        <PerPageSelector :current="downtimes.per_page ?? downtimes.perPage ?? 10" />
      </div>

      <DataTable :columns="columns" :data="downtimes.data" :actions="actions" empty-message="Nenhuma parada encontrada." @action="handleTableAction" />
      <Pagination v-if="downtimes && downtimes.total" :paginator="downtimes" />
    </section>

    <ConfirmModal v-model="deleteState.open" :processing="deleteState.processing" title="Excluir parada" :message="deleteState.item ? `Deseja realmente remover a parada da máquina ${deleteState.item.machine_name}?` : ''" confirm-text="Excluir" variant="danger" @confirm="performDelete" />

    <MachineDowntimeDetailsModal v-model="details.open" :downtime-id="details.id" />
  </AdminLayout>
</template>
