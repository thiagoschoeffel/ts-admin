<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/components/Modal.vue';
import ProductTree from '@/components/products/ProductTree.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import { formatCurrency, formatQuantity } from '@/utils/formatters';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  productId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(props.modelValue);
const loading = ref(false);
const error = ref(false);
const payload = ref(null); // structured product data (required)

watch(() => props.modelValue, (v) => { open.value = v; if (v) tryFetch(); });
watch(open, (v) => emit('update:modelValue', v));
watch(() => props.productId, () => { if (open.value) tryFetch(); });

async function tryFetch() {
  if (!props.productId) return;
  loading.value = true;
  error.value = false;
  payload.value = null;
  try {
    const res = await fetch(`/admin/products/${props.productId}/modal`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('failed');
    const data = await res.json();
    if (!data || !data.product) throw new Error('invalid');
    payload.value = data.product;
  } catch (_) {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

function retry() { tryFetch(); }

function formatDate(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  try {
    return new Intl.DateTimeFormat('pt-BR', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: false,
    }).format(d);
  } catch (_) {
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
  }
}

const createdBy = computed(() => payload.value?.created_by ?? 'Conta removida');
const lastUpdatedAt = computed(() => {
  if (payload.value?.updated_at === payload.value?.created_at) return null;
  return payload.value?.updated_at;
});
const updatedBy = computed(() => {
  if (payload.value?.updated_at === payload.value?.created_at) return 'Nunca atualizado';
  return payload.value?.updated_by ?? 'Conta removida';
});
</script>

<template>
  <Modal v-model="open" title="Detalhes do produto" size="lg" :lockScroll="true" :closeOnBackdrop="true">
    <div v-if="loading" class="space-y-6" aria-hidden="true">
      <div class="space-y-3">
        <div class="skeleton h-6 w-48 rounded-md"></div>
        <div class="skeleton h-4 w-64 rounded-md"></div>
      </div>
      <div class="space-y-4">
        <div class="skeleton h-5 w-40 rounded-md"></div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-3">
            <div class="skeleton h-4 w-24 rounded-md"></div>
            <div class="skeleton h-4 w-32 rounded-md"></div>
          </div>
          <div class="space-y-3">
            <div class="skeleton h-4 w-24 rounded-md"></div>
            <div class="skeleton h-4 w-28 rounded-md"></div>
          </div>
        </div>
      </div>
      <div class="space-y-4">
        <div class="skeleton h-5 w-32 rounded-md"></div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-3">
            <div class="skeleton h-4 w-28 rounded-md"></div>
            <div class="skeleton h-4 w-36 rounded-md"></div>
          </div>
        </div>
      </div>
      <span class="sr-only">Carregando detalhes do produto...</span>
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 text-center text-sm text-slate-500">
      <p class="text-sm text-rose-600">Não foi possível carregar os detalhes do produto.</p>
      <Button type="button" variant="secondary" @click="retry">Tentar novamente</Button>
    </div>

    <div v-if="payload" class="space-y-6">
      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Informações gerais</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Nome</dt>
            <dd class="text-sm text-slate-800">{{ payload.name }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Preço</dt>
            <dd class="text-sm text-slate-800">{{ formatCurrency(payload.price) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Unidade de Medida</dt>
            <dd class="text-sm text-slate-800">{{ payload.unit_of_measure }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Descrição</dt>
            <dd class="text-sm text-slate-800">{{ payload.description || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Comprimento</dt>
            <dd class="text-sm text-slate-800">{{ payload.length ? `${formatQuantity(payload.length)} cm` : '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Largura</dt>
            <dd class="text-sm text-slate-800">{{ payload.width ? `${formatQuantity(payload.width)} cm` : '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Altura</dt>
            <dd class="text-sm text-slate-800">{{ payload.height ? `${formatQuantity(payload.height)} cm` : '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Peso</dt>
            <dd class="text-sm text-slate-800">{{ payload.weight ? `${formatQuantity(payload.weight)} kg` : '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Status</dt>
            <dd>
              <Badge :variant="payload.status === 'active' ? 'success' : 'danger'">
                {{ payload.status === 'active' ? 'Ativo' : 'Inativo' }}
              </Badge>
            </dd>
          </div>
        </dl>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Componentes</h2>
        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
          <ProductTree :nodes="payload.component_tree" />
        </div>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Auditoria</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Criado por</dt>
            <dd class="text-sm text-slate-800">{{ createdBy }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Criado em</dt>
            <dd class="text-sm text-slate-800">{{ formatDate(payload.created_at) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Atualizado por</dt>
            <dd class="text-sm text-slate-800">{{ updatedBy }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Atualizado em</dt>
            <dd class="text-sm text-slate-800">{{ formatDate(lastUpdatedAt) }}</dd>
          </div>
        </dl>
      </section>
    </div>

    <div v-else class="space-y-6"></div>
  </Modal>
</template>
