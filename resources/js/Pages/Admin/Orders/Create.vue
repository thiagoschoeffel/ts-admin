<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import Button from '@/components/Button.vue';
import OrderForm from '@/components/orders/OrderForm.vue';
import OrderModal from '@/components/orders/OrderModal.vue';
import { useToasts } from '@/components/toast/useToasts';
import { formatQuantity } from '@/utils/formatters';

const props = defineProps({
  products: { type: Array, required: true },
  clients: { type: Array, required: true },
  addresses: { type: Array, default: () => [] },
  recentOrders: { type: Array, default: () => [] },
});

// Sistema de toasts
const { error: toastError, success: toastSuccess } = useToasts();

// Items do pedido
const items = ref([]);

// Computed para totais
const total = computed(() => {
  return items.value.reduce((sum, item) => sum + Number(item.total || 0), 0);
});

const totalItemsQuantity = computed(() => {
  return items.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
});

// Funções para gerenciar itens
const addItem = ({ product, quantity }) => {
  const price = Number(product.price);
  const existing = items.value.find(i => i.product_id === product.id);
  if (existing) {
    existing.quantity += quantity;
    existing.total = existing.quantity * existing.unit_price;
    toastSuccess(`Quantidade de ${product.name} atualizada para ${existing.quantity}`);
  } else {
    const newItem = {
      product_id: product.id,
      name: product.name,
      unit_price: price,
      quantity: quantity,
      total: price * quantity,
    };
    items.value.unshift(newItem);
    toastSuccess(`${product.name} adicionado ao pedido`);
  }
};

const commitItemQuantity = (index, value) => {
  const item = items.value[index];
  const prev = item.quantity;
  let next = Number(value);
  if (!isFinite(next) || next <= 0) next = 0.01;
  item.quantity = Number(next.toFixed(2));
  item.total = item.quantity * item.unit_price;
  if (item.quantity !== prev) {
    toastSuccess(`Quantidade de ${item.name} atualizada para ${formatQuantity(item.quantity)}`);
  }
};

const removeItem = (index) => {
  const itemName = items.value[index]?.name || 'Item';
  items.value.splice(index, 1);
  toastSuccess(`${itemName} removido do pedido`);
};

// Modal
const modalOpen = ref(false);
const selectedClient = ref(null);
const paymentMethod = ref('cash');
const deliveryType = ref('pickup');
const selectedAddress = ref(null);

const openModal = () => {
  modalOpen.value = true;
};

const finalizeOrder = () => {
  if (items.value.length === 0) {
    toastError('Adicione pelo menos um produto ao pedido antes de finalizar.');
    return;
  }

  if (!selectedClient.value) {
    toastError('Selecione um cliente para o pedido.');
    return;
  }

  const data = {
    client_id: selectedClient.value.id,
    items: items.value.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
    })),
    payment_method: paymentMethod.value,
    delivery_type: deliveryType.value,
    address_id: deliveryType.value === 'pickup' ? null : selectedAddress.value,
  };

  router.post('/admin/orders', data, {
    onSuccess: () => {
      // Limpar formulário para novo pedido
      items.value = [];
      selectedClient.value = null;
      paymentMethod.value = 'cash';
      deliveryType.value = 'pickup';
      selectedAddress.value = null;

      modalOpen.value = false;
    },
    onError: (errors) => {
      const messages = Object.values(errors).flat();
      toastError(messages.join(', '));
    },
  });
};

onMounted(() => {
  // Keyboard shortcuts
  document.addEventListener('keydown', (e) => {
    if (e.key === 'F2') {
      e.preventDefault();
      openModal();
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
</script>

<template>
  <AdminLayout>
    <Head title="Novo pedido" />

    <section class="space-y-8">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="shopping-bag" class="h-7 w-7 text-slate-700" />
            Novo pedido
          </h1>
          <p class="mt-2 text-sm text-slate-500">Crie um novo pedido adicionando produtos e finalizando com cliente e pagamento.</p>
        </div>
        <Button @click="openModal" variant="primary">
          <HeroIcon name="user-plus" class="h-5 w-5" />
          Finalizar pedido (F2)
        </Button>
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
      title="Finalizar pedido"
      confirm-text="Finalizar pedido"
      v-model:selected-client="selectedClient"
      v-model:payment-method="paymentMethod"
      v-model:delivery-type="deliveryType"
      v-model:selected-address="selectedAddress"
      @confirm="finalizeOrder"
    />
  </AdminLayout>
</template>

<style scoped>
</style>
