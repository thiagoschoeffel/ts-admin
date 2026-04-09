<script setup>
import Switch from '@/components/ui/Switch.vue';
import Dropdown from '@/components/Dropdown.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputTextarea from '@/components/InputTextarea.vue';
import InputCurrency from '@/components/InputCurrency.vue';
import InputNumber from '@/components/InputNumber.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import { useToasts } from '@/components/toast/useToasts.js';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import DataTable from '@/components/DataTable.vue';
import { formatPriceInput, initializePriceDisplay, formatCurrency, formatQuantity } from '@/utils/formatters';

const page = usePage();
const user = computed(() => page.props.auth.user);

const isAdmin = computed(() => user.value?.role === 'admin');
const canCreateProducts = computed(() => isAdmin.value || !!user.value?.permissions?.products?.create);
const canUpdateProducts = computed(() => isAdmin.value || !!user.value?.permissions?.products?.update);

const props = defineProps({
  form: { type: Object, required: true },
  products: { type: Array, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
  isEditing: { type: Boolean, default: false },
  productId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['submit']);

// Sistema de toasts
const { error: toastError, success: toastSuccess } = useToasts();

// Estado para edição inline de componentes
const editingComponentIndex = ref(-1);
const showAddForm = ref(false);
const newComponent = ref({
  id: '',
  quantity: 1,
});
const componentErrors = ref({});

// Estado para autocomplete do produto
const productInput = ref('');
const selectedProduct = ref(null);

// Estado para confirmação de exclusão de componente
const deleteComponentState = ref({ open: false, processing: false, componentIndex: null });

// Refs para inputs
const productInputRef = ref(null);
const quantityInputRef = ref(null);

// Inicializar quando o componente for montado
onMounted(() => {
  // Preço agora é gerenciado pelo InputCurrency
});

// Watcher para focar no campo produto quando o formulário abrir
watch(showAddForm, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      productInputRef.value?.focus();
    });
  }
});

// Computed para produtos disponíveis (excluindo o produto atual e componentes já adicionados, exceto o que está sendo editado)
const availableProducts = computed(() => {
  const addedIds = props.form.components
    .map(c => c.id)
    .filter((id, index) => index !== editingComponentIndex.value); // Excluir apenas o componente que não está sendo editado

  if (props.productId) {
    addedIds.push(props.productId);
  }
  return props.products.filter(p => !addedIds.includes(p.id));
});

// Computed para sugestões de produtos no autocomplete
const productSuggestions = computed(() => {
  if (!productInput.value) return [];
  const query = productInput.value.toLowerCase();
  return availableProducts.value.filter(p =>
    p.name.toLowerCase().includes(query) ||
    (p.code && p.code.toLowerCase().includes(query))
  ).slice(0, 10);
});

// Computed para verificar se há ciclos
const hasCycle = (componentId, currentProductId = props.productId) => {
  if (!currentProductId) return false;
  const component = props.products.find(p => p.id == componentId);
  if (!component) return false;
  // Verificar se o componente tem o produto atual em seus componentes
  return component.components?.some(c => c.id == currentProductId) || false;
};

// Funções para autocomplete do produto
const handleProductInput = () => {
  const exactMatch = availableProducts.value.find(p =>
    p.name.toLowerCase() === productInput.value.toLowerCase() ||
    (p.code && p.code.toLowerCase() === productInput.value.toLowerCase())
  );
  selectedProduct.value = exactMatch || null;
  if (exactMatch) {
    newComponent.value.id = exactMatch.id;
  } else {
    newComponent.value.id = '';
  }
};

const handleProductKeydown = (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (selectedProduct.value) {
      // Produto já selecionado, foco no campo quantidade
      quantityInputRef.value?.focus();
    } else if (productSuggestions.value.length > 0) {
      selectProduct(productSuggestions.value[0]);
    } else {
      e.target.blur();
      nextTick(() => productInputRef.value?.focus());
    }
  }
};

const selectProduct = (product) => {
  selectedProduct.value = product;
  productInput.value = product.name;
  newComponent.value.id = product.id;
  nextTick(() => {
    quantityInputRef.value?.focus();
  });
};

