<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputTextarea from '@/components/InputTextarea.vue';
import InputCurrency from '@/components/InputCurrency.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import InputNumber from '@/components/InputNumber.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import DataTable from '@/components/DataTable.vue';
import Switch from '@/components/ui/Switch.vue';
import { formatCurrency } from '@/utils/formatters.js';

const props = defineProps({
  form: { type: Object, required: true },
  leads: { type: Array, required: true },
  clients: { type: Array, required: true },
  products: { type: Array, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
});

const emit = defineEmits(['submit']);

const leadInput = ref('');
const clientInput = ref('');
const selectedLead = ref(null);
const selectedClient = ref(null);

// Estado para edição inline de itens
const editingItemIndex = ref(-1);
const showAddForm = ref(false);
const newItem = ref({
  product_id: '',
  quantity: 1,
  unit_price: '',
});
const itemErrors = ref({});

// Estado para autocomplete do produto
const productInput = ref('');
const selectedProduct = ref(null);

// Refs para inputs
const productInputRef = ref(null);
const quantityInputRef = ref(null);
const unitPriceInputRef = ref(null);

// Computed para sugestões de autocomplete
const leadSuggestions = computed(() => {
  if (!leadInput.value) return [];
  const query = leadInput.value.toLowerCase();
  return props.leads.filter(lead =>
    lead.name.toLowerCase().includes(query)
  ).slice(0, 10);
});

const clientSuggestions = computed(() => {
  if (!clientInput.value) return [];
  const query = clientInput.value.toLowerCase();
  return props.clients.filter(client =>
    client.name.toLowerCase().includes(query)
  ).slice(0, 10);
});

// Computed para sugestões de produtos no autocomplete
const productSuggestions = computed(() => {
  if (!productInput.value) return [];
  const query = productInput.value.toLowerCase();
  return props.products.filter(product =>
    product.name.toLowerCase().includes(query)
  ).slice(0, 10);
});

// Funções para autocomplete
const handleLeadInput = () => {
  const exactMatch = props.leads.find(lead =>
    lead.name.toLowerCase() === leadInput.value.toLowerCase()
  );
  selectedLead.value = exactMatch || null;
  if (exactMatch) {
    form.lead_id = exactMatch.id;
  } else {
    form.lead_id = '';
  }
};

const handleClientInput = () => {
  const exactMatch = props.clients.find(client =>
    client.name.toLowerCase() === clientInput.value.toLowerCase()
  );
  selectedClient.value = exactMatch || null;
  if (exactMatch) {
    form.client_id = exactMatch.id;
  } else {
    form.client_id = '';
  }
};

const handleLeadKeydown = (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (selectedLead.value) {
      // Lead já selecionado, foco no próximo campo
    } else if (leadSuggestions.value.length > 0) {
      selectLead(leadSuggestions.value[0]);
    } else {
      e.target.blur();
    }
  }
};

const handleClientKeydown = (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (selectedClient.value) {
      // Client já selecionado, foco no próximo campo
    } else if (clientSuggestions.value.length > 0) {
      selectClient(clientSuggestions.value[0]);
    } else {
      e.target.blur();
    }
  }
};

const selectLead = (lead) => {
  selectedLead.value = lead;
  leadInput.value = lead.name;
  form.lead_id = lead.id;
};

const selectClient = (client) => {
  selectedClient.value = client;
  clientInput.value = client.name;
  form.client_id = client.id;
};

// Funções para autocomplete do produto
const handleProductInput = () => {
  const exactMatch = props.products.find(product =>
    product.name.toLowerCase() === productInput.value.toLowerCase()
  );
  selectedProduct.value = exactMatch || null;
  if (exactMatch) {
    newItem.value.product_id = exactMatch.id;
  } else {
    newItem.value.product_id = '';
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
    }
  }
};

const selectProduct = (product) => {
  selectedProduct.value = product;
  productInput.value = product.name;
  newItem.value.product_id = product.id;
  nextTick(() => {
    quantityInputRef.value?.focus();
  });
};

// Watcher para inicializar inputs quando o form for carregado
watch(() => props.form, (newForm) => {
  if (newForm.lead_id) {
    const lead = props.leads.find(l => l.id == newForm.lead_id);
    if (lead) {
      selectedLead.value = lead;
      leadInput.value = lead.name;
    }
  }
  if (newForm.client_id) {
    const client = props.clients.find(c => c.id == newForm.client_id);
    if (client) {
      selectedClient.value = client;
      clientInput.value = client.name;
    }
  }
}, { immediate: true });

// Watcher para focar no campo produto quando o formulário abrir
watch(showAddForm, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      productInputRef.value?.focus();
    });
  }
});

const onSubmit = () => emit('submit');

