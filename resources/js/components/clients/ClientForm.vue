<script setup>
import axios from 'axios';
import Switch from '@/components/ui/Switch.vue';
import Dropdown from '@/components/Dropdown.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputTextarea from '@/components/InputTextarea.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import { useToasts } from '@/components/toast/useToasts.js';
import { ref, computed, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';
import { formatDocument, formatPhone, formatPostalCode, digitsOnly } from '@/utils/masks.js';

const page = usePage();
const user = computed(() => page.props.auth.user);

const isAdmin = computed(() => user.value?.role === 'admin');
const canCreateClients = computed(() => isAdmin.value || !!user.value?.permissions?.clients?.create);
const canUpdateClients = computed(() => isAdmin.value || !!user.value?.permissions?.clients?.update);

// Se pode criar OU editar clientes, pode gerenciar endereços completamente
const canManageAddresses = computed(() => isAdmin.value || canCreateClients.value || canUpdateClients.value);

const props = defineProps({
  form: { type: Object, required: true },
  states: { type: Array, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
  isEditing: { type: Boolean, default: false },
  clientId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['submit']);

// Sistema de toasts
const { error: toastError, success: toastSuccess } = useToasts();

// Estado para edição inline de endereços
const editingAddressIndex = ref(-1);
const showAddForm = ref(false);
const newAddress = ref({
  description: '',
  postal_code: '',
  address: '',
  address_number: '',
  address_complement: '',
  neighborhood: '',
  city: '',
  state: '',
  status: 'active'
});
const addressErrors = ref({});

// Computed para CEP formatado
const formattedPostalCode = computed({
  get: () => newAddress.value.postal_code,
  set: (value) => {
    newAddress.value.postal_code = formatPostalCode(value);
  }
});

// Estado para confirmação de exclusão de endereço
const deleteAddressState = ref({ open: false, processing: false, addressIndex: null });

// Inicializar endereços se não existir
if (!props.form.addresses) {
  props.form.addresses = [];
}

// Função para buscar endereço via CEP
const fetchAddress = async () => {
  const cep = digitsOnly(newAddress.value.postal_code);
  if (cep.length !== 8) return;

  try {
    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    if (!response.ok) {
      throw new Error('Erro na consulta do CEP');
    }

    const data = await response.json();

    if (data.erro) {
      toastError('CEP não encontrado. Verifique se o CEP está correto.');
      return;
    }

    // Preencher os campos automaticamente
    newAddress.value.address = data.logradouro || '';
    newAddress.value.neighborhood = data.bairro || '';
    newAddress.value.city = data.localidade || '';
    newAddress.value.state = data.uf || '';
    // O CEP já está formatado pelo computed, não precisamos alterar

    // Focar no campo número após preencher
    nextTick(() => {
      const numberField = document.querySelector('input[placeholder="Número"]');
      if (numberField) {
        numberField.focus();
      }
    });

  } catch (error) {
    console.error('Erro ao buscar CEP:', error);
    toastError('Erro ao consultar CEP. Verifique sua conexão e tente novamente.');
  }
};

// Função auxiliar para focar no campo descrição do endereço
const focusFirstAddressField = () => {
  nextTick(() => {
    const descriptionField = document.querySelector('input[placeholder="Ex: Sede, Filial Centro"]');
    if (descriptionField) {
      descriptionField.focus();
    }
  });
};

const formatDocumentField = () => {
  props.form.document = formatDocument(props.form.document, props.form.person_type);
};

const formatPhoneField = (key) => {
  props.form[key] = formatPhone(props.form[key]);
};

// Métodos para gerenciar endereços
const addAddress = async () => {
  // Validação dos campos obrigatórios
  const errors = {};

  if (!newAddress.value.description || newAddress.value.description.trim().length < 4) {
    errors.description = 'Descrição é obrigatória e deve ter pelo menos 4 caracteres';
  }

  if (!newAddress.value.postal_code || digitsOnly(newAddress.value.postal_code).length !== 8) {
    errors.postal_code = 'CEP é obrigatório e deve ter 8 dígitos';
  }

  if (!newAddress.value.address) {
    errors.address = 'Logradouro é obrigatório';
  }

  if (!newAddress.value.address_number) {
    errors.address_number = 'Número é obrigatório';
  }

  if (!newAddress.value.neighborhood) {
    errors.neighborhood = 'Bairro é obrigatório';
  }

  if (!newAddress.value.city) {
    errors.city = 'Cidade é obrigatória';
  }

  if (!newAddress.value.state) {
    errors.state = 'Estado é obrigatório';
  }

  if (!newAddress.value.status) {
    errors.status = 'Status é obrigatório';
  }

  // Se há erros, mostrar toast e não adiciona o endereço
  if (Object.keys(errors).length > 0) {
    addressErrors.value = errors;
    toastError('Verifique os campos obrigatórios do endereço.');
    return;
  }

  // Limpa erros anteriores
  addressErrors.value = {};

  if (props.isEditing) {
    // Modo edição: salvar diretamente no banco
    await saveAddressToDatabase();
  } else {
    // Modo criação: adicionar à lista em memória
    if (editingAddressIndex.value >= 0) {
      // Salvar edição em memória
      // Só permite editar endereços que não têm id real (ou seja, só em memória)
      if (!props.form.addresses[editingAddressIndex.value].id || String(props.form.addresses[editingAddressIndex.value].id).length > 10) {
        props.form.addresses[editingAddressIndex.value] = { ...newAddress.value };
        editingAddressIndex.value = -1;
        toastSuccess('Endereço atualizado com sucesso!');
      } else {
        toastError('Não é possível editar endereços já salvos no banco antes de salvar o cliente.');
      }
    } else {
      // Adicionar novo
      props.form.addresses.push({ ...newAddress.value, id: Date.now() }); // ID temporário
      toastSuccess('Endereço adicionado com sucesso!');
    }
    resetNewAddress();
    showAddForm.value = false;
  }
};

const editAddress = (index) => {
  // No modo edição, só permite editar endereços que já existem no backend (id real)
  if (props.isEditing && (!props.form.addresses[index].id || String(props.form.addresses[index].id).length > 10)) {
    toastError('Salve o cliente antes de editar este endereço.');
    return;
  }
  editingAddressIndex.value = index;
  newAddress.value = { ...props.form.addresses[index] };
  // Garantir que o CEP seja formatado corretamente (remover máscara se já estiver aplicada)
  const cleanPostalCode = newAddress.value.postal_code.replace(/\D/g, '');
  newAddress.value.postal_code = formatPostalCode(cleanPostalCode);
  addressErrors.value = {}; // Limpa erros ao editar
  showAddForm.value = true;
  focusFirstAddressField();
};

const cancelEdit = () => {
  editingAddressIndex.value = -1;
  showAddForm.value = false;
  resetNewAddress();
};

const removeAddress = (index) => {
  if (props.isEditing) {
    // Modo edição: confirmar exclusão que será feita no banco
    confirmDeleteAddress(index);
  } else {
    // Modo criação: remover da lista em memória
    props.form.addresses.splice(index, 1);
    if (editingAddressIndex.value === index) {
      cancelEdit();
    }
  }
};

const confirmDeleteAddress = (index) => {
  deleteAddressState.value = { open: true, processing: false, addressIndex: index };
};

const performDeleteAddress = async () => {
  if (deleteAddressState.value.addressIndex === null) return;

  deleteAddressState.value.processing = true;
  try {
    const index = deleteAddressState.value.addressIndex;
    const address = props.form.addresses[index];

    // Só deleta do backend se o id for real (não temporário)
    const isRealId = address.id && String(address.id).length < 10 && Number.isInteger(Number(address.id));
    if (props.isEditing && isRealId) {
      // Modo edição: deletar do banco
      await deleteAddressFromDatabase(address.id);
    }

    // Remover da lista (tanto em edição quanto criação)
    props.form.addresses.splice(index, 1);
    if (editingAddressIndex.value === index) {
      cancelEdit();
    }

    if (!props.isEditing || !isRealId) {
      toastSuccess('Endereço removido com sucesso!');
    }
  } catch (error) {
    // Se houve erro na deleção do banco, não remove da lista
    console.error('Erro ao deletar endereço:', error);
    toastError('Erro ao remover endereço. Tente novamente.');
  } finally {
    deleteAddressState.value.processing = false;
    deleteAddressState.value.open = false;
    deleteAddressState.value.addressIndex = null;
  }
};

const resetNewAddress = () => {
  newAddress.value = {
    description: '',
    postal_code: '',
    address: '',
    address_number: '',
    address_complement: '',
    neighborhood: '',
    city: '',
    state: '',
    status: 'active'
  };
  addressErrors.value = {}; // Limpa erros ao resetar
};

// Funções para operações no banco de dados (modo edição)
const saveAddressToDatabase = async () => {
  try {
    const addressData = { ...newAddress.value };
    addressData.postal_code = addressData.postal_code.replace(/\D/g, '');
    let result;

    if (editingAddressIndex.value >= 0) {
      // Editando endereço existente
      const addressId = props.form.addresses[editingAddressIndex.value].id;
      const { data } = await axios.patch(`/admin/clients/${props.clientId}/addresses/${addressId}`, addressData);
      result = data;
    } else {
      // Criando novo endereço
      const { data } = await axios.post(`/admin/clients/${props.clientId}/addresses`, addressData);
      result = data;
    }

    if (editingAddressIndex.value >= 0) {
      props.form.addresses[editingAddressIndex.value] = result.address;
      editingAddressIndex.value = -1;
      toastSuccess('Endereço atualizado com sucesso!');
    } else {
      props.form.addresses.push(result.address);
      toastSuccess('Endereço adicionado com sucesso!');
    }

    resetNewAddress();
    showAddForm.value = false;
  } catch (error) {
    console.error('Erro ao salvar endereço:', error);
    toastError(error.message || 'Erro ao salvar endereço. Tente novamente.');
  }
};

const deleteAddressFromDatabase = async (addressId) => {
  try {
    await axios.delete(`/admin/clients/${props.clientId}/addresses/${addressId}`);
    toastSuccess('Endereço excluído com sucesso!');
  } catch (error) {
    console.error('Erro ao excluir endereço:', error);
    // Se o endereço não existe (404), trata como sucesso (já foi removido)
    if (error.response?.status === 404) {
      toastSuccess('Endereço excluído com sucesso!');
      return;
    }
    toastError(error.response?.data?.message || error.message || 'Erro ao excluir endereço. Tente novamente.');
    throw error; // Re-throw para impedir a remoção da lista se falhou no banco
  }
};

const onSubmit = () => emit('submit');

// Computed para verificar se há erros nos endereços
const hasAddressErrors = computed(() => {
  if (!props.form.errors) return false;

  return props.form.addresses?.some((address, index) => {
    const addressErrors = Object.keys(props.form.errors).filter(key => key.startsWith(`addresses.${index}.`));
    return addressErrors.length > 0;
  });
});

// DataTable configuration for addresses
const addressColumns = [
  {
    header: 'Descrição',
    key: 'description',
    formatter: (value) => value || 'Sem descrição'
  },
  {
    header: 'Endereço',
    key: 'address',
    formatter: (value, address) => `${address.address}, ${address.address_number}${address.address_complement ? ` - ${address.address_complement}` : ''}, ${address.neighborhood}, ${address.city}/${address.state}`
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (address) => ({
      variant: address.status === 'active' ? 'success' : 'danger'
    }),
    formatter: (value) => value === 'active' ? 'Ativo' : 'Inativo'
  }
];

const addressActions = computed(() => {
  return (address, index) => {
    const acts = [];
    if (canManageAddresses.value) {
      acts.push({
        key: 'edit',
        label: 'Editar',
        icon: 'pencil'
      });
      acts.push({
        key: 'delete',
        label: 'Excluir',
        icon: 'trash',
        class: 'text-rose-600 hover:text-rose-700'
      });
    }
    return acts;
  };
});

const handleAddressAction = ({ action, item }) => {
  const index = props.form.addresses.indexOf(item);
  if (action.key === 'edit') {
    editAddress(index);
  } else if (action.key === 'delete') {
    confirmDeleteAddress(index);
  }
};
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <label class="form-label">
        Nome *
        <InputText v-model="form.name" required :error="!!form.errors.name" />
        <span v-if="form.errors.name" class="text-sm font-medium text-rose-600">{{ form.errors.name }}</span>
      </label>

      <label class="form-label">
        Tipo de pessoa *
        <InputSelect v-model="form.person_type" :options="[
          { value: 'individual', label: 'Pessoa Física' },
          { value: 'company', label: 'Pessoa Jurídica' }
        ]" placeholder="" required :error="!!form.errors.person_type" @change="formatDocumentField" />
        <span v-if="form.errors.person_type" class="text-sm font-medium text-rose-600">{{ form.errors.person_type }}</span>
      </label>

      <label class="form-label">
        CPF/CNPJ *
        <InputText v-model="form.document" :placeholder="form.person_type === 'individual' ? '000.000.000-00' : '00.000.000/0000-00'" required :error="!!form.errors.document" @input="formatDocumentField" :maxlength="form.person_type === 'company' ? 18 : 14" />
        <span v-if="form.errors.document" class="text-sm font-medium text-rose-600">{{ form.errors.document }}</span>
      </label>

      <div class="switch-field sm:col-span-2 lg:col-span-3">
        <span class="switch-label">Status do cliente</span>
        <Switch v-model="form.status" true-value="active" false-value="inactive" />
        <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
          {{ form.status === 'active' ? 'Ativo' : 'Inativo' }}
        </span>
      </div>
      <span v-if="form.errors.status" class="text-sm font-medium text-rose-600 sm:col-span-2 lg:col-span-3">{{ form.errors.status }}</span>
    </div>

        <label class="form-label">
      Observações
      <InputTextarea v-model="form.observations" :error="!!form.errors.observations" />
      <span v-if="form.errors.observations" class="text-sm font-medium text-rose-600">{{ form.errors.observations }}</span>
    </label>

    <fieldset class="space-y-3">
      <legend class="text-sm font-semibold text-slate-700">Endereços</legend>

      <!-- Formulário inline para adicionar/editar endereço -->
      <div v-if="showAddForm" class="border border-slate-200 rounded-lg p-4 bg-slate-50">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Descrição *
            <InputText v-model="newAddress.description" placeholder="Ex: Sede, Filial Centro" required :error="!!addressErrors.description" />
            <span v-if="addressErrors.description" class="text-sm font-medium text-rose-600">{{ addressErrors.description }}</span>
          </label>
          <label class="form-label">
            CEP *
            <InputText v-model="formattedPostalCode" placeholder="00000-000" required :error="!!addressErrors.postal_code" @blur="fetchAddress" maxlength="9" />
            <span v-if="addressErrors.postal_code" class="text-sm font-medium text-rose-600">{{ addressErrors.postal_code }}</span>
          </label>
          <label class="form-label">
            Logradouro *
            <InputText v-model="newAddress.address" :error="!!addressErrors.address" />
            <span v-if="addressErrors.address" class="text-sm font-medium text-rose-600">{{ addressErrors.address }}</span>
          </label>
          <label class="form-label">
            Número *
            <InputText v-model="newAddress.address_number" :error="!!addressErrors.address_number" />
            <span v-if="addressErrors.address_number" class="text-sm font-medium text-rose-600">{{ addressErrors.address_number }}</span>
          </label>
          <label class="form-label">
            Complemento
            <InputText v-model="newAddress.address_complement" />
          </label>
          <label class="form-label">
            Bairro *
            <InputText v-model="newAddress.neighborhood" :error="!!addressErrors.neighborhood" />
            <span v-if="addressErrors.neighborhood" class="text-sm font-medium text-rose-600">{{ addressErrors.neighborhood }}</span>
          </label>
          <label class="form-label">
            Cidade *
            <InputText v-model="newAddress.city" disabled :error="!!addressErrors.city" />
            <span v-if="addressErrors.city" class="text-sm font-medium text-rose-600">{{ addressErrors.city }}</span>
            <span class="text-xs text-slate-500">Preenchido automaticamente via CEP</span>
          </label>
          <label class="form-label">
            Estado (UF) *
            <InputText v-model="newAddress.state" disabled :error="!!addressErrors.state" />
            <span v-if="addressErrors.state" class="text-sm font-medium text-rose-600">{{ addressErrors.state }}</span>
            <span class="text-xs text-slate-500">Preenchido automaticamente via CEP</span>
          </label>
          <div class="switch-field lg:col-span-3">
            <span class="switch-label">Status do endereço</span>
            <Switch v-model="newAddress.status" true-value="active" false-value="inactive" />
            <span class="switch-status" :class="{ 'inactive': newAddress.status !== 'active' }">
              {{ newAddress.status === 'active' ? 'Ativo' : 'Inativo' }}
            </span>
          </div>
          <div class="flex items-end gap-2 lg:col-span-3">
            <Button v-if="canManageAddresses" type="button" @click="addAddress" variant="primary" size="sm">
              {{ editingAddressIndex >= 0 ? 'Salvar' : 'Adicionar' }}
            </Button>
            <Button type="button" @click="cancelEdit" variant="ghost" size="sm">
              Cancelar
            </Button>
          </div>
        </div>
      </div>

      <!-- Tabela de endereços -->
      <DataTable
        :columns="addressColumns"
        :data="form.addresses || []"
        :actions="addressActions"
        empty-message="Nenhum endereço cadastrado para este cliente."
        @action="handleAddressAction"
      />

      <!-- Botão para adicionar novo endereço -->
      <div v-if="!showAddForm && canManageAddresses" class="flex justify-center pt-4">
        <Button type="button" @click="showAddForm = true; focusFirstAddressField()" variant="ghost" size="sm">
          Adicionar novo endereço
        </Button>
      </div>

      <span v-if="hasAddressErrors" class="text-sm font-medium text-rose-600">Verifique os erros nos endereços.</span>
    </fieldset>

    <fieldset class="space-y-3">
      <legend class="text-sm font-semibold text-slate-700">Contato</legend>
      <div class="grid gap-4 sm:grid-cols-2" id="contact_fields">
        <label class="form-label">
          Nome do contato{{ form.person_type === 'company' ? ' *' : '' }}
          <InputText v-model="form.contact_name" :required="form.person_type === 'company'" :disabled="form.person_type === 'individual'" :error="!!form.errors.contact_name" />
          <span v-if="form.errors.contact_name" class="text-sm font-medium text-rose-600">{{ form.errors.contact_name }}</span>
        </label>
        <label class="form-label">
          Telefone principal{{ form.person_type === 'company' ? ' *' : '' }}
          <InputText v-model="form.contact_phone_primary" :required="form.person_type === 'company'" :error="!!form.errors.contact_phone_primary" @input="formatPhoneField('contact_phone_primary')" maxlength="15" placeholder="(11) 99999-9999" />
          <span v-if="form.errors.contact_phone_primary" class="text-sm font-medium text-rose-600">{{ form.errors.contact_phone_primary }}</span>
        </label>
        <label class="form-label">
          Telefone secundário{{ form.person_type === 'company' ? ' *' : '' }}
          <InputText v-model="form.contact_phone_secondary" :required="form.person_type === 'company'" :error="!!form.errors.contact_phone_secondary" @input="formatPhoneField('contact_phone_secondary')" maxlength="15" placeholder="(11) 99999-9999" />
          <span v-if="form.errors.contact_phone_secondary" class="text-sm font-medium text-rose-600">{{ form.errors.contact_phone_secondary }}</span>
        </label>
        <label class="form-label">
          E-mail{{ form.person_type === 'company' ? ' *' : '' }}
          <InputText type="email" v-model="form.contact_email" :required="form.person_type === 'company'" :error="!!form.errors.contact_email" />
          <span v-if="form.errors.contact_email" class="text-sm font-medium text-rose-600">{{ form.errors.contact_email }}</span>
        </label>
      </div>
    </fieldset>

    <div class="flex flex-wrap gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">{{ submitLabel }}</Button>
      <Button :href="cancelHref" variant="ghost">Cancelar</Button>
    </div>
  </form>

  <ConfirmModal v-model="deleteAddressState.open"
                :processing="deleteAddressState.processing"
                title="Excluir endereço"
                message="Deseja realmente remover este endereço?"
                confirm-text="Excluir"
                variant="danger"
                @confirm="performDeleteAddress" />
</template>

<style scoped>
.form-label { display:flex; flex-direction:column; gap:.5rem; font-weight:600; color:#334155 }
</style>

