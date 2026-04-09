<script setup>
import { ref, onMounted } from 'vue';
import { useToasts } from '@/components/toast/useToasts';
import AddressForm from './AddressForm.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
  clientId: { type: Number, required: true },
  states: { type: Array, required: true },
});

const { success, error } = useToasts();

const addresses = ref([]);
const showForm = ref(false);
const editingAddress = ref(null);
const form = ref({
  description: '',
  postal_code: '',
  address: '',
  address_number: '',
  address_complement: '',
  neighborhood: '',
  city: '',
  state: '',
  status: 'active',
  processing: false,
  errors: {},
});

const loadAddresses = async () => {
  try {
    const response = await fetch(`/admin/clients/${props.clientId}/addresses`);
    const data = await response.json();
    addresses.value = data.addresses;
  } catch (err) {
    console.error('Erro ao carregar endereços:', err);
    error('Erro ao carregar endereços.');
  }
};

const openCreateForm = () => {
  editingAddress.value = null;
  form.value = {
    description: '',
    postal_code: '',
    address: '',
    address_number: '',
    address_complement: '',
    neighborhood: '',
    city: '',
    state: '',
    status: 'active',
    processing: false,
    errors: {},
  };
  showForm.value = true;
};

const openEditForm = (address) => {
  editingAddress.value = address;
  form.value = {
    description: address.description,
    postal_code: address.postal_code.replace(/\D/g, ''), // Remove formatting
    address: address.address,
    address_number: address.address_number,
    address_complement: address.address_complement,
    neighborhood: address.neighborhood,
    city: address.city,
    state: address.state,
    status: address.status,
    processing: false,
    errors: {},
  };
  showForm.value = true;
};

const closeForm = () => {
  showForm.value = false;
  editingAddress.value = null;
};

const submitForm = async () => {
  form.value.processing = true;
  form.value.errors = {};

  try {
    const url = editingAddress.value
      ? `/admin/clients/${props.clientId}/addresses/${editingAddress.value.id}`
      : `/admin/clients/${props.clientId}/addresses`;

    const method = editingAddress.value ? 'PATCH' : 'POST';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify(form.value),
    });

    const data = await response.json();

    if (response.ok) {
      success(editingAddress.value ? 'Endereço atualizado com sucesso.' : 'Endereço criado com sucesso.');
      await loadAddresses();
      closeForm();
    } else {
      form.value.errors = data.errors || {};
      error('Erro ao salvar endereço.');
    }
  } catch (err) {
    console.error('Erro ao salvar endereço:', err);
    error('Erro ao salvar endereço.');
  } finally {
    form.value.processing = false;
  }
};

const deleteAddress = async (address) => {
  if (!confirm('Tem certeza que deseja excluir este endereço?')) return;

  try {
    const response = await fetch(`/admin/clients/${props.clientId}/addresses/${address.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    });

    if (response.ok) {
      success('Endereço removido com sucesso.');
      await loadAddresses();
    } else {
      error('Erro ao remover endereço.');
    }
  } catch (err) {
    console.error('Erro ao remover endereço:', err);
    error('Erro ao remover endereço.');
  }
};

onMounted(loadAddresses);
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-slate-700">Endereços</h3>
      <Button @click="openCreateForm" variant="primary">Adicionar Endereço</Button>
    </div>

    <div v-if="addresses.length === 0" class="text-center py-8 text-slate-500">
      Nenhum endereço cadastrado.
    </div>

    <div v-else class="space-y-4">
      <div v-for="address in addresses" :key="address.id" class="border border-slate-200 rounded-lg p-4">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <h4 class="font-semibold text-slate-800">{{ address.description }}</h4>
            <p class="text-sm text-slate-600 mt-1">
              {{ address.address }}, {{ address.address_number }}
              <span v-if="address.address_complement"> - {{ address.address_complement }}</span><br>
              {{ address.neighborhood }}, {{ address.city }} - {{ address.state }}<br>
              CEP: {{ address.postal_code }}
            </p>
            <p class="text-xs text-slate-500 mt-2">
              Status: <span :class="address.status === 'active' ? 'text-green-600' : 'text-red-600'">
                {{ address.status === 'active' ? 'Ativo' : 'Inativo' }}
              </span>
            </p>
          </div>
          <div class="flex gap-2">
            <Button @click="openEditForm(address)" variant="ghost" size="sm">Editar</Button>
            <Button @click="deleteAddress(address)" variant="outline-danger" size="sm">Excluir</Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for Address Form -->
    <div v-if="showForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">
          {{ editingAddress ? 'Editar Endereço' : 'Novo Endereço' }}
        </h3>
        <AddressForm
          :form="form"
          :states="states"
          :submit-label="editingAddress ? 'Atualizar' : 'Criar'"
          cancel-href="#"
          @submit="submitForm"
        />
        <div class="mt-4 flex justify-end">
          <Button @click="closeForm" variant="ghost">Fechar</Button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