// Métodos para gerenciar itens
const addItem = () => {
  // Validação dos campos obrigatórios
  const errors = {};

  if (!selectedProduct.value || !newItem.value.product_id) {
    errors.product_id = 'Produto é obrigatório';
  }

  if (!newItem.value.quantity || parseFloat(newItem.value.quantity) <= 0) {
    errors.quantity = 'Quantidade é obrigatória e deve ser maior que zero';
  }

  if (!newItem.value.unit_price || parseFloat(newItem.value.unit_price) < 0) {
    errors.unit_price = 'Preço unitário é obrigatório e deve ser maior ou igual a zero';
  }

  // Se há erros, mostrar e não adiciona o item
  if (Object.keys(errors).length > 0) {
    itemErrors.value = errors;
    return;
  }

  // Limpa erros anteriores
  itemErrors.value = {};

  if (editingItemIndex.value >= 0) {
    // Salvar edição
    props.form.items[editingItemIndex.value] = { ...newItem.value };
    updateSubtotal(editingItemIndex.value);
    editingItemIndex.value = -1;
  } else {
    // Adicionar novo
    props.form.items.unshift({ ...newItem.value });
    updateSubtotal(0);
  }
  resetNewItem();
  showAddForm.value = false;
};

const editItem = (index) => {
  editingItemIndex.value = index;
  newItem.value = { ...props.form.items[index] };
  // Setar o texto do produto para o autocomplete
  const product = props.products.find(p => p.id == props.form.items[index].product_id);
  if (product) {
    productInput.value = product.name;
    selectedProduct.value = product;
  }
  itemErrors.value = {}; // Limpa erros ao editar
  showAddForm.value = true;
};

const cancelEdit = () => {
  editingItemIndex.value = -1;
  showAddForm.value = false;
  resetNewItem();
};

const removeItem = (index) => {
  props.form.items.splice(index, 1);
  if (editingItemIndex.value === index) {
    cancelEdit();
  }
};

const resetNewItem = () => {
  newItem.value = {
    product_id: '',
    quantity: 1,
    unit_price: '',
  };
  productInput.value = '';
  selectedProduct.value = null;
  itemErrors.value = {}; // Limpa erros ao resetar
};

const updateSubtotal = (index) => {
  const item = props.form.items[index];
  item.subtotal = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
};

// DataTable configuration for items
const itemColumns = [
  {
    header: 'Produto',
    key: 'product_id',
    formatter: (value) => {
      const product = props.products.find(p => p.id == value);
      return product?.name || 'Produto não encontrado';
    }
  },
  {
    header: 'Quantidade',
    key: 'quantity',
    formatter: (value) => value
  },
  {
    header: 'Preço Unitário',
    key: 'unit_price',
    formatter: (value) => formatCurrency(value)
  },
  {
    header: 'Subtotal',
    key: 'subtotal',
    formatter: (value) => formatCurrency(value)
  }
];

const itemActions = [
  {
    key: 'edit',
    label: 'Editar',
    icon: 'pencil'
  },
  {
    key: 'delete',
    label: 'Excluir',
    icon: 'trash',
    class: 'text-rose-600 hover:text-rose-700'
  }
];