// Métodos para gerenciar componentes
const addComponent = async () => {
  // Validação dos campos obrigatórios
  const errors = {};

  if (!selectedProduct.value || !newComponent.value.id) {
    errors.id = 'Produto é obrigatório';
  }

  if (!newComponent.value.quantity || parseFloat(newComponent.value.quantity) <= 0) {
    errors.quantity = 'Quantidade é obrigatória e deve ser maior que zero';
  }

  // Verificar ciclo
  if (newComponent.value.id && hasCycle(newComponent.value.id)) {
    errors.id = 'Este produto não pode ser adicionado devido a dependências circulares';
  }

  // Se há erros, mostrar toast e não adiciona o componente
  if (Object.keys(errors).length > 0) {
    componentErrors.value = errors;
    toastError('Verifique os campos obrigatórios do componente.');
    return;
  }

  // Limpa erros anteriores
  componentErrors.value = {};

  if (props.isEditing) {
    // Modo edição: salvar diretamente no banco
    await saveComponentToDatabase();
  } else {
    // Modo criação: adicionar à lista em memória
    if (editingComponentIndex.value >= 0) {
      // Salvar edição em memória
      props.form.components[editingComponentIndex.value] = { ...newComponent.value };
      editingComponentIndex.value = -1;
      toastSuccess('Componente atualizado com sucesso!');
    } else {
      // Adicionar novo
      props.form.components.unshift({ ...newComponent.value, id: parseInt(newComponent.value.id) });
      toastSuccess('Componente adicionado com sucesso!');
    }
    resetNewComponent();
    showAddForm.value = false;
  }
};

const editComponent = (index) => {
  // No modo edição, só permite editar componentes que já existem no backend (id real)
  if (props.isEditing && (!props.form.components[index].id || String(props.form.components[index].id).length > 10)) {
    toastError('Salve o produto antes de editar este componente.');
    return;
  }
  editingComponentIndex.value = index;
  newComponent.value = {
    ...props.form.components[index],
    id: String(props.form.components[index].id) // Garantir que seja string para o select
  };
  // Setar o texto do produto para o autocomplete
  const product = props.products.find(p => p.id == props.form.components[index].id);
  if (product) {
    productInput.value = product.name;
    selectedProduct.value = product;
  }
  componentErrors.value = {}; // Limpa erros ao editar
  showAddForm.value = true;
};

const cancelEdit = () => {
  editingComponentIndex.value = -1;
  showAddForm.value = false;
  resetNewComponent();
};

const removeComponent = (index) => {
  if (props.isEditing) {
    // Modo edição: confirmar exclusão que será feita no banco
    confirmDeleteComponent(index);
  } else {
    // Modo criação: remover da lista em memória
    props.form.components.splice(index, 1);
    if (editingComponentIndex.value === index) {
      cancelEdit();
    }
  }
};

const confirmDeleteComponent = (index) => {
  deleteComponentState.value = { open: true, processing: false, componentIndex: index };
};

const performDeleteComponent = async () => {
  if (deleteComponentState.value.componentIndex === null) return;

  deleteComponentState.value.processing = true;
  try {
    const index = deleteComponentState.value.componentIndex;
    const component = props.form.components[index];

    // Só deleta do backend se o id for real (não temporário)
    const isRealId = component.id && String(component.id).length < 10 && Number.isInteger(Number(component.id));
    if (props.isEditing && isRealId) {
      // Modo edição: deletar do banco
      await deleteComponentFromDatabase(component.id);
    }

    // Remover da lista (tanto em edição quanto criação)
    props.form.components.splice(index, 1);
    if (editingComponentIndex.value === index) {
      cancelEdit();
    }

    if (!props.isEditing || !isRealId) {
      toastSuccess('Componente removido com sucesso!');
    }
  } catch (error) {
    // Se houve erro na deleção do banco, não remove da lista
    console.error('Erro ao deletar componente:', error);
    toastError('Erro ao remover componente. Tente novamente.');
  } finally {
    deleteComponentState.value.processing = false;
    deleteComponentState.value.open = false;
    deleteComponentState.value.componentIndex = null;
  }
};

const resetNewComponent = () => {
  newComponent.value = {
    id: '',
    quantity: 1,
  };
  productInput.value = '';
  selectedProduct.value = null;
  componentErrors.value = {}; // Limpa erros ao resetar
};

const onSubmit = () => emit('submit');

