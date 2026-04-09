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
import OrderDetailsModal from '@/components/orders/OrderDetailsModal.vue';
import PdfViewerModal from '@/components/orders/PdfViewerModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';
import { formatCurrency } from '@/utils/formatters';

const props = defineProps({
  orders: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.orders?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.orders?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.orders?.delete);
const canView = computed(() => isAdmin.value || !!user.value?.permissions?.orders?.view);
const canExportPdf = computed(() => isAdmin.value || !!user.value?.permissions?.orders?.export_pdf);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
// Filtro de período do pedido com hora
const orderedPeriod = ref({
  start: props.filters.ordered_from || null,
  end: props.filters.ordered_to || null,
});

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  const params = {
    search: search.value,
    status: status.value,
    ordered_from: orderedPeriod.value?.start || null,
    ordered_to: orderedPeriod.value?.end || null,
  };
  router.get('/admin/orders', params, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};
const resetFilters = () => {
  search.value = '';
  status.value = '';
  orderedPeriod.value = { start: null, end: null };
  submitFilters();
};

// Estado para confirmação de exclusão
const deleteState = ref({ open: false, processing: false, order: null });

const confirmDelete = (order) => {
  deleteState.value = { open: true, processing: false, order };
};

const performDelete = async () => {
  if (!deleteState.value.order) return;

  deleteState.value.processing = true;
  try {
    await router.delete(`/admin/orders/${deleteState.value.order.id}`, {
      onSuccess: () => {
        deleteState.value = { open: false, processing: false, order: null };
      },
      onError: () => {
        deleteState.value.processing = false;
      }
    });
  } catch (error) {
    deleteState.value.processing = false;
    console.error('Erro ao excluir pedido:', error);
  }
};

// Estado para modal de detalhes
const details = ref({ open: false, orderId: null });
const openDetails = (order) => { details.value.orderId = order.id; details.value.open = true; };

// Estado para modal de visualização de PDF
const pdfViewer = ref({ open: false, url: '' });

const getStatusVariant = (status) => {
  const variants = {
    pending: 'warning',
    confirmed: 'info',
    completed: 'success',
    shipped: 'primary',
    delivered: 'success',
    cancelled: 'danger',
  };
  return variants[status] || 'secondary';
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pendente',
    confirmed: 'Confirmado',
    completed: 'Concluído',
    shipped: 'Enviado',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
  };
  return labels[status] || status;
};

// DataTable configuration
const columns = [
  {
    header: 'ID',
    key: 'id',
    component: 'button',
    props: (order) => ({
      type: 'button',
      class: canView.value ? 'font-bold text-blue-600 cursor-pointer' : 'text-slate-900',
      onClick: () => openDetails(order)
    })
  },
  {
    header: 'Cliente',
    key: 'client',
    formatter: (value) => value?.name || 'Cliente não informado'
  },
  {
    header: 'Usuário',
    key: 'user',
    formatter: (value) => value?.name || '-'
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (order) => ({
      variant: getStatusVariant(order.status)
    }),
    formatter: (value) => getStatusLabel(value)
  },
  {
    header: 'Total',
    key: 'total',
    formatter: (value) => formatCurrency(value)
  },
  {
    header: 'Data do pedido',
    key: 'ordered_at',
    formatter: (value) => value || '-'
  }
];

const actions = computed(() => {
  const acts = [];
  if (canExportPdf.value) {
    acts.push({
      key: 'print',
      label: 'Imprimir',
      icon: 'printer',
      component: 'button',
      props: (order) => ({
        type: 'button',
        class: 'menu-panel-link'
      })
    });
  }
  if (canUpdate.value) {
    acts.push({
      key: 'edit',
      label: 'Editar',
      icon: 'pencil',
      component: Link,
      props: (order) => ({
        href: route('orders.edit', order.id),
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
  } else if (action.key === 'print') {
    pdfViewer.value = { open: true, url: route('orders.pdf.show', item.id) };
  }
};
</script>

<template>
  <AdminLayout>
    <Head title="Pedidos" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="shopping-bag" outline class="h-7 w-7 text-slate-700" />
            Pedidos
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os pedidos dos clientes.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('orders.create')">Novo pedido</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar por cliente
            <InputText v-model="search" placeholder="Digite para buscar" />
          </label>
          <label class="form-label">
            Status
            <InputSelect v-model="status" :options="[
              { value: '', label: 'Todos' },
              { value: 'pending', label: 'Pendente' },
              { value: 'confirmed', label: 'Confirmado' },
              { value: 'shipped', label: 'Enviado' },
              { value: 'delivered', label: 'Entregue' },
              { value: 'cancelled', label: 'Cancelado' }
            ]" placeholder="" />
          </label>
          <label class="form-label">
            Período do pedido
            <InputDatePicker v-model="orderedPeriod" :range="true" :withTime="true" placeholder="Selecione o período" />
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
        <PerPageSelector :current="orders.per_page ?? orders.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="orders.data"
        :actions="actions"
        empty-message="Nenhum pedido encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="orders && orders.total" :paginator="orders" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  title="Excluir pedido"
                  :message="deleteState.order ? `Deseja realmente remover o pedido #${deleteState.order.id}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />

    <OrderDetailsModal v-model="details.open" :order-id="details.orderId" />

    <PdfViewerModal v-model="pdfViewer.open" :pdf-url="pdfViewer.url" />
  </AdminLayout>
</template>