const handleItemAction = ({ action, item }) => {
  const index = props.form.items.indexOf(item);
  if (action.key === 'edit') {
    editItem(index);
  } else if (action.key === 'delete') {
    removeItem(index);
  }
};
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
      <label class="form-label">
        Lead *
        <InputText
          v-model="leadInput"
          @input="handleLeadInput"
          @change="handleLeadInput"
          @keydown="handleLeadKeydown"
          type="text"
          list="leads"
          placeholder="Digite o nome do lead..."
          required
          :error="!!form.errors.lead_id"
        />
        <datalist id="leads">
          <option v-for="lead in leadSuggestions" :key="lead.id" :value="lead.name">
            {{ lead.name }}
          </option>
        </datalist>
        <span v-if="form.errors.lead_id" class="text-sm font-medium text-rose-600">{{ form.errors.lead_id }}</span>
      </label>
      <label class="form-label">
        Cliente *
        <InputText
          v-model="clientInput"
          @input="handleClientInput"
          @change="handleClientInput"
          @keydown="handleClientKeydown"
          type="text"
          list="clients"
          placeholder="Digite o nome do cliente..."
          required
          :error="!!form.errors.client_id"
        />
        <datalist id="clients">
          <option v-for="client in clientSuggestions" :key="client.id" :value="client.name">
            {{ client.name }}
          </option>
        </datalist>
        <span v-if="form.errors.client_id" class="text-sm font-medium text-rose-600">{{ form.errors.client_id }}</span>
      </label>
      <label class="form-label">
        Título *
        <InputText v-model="form.title" required :error="!!form.errors.title" />
        <span v-if="form.errors.title" class="text-sm font-medium text-rose-600">{{ form.errors.title }}</span>
      </label>
      <label class="form-label">
        Etapa *
        <InputSelect v-model="form.stage" :options="[
          { value: 'new', label: 'Novo' },
          { value: 'contact', label: 'Contato' },
          { value: 'proposal', label: 'Proposta' },
          { value: 'negotiation', label: 'Negociação' },
          { value: 'won', label: 'Ganho' },
          { value: 'lost', label: 'Perdido' }
        ]" :error="!!form.errors.stage" :placeholder="null" />
        <span v-if="form.errors.stage" class="text-sm font-medium text-rose-600">{{ form.errors.stage }}</span>
      </label>
      <label class="form-label">
        Probabilidade (%)
        <InputNumber v-model="form.probability" :formatted="true" :precision="0" :min="0" :max="100" :step="1" placeholder="0" :error="!!form.errors.probability" />
        <span v-if="form.errors.probability" class="text-sm font-medium text-rose-600">{{ form.errors.probability }}</span>
      </label>
      <label class="form-label">
        Valor Estimado
        <InputCurrency v-model="form.expected_value" placeholder="R$ 0,00" :error="!!form.errors.expected_value" />
        <span v-if="form.errors.expected_value" class="text-sm font-medium text-rose-600">{{ form.errors.expected_value }}</span>
      </label>
      <label class="form-label">
        Data Prevista de Fechamento
        <InputDatePicker v-model="form.expected_close_date" placeholder="Selecionar data" :error="!!form.errors.expected_close_date" />
        <span v-if="form.errors.expected_close_date" class="text-sm font-medium text-rose-600">{{ form.errors.expected_close_date }}</span>
      </label>
      <div class="switch-field sm:col-span-2">
        <span class="switch-label">Status da oportunidade</span>
        <Switch v-model="form.status" true-value="active" false-value="inactive" />
        <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
          {{ form.status === 'active' ? 'Ativa' : 'Inativa' }}
        </span>
      </div>
      <span v-if="form.errors.status" class="text-sm font-medium text-rose-600 sm:col-span-2">{{ form.errors.status }}</span>
    </div>

    <label class="form-label">
      Descrição
      <InputTextarea v-model="form.description" :error="!!form.errors.description" />
      <span v-if="form.errors.description" class="text-sm font-medium text-rose-600">{{ form.errors.description }}</span>
    </label>

    <!-- Itens da oportunidade -->
    <fieldset class="space-y-3">
      <legend class="text-sm font-semibold text-slate-700">Itens da oportunidade</legend>

      <!-- Formulário inline para adicionar/editar item -->
      <div v-if="showAddForm" class="border border-slate-200 rounded-lg p-4 bg-slate-50">
        <div class="grid gap-4 sm:grid-cols-3">
          <label class="form-label">
            Produto *
            <InputText
              ref="productInputRef"
              v-model="productInput"
              @input="handleProductInput"
              @change="handleProductInput"
              @keydown="handleProductKeydown"
              type="text"
              list="opportunity-products"
              placeholder="Digite o nome do produto..."
              required
              :error="!!itemErrors.product_id"
            />
            <datalist id="opportunity-products">
              <option v-for="product in productSuggestions" :key="product.id" :value="product.name">
                {{ product.name }}
              </option>
            </datalist>
            <span v-if="itemErrors.product_id" class="text-sm font-medium text-rose-600">{{ itemErrors.product_id }}</span>
          </label>
          <label class="form-label">
            Quantidade *
            <InputNumber ref="quantityInputRef" v-model="newItem.quantity" :formatted="true" :precision="2" :min="0.01" :step="0.01" required :error="!!itemErrors.quantity" />
            <span v-if="itemErrors.quantity" class="text-sm font-medium text-rose-600">{{ itemErrors.quantity }}</span>
          </label>
          <label class="form-label">
            Preço Unitário *
            <InputCurrency ref="unitPriceInputRef" v-model="newItem.unit_price" required :error="!!itemErrors.unit_price" />
            <span v-if="itemErrors.unit_price" class="text-sm font-medium text-rose-600">{{ itemErrors.unit_price }}</span>
          </label>
          <div class="flex items-end gap-2 sm:col-span-3">
            <Button type="button" @click="addItem" variant="primary" size="sm">
              {{ editingItemIndex >= 0 ? 'Salvar' : 'Adicionar' }}
            </Button>
            <Button type="button" @click="cancelEdit" variant="ghost" size="sm">
              Cancelar
            </Button>
          </div>
        </div>
      </div>

      <!-- Tabela de itens -->
      <DataTable
        :columns="itemColumns"
        :data="form.items || []"
        :actions="itemActions"
        empty-message="Nenhum item adicionado."
        @action="handleItemAction"
      />

      <!-- Botão para adicionar novo item -->
      <div v-if="!showAddForm" class="flex justify-center pt-4">
        <Button type="button" @click="showAddForm = true" variant="ghost" size="sm">
          Adicionar item
        </Button>
      </div>

      <span v-if="form.errors && Object.keys(form.errors).some(key => key.startsWith('items.'))" class="text-sm font-medium text-rose-600">Verifique os erros nos itens.</span>
    </fieldset>

    <div class="flex flex-wrap gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">
        <HeroIcon name="check" class="h-5 w-5" />
        <span v-if="!form.processing">{{ submitLabel }}</span>
        <span v-else>Salvando…</span>
      </Button>
      <Button type="button" variant="ghost" :href="cancelHref">Cancelar</Button>
    </div>
  </form>
</template>
