<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  clients: { type: Array, required: true },
  addresses: { type: Array, default: () => [] },
  title: { type: String, default: 'Finalizar Pedido' },
  confirmText: { type: String, default: 'Finalizar Pedido' },
  selectedClient: { type: Object, default: null },
  paymentMethod: { type: String, default: 'cash' },
  deliveryType: { type: String, default: 'pickup' },
  selectedAddress: { type: [Number, String], default: null },
  orderStatus: { type: String, default: 'pending' },
});

const emit = defineEmits(['update:modelValue', 'update:selectedClient', 'update:paymentMethod', 'update:deliveryType', 'update:selectedAddress', 'update:orderStatus', 'confirm']);

const page = usePage();

const modalOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const clientInput = ref('');
const clientInputRef = ref(null);
const paymentMethodRef = ref(null);

// Reactive props
const selectedClient = computed({
  get: () => props.selectedClient,
  set: (value) => emit('update:selectedClient', value),
});

const paymentMethod = computed({
  get: () => props.paymentMethod,
  set: (value) => emit('update:paymentMethod', value),
});

const deliveryType = computed({
  get: () => props.deliveryType,
  set: (value) => emit('update:deliveryType', value),
});

const selectedAddress = computed({
  get: () => props.selectedAddress,
  set: (value) => emit('update:selectedAddress', value),
});

const orderStatus = computed({
  get: () => props.orderStatus,
  set: (value) => {
    // Only allow status changes if user has permission
    if (canUpdateStatus.value) {
      emit('update:orderStatus', value);
    }
  },
});

const canUpdateStatus = computed(() => {
  const user = page.props.auth?.user;
  return user?.permissions?.orders?.update_status || user?.role === 'admin';
});

const clientSuggestions = computed(() => {
  if (!clientInput.value) return [];
  const query = clientInput.value.toLowerCase();
  return props.clients.filter(c =>
    c.name.toLowerCase().includes(query)
  ).slice(0, 10);
});

const handleClientInput = () => {
  const exactMatch = props.clients.find(c =>
    c.name.toLowerCase() === clientInput.value.toLowerCase()
  );
  selectedClient.value = exactMatch || null;
};

const handleClientKeydown = (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (selectedClient.value) {
      paymentMethodRef.value?.focus();
    } else if (clientSuggestions.value.length > 0) {
      selectClient(clientSuggestions.value[0]);
    } else {
      e.target.blur();
      nextTick(() => clientInputRef.value?.focus());
    }
  }
};

const handlePaymentMethodKeydown = (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    const deliveryButton = document.querySelector('button[data-delivery-type="pickup"]');
    if (deliveryButton) {
      deliveryButton.focus();
    }
  }
};

const selectClient = (client) => {
  selectedClient.value = client;
  clientInput.value = client.name;
};

const clientAddresses = computed(() => {
  if (!selectedClient.value) return [];
  return props.addresses.filter(addr => addr.client_id === selectedClient.value.id);
});

// Watch for modal opening to focus client input
watch(modalOpen, (isOpen) => {
  if (isOpen) {
    // Initialize client input
    clientInput.value = selectedClient.value?.name || '';
    setTimeout(() => {
      clientInputRef.value?.focus();
    }, 150);
  }
});

// Watch for delivery type changes to auto-select first address
watch(deliveryType, (newType) => {
  if (newType === 'delivery' && clientAddresses.value.length > 0) {
    selectedAddress.value = clientAddresses.value[0].id;
  } else if (newType === 'pickup') {
    selectedAddress.value = null;
  }
});

// Watch for client changes to clear selected address
watch(selectedClient, () => {
  selectedAddress.value = null;
  if (deliveryType.value === 'delivery' && clientAddresses.value.length > 0) {
    selectedAddress.value = clientAddresses.value[0].id;
  }
});

const confirm = () => {
  emit('confirm');
};
</script>

<template>
  <Modal v-model="modalOpen" :title="title" size="md" :lockScroll="true">
    <div class="space-y-4">
      <label class="form-label">
        Cliente *
        <InputText
          ref="clientInputRef"
          v-model="clientInput"
          @input="handleClientInput"
          @change="handleClientInput"
          @keydown="handleClientKeydown"
          type="text"
          list="clients"
          placeholder="Digite o nome do cliente..."
        />
        <datalist id="clients">
          <option v-for="client in clientSuggestions" :key="client.id" :value="client.name">
            {{ client.name }}
          </option>
        </datalist>
      </label>

      <label class="form-label" v-if="orderStatus !== undefined">
        Status do pedido
        <InputSelect v-model="orderStatus" :options="[
          { value: 'pending', label: 'Pendente' },
          { value: 'confirmed', label: 'Confirmado' },
          { value: 'shipped', label: 'Enviado' },
          { value: 'delivered', label: 'Entregue' },
          { value: 'cancelled', label: 'Cancelado' }
        ]" :disabled="!canUpdateStatus" :placeholder="null" />
        <span v-if="!canUpdateStatus" class="text-sm text-blue-500 mt-1 flex items-center gap-1">
          <HeroIcon name="information-circle" class="h-4 w-4" />
          Você não tem permissão para alterar o status do pedido
        </span>
      </label>

      <label class="form-label">
        Forma de pagamento *
        <InputSelect ref="paymentMethodRef" v-model="paymentMethod" @keydown="handlePaymentMethodKeydown" :options="[
          { value: 'cash', label: 'Dinheiro' },
          { value: 'card', label: 'Cartão' },
          { value: 'pix', label: 'PIX' }
        ]" :placeholder="null" />
      </label>

      <div>
        <label class="form-label mb-2">Tipo de entrega</label>
        <div class="flex">
          <Button
            @click="deliveryType = 'pickup'"
            :variant="deliveryType === 'pickup' ? 'primary' : 'outline'"
            type="button"
            data-delivery-type="pickup"
            class="flex-1 rounded-r-none border-r-0 hover:translate-y-0 hover:shadow-none"
          >
            Retirada em balcão
          </Button>
          <Button
            @click="deliveryType = 'delivery'"
            :variant="deliveryType === 'delivery' ? 'primary' : 'outline'"
            type="button"
            data-delivery-type="delivery"
            class="flex-1 rounded-l-none hover:translate-y-0 hover:shadow-none"
          >
            Entrega
          </Button>
        </div>
      </div>

      <div>
        <label class="form-label">
          Endereço de entrega{{ deliveryType === 'delivery' ? ' *' : '' }}
          <InputSelect v-model="selectedAddress" :options="clientAddresses.map(address => ({
            value: address.id,
            label: `${address.description} - ${address.address}, ${address.address_number} - ${address.city}/${address.state}`
          }))" :disabled="deliveryType === 'pickup'" :required="deliveryType === 'delivery'" />
        </label>
      </div>
    </div>

    <template #footer="{ close }">
      <Button @click="close" variant="outline">Cancelar</Button>
      <Button @click="confirm" variant="primary">{{ confirmText }}</Button>
    </template>
  </Modal>
</template>

<style scoped>
</style>
