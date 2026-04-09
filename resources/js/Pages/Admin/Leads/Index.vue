
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import Dropdown from '@/components/Dropdown.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import LeadDetailsModal from '@/components/leads/LeadDetailsModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  leads: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');

const canViewLeads = computed(() => isAdmin.value || !!user.value?.permissions?.leads?.view);
const canCreateLeads = computed(() => isAdmin.value || !!user.value?.permissions?.leads?.create);
const canUpdateLeads = computed(() => isAdmin.value || !!user.value?.permissions?.leads?.update);
const canDeleteLeads = computed(() => isAdmin.value || !!user.value?.permissions?.leads?.delete);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const source = ref(props.filters.source || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get('/admin/leads', { search: search.value, status: status.value, source: source.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => { search.value = ''; status.value = ''; source.value = ''; submitFilters(); };

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, lead: null });

const confirmDelete = (lead) => {
  deleteState.value = { open: true, processing: false, lead };
};

const performDelete = async () => {
  if (!deleteState.value.lead) return;

  deleteState.value.processing = true;
  try {
    await router.delete(`/admin/leads/${deleteState.value.lead.id}`, {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, lead: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
  }
};

const details = ref({ open: false, leadId: null });
const openDetails = (lead) => { details.value.leadId = lead.id; details.value.open = true; };

const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (lead) => ({
      type: 'button',
      class: 'font-bold text-blue-600 cursor-pointer',
      onClick: () => openDetails(lead)
    })
  },
  {
    header: 'Interações',
    key: 'interactions_count'
  },
  {
    header: 'Email',
    key: 'email'
  },
  {
    header: 'Telefone',
    key: 'phone'
  },
  {
    header: 'Empresa',
    key: 'company'
  },
  {
    header: 'Origem',
    key: 'source',
    formatter: (value) => {
      const sources = {
        site: 'Site',
        indicacao: 'Indicação',
        evento: 'Evento',
        manual: 'Manual'
      };
      return sources[value] || value;
    }
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (lead) => ({
      variant: lead.status === 'qualified' ? 'success' : lead.status === 'in_contact' ? 'warning' : lead.status === 'new' ? 'info' : 'secondary'
    }),
    formatter: (value) => {
      const statuses = {
        new: 'Novo',
        in_contact: 'Em contato',
        qualified: 'Qualificado',
        discarded: 'Descartado'
      };
      return statuses[value] || value;
    }
  },
  {
    header: 'Dono',
    key: 'owner',
    formatter: (value) => value?.name || '—'
  }
];

const actions = computed(() => {
  return (lead) => {
    const acts = [];
    if (canUpdateLeads.value) {
      acts.push({
        key: 'edit',
        label: 'Editar',
        icon: 'pencil',
        component: Link,
        props: (lead) => ({
          href: route('leads.edit', lead.id),
          class: 'menu-panel-link'
        })
      });
    }
    if (canDeleteLeads.value) {
      acts.push({
        key: 'delete',
        label: 'Excluir',
        icon: 'trash',
        class: 'menu-panel-link text-rose-600 hover:text-rose-700'
      });
    }
    return acts;
  };
});

const handleTableAction = ({ action, item }) => {
  if (action.key === 'delete') {
    confirmDelete(item);
  }
};
</script>

<template>
  <AdminLayout>
    <Head title="Leads" />
    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="chat-bubble-left-right" class="h-7 w-7 text-slate-700" />
            Leads
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os leads e oportunidades de vendas.</p>
        </div>
        <Button v-if="canCreateLeads" variant="primary" :href="route('leads.create')">Novo lead</Button>
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
              { value: 'new', label: 'Novo' },
              { value: 'in_contact', label: 'Em contato' },
              { value: 'qualified', label: 'Qualificado' },
              { value: 'discarded', label: 'Descartado' }
            ]" placeholder="" />
          </label>
          <label class="form-label">
            Origem
            <InputSelect v-model="source" :options="[
              { value: '', label: 'Todas' },
              { value: 'site', label: 'Site' },
              { value: 'indicacao', label: 'Indicação' },
              { value: 'evento', label: 'Evento' },
              { value: 'manual', label: 'Manual' }
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
        <PerPageSelector :current="leads.per_page ?? leads.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="leads.data"
        :actions="actions"
        empty-message="Nenhum lead encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="leads && leads.total" :paginator="leads" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir lead"
                  :message="deleteState.lead ? `Deseja realmente remover ${deleteState.lead.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <LeadDetailsModal v-model="details.open" :lead-id="details.leadId" />
  </AdminLayout>
</template>