// Funções para operações no banco de dados (modo edição)
const saveComponentToDatabase = async () => {
  try {
    const componentData = {
      component_id: newComponent.value.id,
      quantity: newComponent.value.quantity
    };
    let result;

    if (editingComponentIndex.value >= 0) {
      // Editando componente existente
      const componentId = props.form.components[editingComponentIndex.value].id;
      const { data } = await axios.patch(`/admin/products/${props.productId}/components/${componentId}`, componentData);
      result = data;
    } else {
      // Criando novo componente
      const { data } = await axios.post(`/admin/products/${props.productId}/components`, componentData);
      result = data;
    }

    if (editingComponentIndex.value >= 0) {
      props.form.components[editingComponentIndex.value] = result.component;
      editingComponentIndex.value = -1;
      toastSuccess('Componente atualizado com sucesso!');
    } else {
      props.form.components.unshift(result.component);
      toastSuccess('Componente adicionado com sucesso!');
    }

    resetNewComponent();
    showAddForm.value = false;
  } catch (error) {
    console.error('Erro ao salvar componente:', error);
    toastError(error.response?.data?.message || error.message || 'Erro ao salvar componente. Tente novamente.');
  }
};

const deleteComponentFromDatabase = async (componentId) => {
  try {
    await axios.delete(`/admin/products/${props.productId}/components/${componentId}`);
    toastSuccess('Componente excluído com sucesso!');
  } catch (error) {
    console.error('Erro ao excluir componente:', error);
    // Se o componente não existe (404), trata como sucesso (já foi removido)
    if (error.response?.status === 404) {
      toastSuccess('Componente excluído com sucesso!');
      return;
    }
    toastError(error.response?.data?.message || error.message || 'Erro ao excluir componente. Tente novamente.');
    throw error; // Re-throw para impedir a remoção da lista se falhou no banco
  }
};

// Computed para verificar se há erros nos componentes
const hasComponentErrors = computed(() => {
  if (!props.form.errors) return false;

  return props.form.components?.some((component, index) => {
    const componentErrors = Object.keys(props.form.errors).filter(key => key.startsWith(`components.${index}.`));
    return componentErrors.length > 0;
  });
});

// DataTable configuration for components
const componentColumns = [
  {
    header: 'Produto',
    key: 'id',
    formatter: (value) => {
      const product = props.products.find(p => p.id == value);
      return product?.name || 'Produto não encontrado';
    }
  },
  {
    header: 'Quantidade',
    key: 'quantity',
    formatter: (value) => formatQuantity(value)
  },
  {
    header: 'Preço Unitário',
    key: 'id',
    formatter: (value) => {
      const product = props.products.find(p => p.id == value);
      return formatCurrency(product?.price || 0);
    }
  },
  {
    header: 'Total',
    key: 'id',
    formatter: (value, component) => {
      const product = props.products.find(p => p.id == value);
      const total = Number(component.quantity) * Number(product?.price || 0);
      return formatCurrency(total);
    }
  }
];

