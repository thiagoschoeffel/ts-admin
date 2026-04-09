<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  movementId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(props.modelValue);
const loading = ref(false);
const error = ref(false);
const payload = ref(null); // structured movement data (required)

watch(() => props.modelValue, (v) => { open.value = v; if (v) tryFetch(); });
watch(open, (v) => emit('update:modelValue', v));
watch(() => props.movementId, () => { if (open.value) tryFetch(); });

async function tryFetch() {
  if (!props.movementId) return;
  loading.value = true;
  error.value = false;
  payload.value = null;
  try {
    const res = await fetch(`/admin/inventory/movements/${props.movementId}/modal`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('failed');
    const data = await res.json();
    if (!data || !data.movement) throw new Error('invalid');
    payload.value = data.movement;
  } catch (_) {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

function retry() { tryFetch(); }

function formatNumber(value) {
  if (value === null || value === undefined) return '—';
  return Number(value).toLocaleString('pt-BR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 3
  });
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
  <Modal v-model="open" title="Detalhes do movimento" size="lg" :lockScroll="true" :closeOnBackdrop="true">
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
          <div class="space-y-3">
            <div class="skeleton h-4 w-28 rounded-md"></div>
            <div class="skeleton h-4 w-32 rounded-md"></div>
          </div>
        </div>
      </div>
      <span class="sr-only">Carregando detalhes do movimento...</span>
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 text-center text-sm text-slate-500">
      <p class="text-sm text-rose-600">Não foi possível carregar os detalhes do movimento.</p>
      <Button type="button" variant="secondary" @click="retry">Tentar novamente</Button>
    </div>

    <div v-if="payload" class="space-y-6">
      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Informações gerais</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">ID do movimento</dt>
            <dd class="text-sm text-slate-800">#{{ payload.id }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Data/hora</dt>
            <dd class="text-sm text-slate-800">{{ payload.occurred_at || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Tipo de item</dt>
            <dd class="text-sm text-slate-800">{{ payload.item_type_formatted }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Direção</dt>
            <dd>
              <Badge
                :variant="payload.direction === 'in' ? 'success' : payload.direction === 'out' ? 'danger' : 'warning'">
                {{ payload.direction_formatted }}
              </Badge>
            </dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Quantidade</dt>
            <dd class="text-sm text-slate-800">{{ formatNumber(payload.quantity) }} {{ payload.unit }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Localização</dt>
            <dd class="text-sm text-slate-800">
              {{ payload.location_type_formatted }}
              <span v-if="payload.location_name"> - {{ payload.location_name }}</span>
            </dd>
          </div>
        </dl>
      </section>

      <section v-if="payload.raw_material || payload.block_type || payload.mold_type || payload.dimensions"
        class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Detalhes do item</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div v-if="payload.raw_material" class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Matéria-prima</dt>
            <dd class="text-sm text-slate-800">{{ payload.raw_material.name }}</dd>
          </div>
          <div v-if="payload.block_type" class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Tipo de bloco</dt>
            <dd class="text-sm text-slate-800">{{ payload.block_type.name }}</dd>
          </div>
          <div v-if="payload.mold_type" class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Tipo de molde</dt>
            <dd class="text-sm text-slate-800">{{ payload.mold_type.name }}</dd>
          </div>
          <div v-if="payload.dimensions" class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Dimensões (mm)</dt>
            <dd class="text-sm text-slate-800">
              {{ payload.dimensions.length_mm }} × {{ payload.dimensions.width_mm }} × {{ payload.dimensions.height_mm
              }}
            </dd>
          </div>
        </dl>
      </section>

      <section v-if="payload.reference_formatted || payload.notes" class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Referências e observações</h2>
        <dl class="grid gap-4 sm:grid-cols-1">
          <div v-if="payload.reference_formatted" class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Referência</dt>
            <dd class="text-sm text-slate-800">{{ payload.reference_formatted }}</dd>
          </div>
          <div v-if="payload.notes" class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Observações</dt>
            <dd class="text-sm text-slate-800">{{ payload.notes }}</dd>
          </div>
        </dl>
      </section>

      <section v-if="payload.related_consumption" class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Consumo relacionado</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Matéria-prima consumida</dt>
            <dd class="text-sm text-slate-800">{{ payload.related_consumption.raw_material?.name || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Quantidade consumida</dt>
            <dd class="text-sm text-slate-800">{{ formatNumber(payload.related_consumption.quantity) }} {{
              payload.related_consumption.unit }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Registrado por</dt>
            <dd class="text-sm text-slate-800">{{ payload.related_consumption.created_by || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Data do registro</dt>
            <dd class="text-sm text-slate-800">{{ payload.related_consumption.created_at || '—' }}</dd>
          </div>
        </dl>
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
            <dd class="text-sm text-slate-800">{{ payload.created_at || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Atualizado por</dt>
            <dd class="text-sm text-slate-800">{{ updatedBy }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Atualizado em</dt>
            <dd class="text-sm text-slate-800">{{ lastUpdatedAt || '—' }}</dd>
          </div>
        </dl>
      </section>
    </div>

    <div v-else class="space-y-6"></div>
  </Modal>
</template>
