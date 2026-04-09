<script setup>
import { router } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import { formatCurrency } from '@/utils/formatters';

const props = defineProps({
  recentOrders: { type: Array, default: () => [] },
});

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
</script>

<template>
  <div>
    <div>
      <h2 class="text-xl font-semibold">Últimos pedidos</h2>
      <p class="mt-2 text-sm text-slate-500">Visualize os pedidos mais recentes criados no sistema.</p>
    </div>
    <div class="space-y-4 mt-6">
      <div v-for="order in recentOrders" :key="order.id" @click="router.visit(`/admin/orders/${order.id}/edit`)" class="p-8 border border-slate-200 rounded-lg bg-white shadow-sm cursor-pointer hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <p class="text-xs text-slate-500 mb-2">Pedido #{{ order.id }}</p>
            <h3 class="font-medium text-slate-900">{{ order.client.name }}</h3>
            <p class="text-xs text-slate-600 mt-1">{{ order.ordered_at || order.created_at }}</p>
          </div>
          <div class="flex flex-col items-end gap-0.5">
            <Badge :variant="getStatusVariant(order.status)">
              {{ getStatusLabel(order.status) }}
            </Badge>
            <div class="text-right mt-2">
              <div class="font-semibold text-slate-900">{{ formatCurrency(order.total) }}</div>
              <div class="text-xs text-slate-500">{{ order.user.name }}</div>
            </div>
          </div>
        </div>
      </div>
      <div v-if="recentOrders.length === 0" class="text-center py-4 text-slate-500">
        Nenhum pedido recente.
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