const componentActions = computed(() => {
  return (component, index) => {
    const acts = [];
    if (canUpdateProducts.value) {
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

const handleComponentAction = ({ action, item }) => {
  const index = props.form.components.indexOf(item);
  if (action.key === 'edit') {
    editComponent(index);
  } else if (action.key === 'delete') {
    confirmDeleteComponent(index);
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
        Preço *
        <InputCurrency v-model="form.price" required :error="!!form.errors.price" placeholder="R$ 0,00" />
        <span v-if="form.errors.price" class="text-sm font-medium text-rose-600">{{ form.errors.price }}</span>
      </label>

      <label class="form-label">
        Unidade de Medida *
        <InputSelect v-model="form.unit_of_measure" required :error="!!form.errors.unit_of_measure" :placeholder="null" :options="[
          { value: 'UND', label: 'Unidade (UND)' },
          { value: 'KG', label: 'Quilograma (KG)' },
          { value: 'M2', label: 'Metro Quadrado (M²)' },
          { value: 'M3', label: 'Metro Cúbico (M³)' },
          { value: 'L', label: 'Litro (L)' },
          { value: 'ML', label: 'Mililitro (ML)' },
          { value: 'PCT', label: 'Pacote (PCT)' },
          { value: 'CX', label: 'Caixa (CX)' },
          { value: 'DZ', label: 'Dúzia (DZ)' },
        ]" />
        <span v-if="form.errors.unit_of_measure" class="text-sm font-medium text-rose-600">{{ form.errors.unit_of_measure }}</span>
      </label>

      <div class="switch-field sm:col-span-2 lg:col-span-3">
        <span class="switch-label">Status do produto</span>
        <Switch v-model="form.status" true-value="active" false-value="inactive" />
        <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
          {{ form.status === 'active' ? 'Ativo' : 'Inativo' }}
        </span>
      </div>
      <span v-if="form.errors.status" class="text-sm font-medium text-rose-600 sm:col-span-2 lg:col-span-3">{{ form.errors.status }}</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <label class="form-label">
        Comprimento (cm)
        <InputNumber v-model="form.length" :formatted="true" :precision="2" :min="0" :step="0.01" placeholder="0,00" :error="!!form.errors.length" />
        <span v-if="form.errors.length" class="text-sm font-medium text-rose-600">{{ form.errors.length }}</span>
      </label>

      <label class="form-label">
        Largura (cm)
        <InputNumber v-model="form.width" :formatted="true" :precision="2" :min="0" :step="0.01" placeholder="0,00" :error="!!form.errors.width" />
        <span v-if="form.errors.width" class="text-sm font-medium text-rose-600">{{ form.errors.width }}</span>
      </label>

      <label class="form-label">
        Altura (cm)
        <InputNumber v-model="form.height" :formatted="true" :precision="2" :min="0" :step="0.01" placeholder="0,00" :error="!!form.errors.height" />
        <span v-if="form.errors.height" class="text-sm font-medium text-rose-600">{{ form.errors.height }}</span>
      </label>

      <label class="form-label">
        Peso (kg)
        <InputNumber v-model="form.weight" :formatted="true" :precision="2" :min="0" :step="0.01" placeholder="0,00" :error="!!form.errors.weight" />
        <span v-if="form.errors.weight" class="text-sm font-medium text-rose-600">{{ form.errors.weight }}</span>
      </label>
    </div>

    <label class="form-label">
      Descrição
      <InputTextarea v-model="form.description" :error="!!form.errors.description" />
      <span v-if="form.errors.description" class="text-sm font-medium text-rose-600">{{ form.errors.description }}</span>
    </label>

    <fieldset class="space-y-3">
      <legend class="text-sm font-semibold text-slate-700">Componentes</legend>

      <!-- Formulário inline para adicionar/editar componente -->
      <div v-if="showAddForm" class="border border-slate-200 rounded-lg p-4 bg-slate-50">
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="form-label">
            Produto *
            <InputText
              ref="productInputRef"
              v-model="productInput"
              @input="handleProductInput"
              @change="handleProductInput"
              @keydown="handleProductKeydown"
              type="text"
              list="component-products"
              placeholder="Digite o nome ou código do produto..."
              required
              :error="!!componentErrors.id"
            />
            <datalist id="component-products">
              <option v-for="product in productSuggestions" :key="product.id" :value="product.name">
                {{ product.name }} ({{ product.code || 'Sem código' }})
              </option>
            </datalist>
            <span v-if="componentErrors.id" class="text-sm font-medium text-rose-600">{{ componentErrors.id }}</span>
          </label>
                    <label class="form-label">
            Quantidade *
            <InputNumber ref="quantityInputRef" v-model="newComponent.quantity" :formatted="true" :precision="2" :min="0.01" :step="0.01" required :error="!!componentErrors.quantity" />
            <span v-if="componentErrors.quantity" class="text-sm font-medium text-rose-600">{{ componentErrors.quantity }}</span>
          </label>
          <div class="flex items-end gap-2 sm:col-span-2">
            <Button v-if="canUpdateProducts" type="button" @click="addComponent" variant="primary" size="sm">
              {{ editingComponentIndex >= 0 ? 'Salvar' : 'Adicionar' }}
            </Button>
            <Button type="button" @click="cancelEdit" variant="ghost" size="sm">
              Cancelar
            </Button>
          </div>
        </div>
      </div>

      <!-- Tabela de componentes -->
      <DataTable
        :columns="componentColumns"
        :data="form.components || []"
        :actions="componentActions"
        empty-message="Nenhum componente adicionado."
        @action="handleComponentAction"
      />

      <!-- Botão para adicionar novo componente -->
      <div v-if="!showAddForm && canUpdateProducts" class="flex justify-center pt-4">
        <Button type="button" @click="showAddForm = true" variant="ghost" size="sm">
          Adicionar componente
        </Button>
      </div>

      <span v-if="hasComponentErrors" class="text-sm font-medium text-rose-600">Verifique os erros nos componentes.</span>
    </fieldset>

    <div class="flex flex-wrap gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">{{ submitLabel }}</Button>
      <Button :href="cancelHref" variant="ghost">Cancelar</Button>
    </div>
  </form>

  <ConfirmModal v-model="deleteComponentState.open"
                :processing="deleteComponentState.processing"
                title="Excluir componente"
                message="Deseja realmente remover este componente?"
                confirm-text="Excluir"
                variant="danger"
                @confirm="performDeleteComponent" />
</template>

<style scoped>
.form-label { display:flex; flex-direction:column; gap:.5rem; font-weight:600; color:#334155 }
</style>
