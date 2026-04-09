<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import axios from 'axios';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import { useToasts } from '@/components/toast/useToasts';
import Button from '@/components/Button.vue';
import OrderForm from '@/components/orders/OrderForm.vue';
import OrderModal from '@/components/orders/OrderModal.vue';
import { formatQuantity } from '@/utils/formatters';

const props = defineProps({
  order: { type: Object, required: true },
  products: { type: Array, required: true },
  clients: { type: Array, required: true },
  addresses: { type: Array, default: () => [] },
  recentOrders: { type: Array, default: () => [] },
  currentClient: { type: Object, default: null },
});

const items = ref(props.order.items || []);
// Ensure all quantity values are numbers
items.value = items.value.map(item => ({
  ...item,
  quantity: Number(item.quantity) || 0,
  unit_price: Number(item.unit_price) || 0,
  total: Number(item.total) || 0,
}));

const total = computed(() => {
  return items.value.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0), 0);
});

const totalItemsQuantity = computed(() => {
  return items.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
});

// Sistema de toasts
const { error: toastError, success: toastSuccess } = useToasts();

// Persistência da quantidade no commit (blur) vindo do InputNumber
const commitItemQuantity = async (index, value, originalValue) => {
  const item = items.value[index];
  let newQuantity = Number(value);
  if (!isFinite(newQuantity) || newQuantity <= 0) newQuantity = 0.01;
  newQuantity = Math.round(newQuantity * 100) / 100;

  // Comparar com valor original passado pelo OrderForm
  if (Math.abs(newQuantity - originalValue) > 0.001) {
    // Atualização otimista local; total é derivado reativamente no template
    item.quantity = newQuantity;
    try {
      const response = await axios.patch(`/admin/orders/${props.order.id}/items/${item.id}`, { quantity: item.quantity });
      item.quantity = response.data.item.quantity;
      toastSuccess(`Quantidade de ${item.name} atualizada para ${formatQuantity(item.quantity)}`);
    } catch (error) {
      console.error('Erro ao atualizar quantidade:', error);
      const messages = error.response?.data?.errors ? Object.values(error.response.data.errors).flat() : ['Erro ao atualizar quantidade do item'];
      toastError(messages.join(', '));
      item.quantity = originalValue; // Reverter para valor original
    }
  }
  // Se valor não mudou, não faz nada
};

const addItem = async ({ product, quantity }) => {
  const price = Number(product.price);
  const qty = Number(quantity);

  try {
    const response = await axios.post(`/admin/orders/${props.order.id}/items`, {
      product_id: product.id,
      quantity: qty,
    });

    // Check if item already exists locally
    const existingIndex = items.value.findIndex(i => i.product_id === product.id);
    if (existingIndex >= 0) {
      // Update existing item
      items.value[existingIndex] = {
        ...response.data.item,
        quantity: Number(response.data.item.quantity) || 0,
        unit_price: Number(response.data.item.unit_price) || 0,
        total: Number(response.data.item.total) || 0,
      };
      toastSuccess(`Quantidade de ${product.name} atualizada para ${response.data.item.quantity}`);
    } else {
      // Add new item at the beginning of the list
      const newItem = {
        ...response.data.item,
        quantity: Number(response.data.item.quantity) || 0,
        unit_price: Number(response.data.item.unit_price) || 0,
        total: Number(response.data.item.total) || 0,
      };
      items.value.unshift(newItem);
      toastSuccess(`${product.name} adicionado ao pedido`);
    }
  } catch (error) {
    console.error('Erro ao adicionar item:', error);
    const messages = error.response?.data?.errors ? Object.values(error.response.data.errors).flat() : ['Erro ao adicionar item ao pedido'];
    toastError(messages.join(', '));
  }
};

const removeItem = async (index) => {
  const item = items.value[index];
  try {
    await axios.delete(`/admin/orders/${props.order.id}/items/${item.id}`);
    items.value.splice(index, 1);
    toastSuccess(`${item.name} removido do pedido`);
  } catch (error) {
    console.error('Erro ao remover item:', error);
    const messages = error.response?.data?.errors ? Object.values(error.response.data.errors).flat() : ['Erro ao remover item do pedido'];
    toastError(messages.join(', '));
  }
};

