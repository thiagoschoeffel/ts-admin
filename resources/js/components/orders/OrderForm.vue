<script setup>
import { ref, computed, nextTick } from 'vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputNumber from '@/components/InputNumber.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { formatCurrency, formatQuantity } from '@/utils/formatters';
import RecentOrders from '@/components/orders/RecentOrders.vue';

const props = defineProps({
  products: { type: Array, required: true },
  modelValue: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  totalItemsQuantity: { type: Number, default: 0 },
  recentOrders: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'add-item', 'commit-quantity', 'remove-item']);

const items = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

// Add product section
const productInput = ref('');
const quantityInput = ref(1);
const productInputRef = ref(null);
const quantityInputRef = ref(null);
const selectedProduct = ref(null);

const productSuggestions = computed(() => {
  if (!productInput.value) return [];
  const query = productInput.value.toLowerCase();
  return props.products.filter(p =>
    p.name.toLowerCase().includes(query) ||
    (p.code && p.code.toLowerCase().includes(query))
  ).slice(0, 10);
});

const handleProductInput = () => {
  const exactMatch = props.products.find(p =>
    p.name.toLowerCase() === productInput.value.toLowerCase() ||
    (p.code && p.code.toLowerCase() === productInput.value.toLowerCase())
  );
  selectedProduct.value = exactMatch || null;
};

const handleProductKeydown = (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (selectedProduct.value) {
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
  nextTick(() => quantityInputRef.value?.focus());
};

const addItem = () => {
  if (!selectedProduct.value || quantityInput.value <= 0) return;
  emit('add-item', {
    product: selectedProduct.value,
    quantity: quantityInput.value,
  });
  productInput.value = '';
  quantityInput.value = 1;
  selectedProduct.value = null;
  nextTick(() => productInputRef.value.focus());
};

// Items management
const originalQuantities = ref({}); // Armazenar valor original por index

const handleQuantityFocus = (index, event) => {
  // Captura o valor atual quando o campo ganha foco
  originalQuantities.value[index] = Number(items.value[index].quantity);
};

const commitItemQuantity = (index, value) => {
  const originalValue = originalQuantities.value[index];
  const newValue = Number(value);

  // Só emite se valor realmente mudou
  if (Math.abs(newValue - originalValue) > 0.001) {
    emit('commit-quantity', index, value, originalValue);
  }
};

const removeItem = (index) => {
  confirmDelete(items.value[index], index);
};

const deleteState = ref({ open: false, processing: false, item: null, index: null });
const confirmDelete = (item, index) => {
  deleteState.value = { open: true, processing: false, item, index };
};
const performDelete = () => {
  if (deleteState.value.index === null) return;
  deleteState.value.processing = true;
  setTimeout(() => {
    emit('remove-item', deleteState.value.index);
    deleteState.value.processing = false;
    deleteState.value.open = false;
    deleteState.value.item = null;
    deleteState.value.index = null;
  }, 100);
};
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: Product input and items list -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Add Product Section -->
      <div class="card">
        <h2 class="text-xl font-semibold mb-6">Adicionar produto</h2>
        <div class="space-y-4">
          <label class="form-label">
            Produto
            <InputText
              ref="productInputRef"
              v-model="productInput"
              @input="handleProductInput"
              @change="handleProductInput"
              @keydown="handleProductKeydown"
              type="text"
              list="products"
              placeholder="Digite o nome ou código do produto..."
              size="lg"
            />
            <datalist id="products">
              <option v-for="product in productSuggestions" :key="product.id" :value="product.name">
                {{ product.name }} ({{ product.code || 'Sem código' }})
              </option>
            </datalist>
          </label>

          <div class="grid grid-cols-2 gap-4">
            <label class="form-label">
              Quantidade
              <InputNumber
                ref="quantityInputRef"
                v-model="quantityInput"
                :formatted="true"
                :precision="2"
                :min="0.01"
                :step="0.01"
                size="lg"
                @enter="addItem"
              />
            </label>
            <div class="flex items-end">
              <Button @click="addItem" variant="primary" size="lg" class="w-full">
                Adicionar (Enter)
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Items List -->
      <div class="card">
        <h2 class="text-xl font-semibold mb-6">Itens do pedido</h2>
        <div class="space-y-0">
          <div
            v-for="(item, index) in items"
            :key="item.id ?? item.product_id ?? index"
            class="flex items-center justify-between p-4"
            :class="{ 'border-t border-slate-200': index > 0, 'bg-slate-50': index % 2 === 0 }"
          >
            <div class="flex-1">
              <h3 class="font-medium text-slate-900 mb-3">{{ item.name }}</h3>
              <div class="flex items-center gap-2 text-sm text-slate-600">
                <span>{{ formatCurrency(item.unit_price) }}</span>
                <span>x</span>
                <InputNumber
                  :data-quantity-index="index"
                  v-model="items[index].quantity"
                  :formatted="true"
                  @focus="(event) => handleQuantityFocus(index, event)"
                  @commit="(val) => commitItemQuantity(index, val)"
                  size="sm"
                  class="w-20"
                  :precision="2"
                  :min="0.01"
                  :step="0.01"
                />
              </div>
            </div>
            <div class="flex items-center gap-4">
              <span class="font-semibold text-slate-900">{{ formatCurrency(item.quantity * item.unit_price) }}</span>
              <Button @click="removeItem(index)" variant="outline-danger" size="sm">
                <HeroIcon name="trash" class="h-5 w-5" />
              </Button>
            </div>
          </div>
          <div v-if="items.length === 0" class="text-center py-8 text-slate-500">
            Nenhum item adicionado ainda.
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Summary -->
    <div class="space-y-6">
      <div class="card">
        <h2 class="text-xl font-semibold mb-6">Resumo</h2>
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-slate-600">Total de itens:</span>
            <span class="font-semibold">{{ formatQuantity(totalItemsQuantity) }}</span>
          </div>
          <div class="flex justify-between items-center text-xl font-bold text-slate-900 border-t border-slate-200 pt-4">
            <span>Total:</span>
            <span>{{ formatCurrency(total) }}</span>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <RecentOrders :recent-orders="recentOrders" />
    </div>
  </div>

  <ConfirmModal v-model="deleteState.open"
                :processing="deleteState.processing"
                title="Remover item"
                :message="deleteState.item ? `Deseja realmente remover ${deleteState.item.name} do pedido?` : ''"
                confirm-text="Remover"
                variant="danger"
                @confirm="performDelete" />
</template>

<style scoped>
</style>