// Modal for customer/payment
const modalOpen = ref(false);
const selectedClient = ref(null);
const paymentMethod = ref('');
const deliveryType = ref('pickup'); // pickup or delivery
const selectedAddress = ref(null);
const orderStatus = ref('pending');

const openModal = () => {
  // Initialize modal values with current order data
  selectedClient.value = props.currentClient ? { id: props.currentClient.id, name: props.currentClient.name } : null;

  orderStatus.value = props.order.status || 'pending';
  paymentMethod.value = props.order.payment_method || 'cash';
  deliveryType.value = props.order.delivery_type || 'pickup';

  // Set the address ID directly
  selectedAddress.value = props.order.address_id || null;

  modalOpen.value = true;
};

const saveOrder = () => {
  // Validação: verificar se há itens no pedido
  if (items.value.length === 0) {
    toastError('Adicione pelo menos um produto ao pedido antes de salvar.');
    return;
  }

  // Validação: verificar se há cliente selecionado
  if (!selectedClient.value) {
    toastError('Selecione um cliente para o pedido.');
    return;
  }

  const data = {
    client_id: selectedClient.value.id,
    status: orderStatus.value,
    payment_method: paymentMethod.value,
    delivery_type: deliveryType.value,
    address_id: deliveryType.value === 'pickup' ? null : selectedAddress.value,
    // Items are already managed individually, no need to send them
  };

  router.patch(`/admin/orders/${props.order.id}`, data, {
    onSuccess: () => {
      modalOpen.value = false;
    },
    onError: (errors) => {
      const messages = Object.values(errors).flat();
      toastError(messages.join(', '));
    },
  });
};

const goToNewOrder = () => {
  router.visit('/admin/orders/create');
};

onMounted(() => {
  // Keyboard shortcuts
  document.addEventListener('keydown', (e) => {
    if (e.key === 'F2') {
      e.preventDefault();
      openModal();
    }
    if (e.key === 'F3') {
      e.preventDefault();
      goToNewOrder();
    }
  });

  // Focus on product input when page loads
  nextTick(() => {
    const productInput = document.querySelector('input[placeholder*="Digite o nome ou código do produto"]');
    if (productInput) {
      productInput.focus();
    }
  });
});

// Watch modal to refocus on product input when modal closes
watch(modalOpen, (isOpen) => {
  if (!isOpen) {
    nextTick(() => {
      const productInput = document.querySelector('input[placeholder*="Digite o nome ou código do produto"]');
      if (productInput) {
        productInput.focus();
      }
    });
  }
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
  <AdminLayout>
    <Head title="Editar pedido" />

    <section class="space-y-8">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="shopping-bag" class="h-7 w-7 text-slate-700" />
            Editar pedido #{{ order.id }}
          </h1>
          <p class="mt-2 text-sm text-slate-500">Edite os itens do pedido e finalize com cliente e pagamento.</p>
        </div>
        <div class="flex gap-3">
          <Button @click="openModal" variant="primary">
            <HeroIcon name="user-plus" class="h-5 w-5" />
            Salvar alterações (F2)
          </Button>
          <Button @click="goToNewOrder" variant="outline">
            <HeroIcon name="plus" class="h-5 w-5" />
            Novo pedido (F3)
          </Button>
        </div>
      </div>

      <OrderForm
        :products="products"
        v-model="items"
        :total="total"
        :total-items-quantity="totalItemsQuantity"
        :recent-orders="recentOrders"
        @add-item="addItem"
        @commit-quantity="commitItemQuantity"
        @remove-item="removeItem"
      />

      <!-- RecentOrders removido - agora está dentro do OrderForm -->
    </section>

    <OrderModal
      v-model="modalOpen"
      :clients="clients"
      :addresses="addresses"
      title="Salvar alterações"
      confirm-text="Salvar alterações"
      v-model:selected-client="selectedClient"
      v-model:payment-method="paymentMethod"
      v-model:delivery-type="deliveryType"
      v-model:selected-address="selectedAddress"
      v-model:order-status="orderStatus"
      @confirm="saveOrder"
    />
  </AdminLayout>
</template>

<style scoped>
</style>
