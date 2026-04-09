<script setup>
import { ref, watch, computed, getCurrentInstance } from 'vue';
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  blockDispatchId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['update:modelValue']);

const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const open = ref(props.modelValue);
const loading = ref(false);
const error = ref(false);
const payload = ref(null);

watch(() => props.modelValue, (v) => { open.value = v; if (v) tryFetch(); });
watch(open, (v) => emit('update:modelValue', v));
watch(() => props.blockDispatchId, () => { if (open.value) tryFetch(); });

async function tryFetch() {
  if (!props.blockDispatchId) return;
  loading.value = true;
  error.value = false;
  payload.value = null;
  try {
    const res = await fetch(route('block-dispatches.modal', props.blockDispatchId), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('failed');
    const data = await res.json();
    if (!data || !data.blockDispatch) throw new Error('invalid');
    payload.value = data.blockDispatch;
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

const nf2 = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const nf3 = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
const nf0 = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 0 });

function toNumber(value) {
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
}

function calcM3(it) {
  const length = toNumber(it?.length_mm);
  const width = toNumber(it?.width_mm);
  const height = toNumber(it?.height_mm);
  if (!length || !width || !height) return null;
  return (length / 1000) * (width / 1000) * (height / 1000);
}

function calcDensity(it) {
  const m3 = calcM3(it);
  const weightKg = toNumber(it?.weight);
  if (!m3 || !weightKg || m3 <= 0) return null;
  return weightKg / m3;
}
</script>

<template>
  <Modal v-model="open" title="Detalhes da saída de blocos" size="lg" :lockScroll="true" :closeOnBackdrop="true">
    <div v-if="loading" class="space-y-6" aria-hidden="true">
      <div class="space-y-3">
        <div class="skeleton h-6 w-56 rounded-md"></div>
        <div class="skeleton h-4 w-72 rounded-md"></div>
      </div>
      <span class="sr-only">Carregando detalhes da saída de blocos...</span>
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 text-center text-sm text-slate-500">
      <p class="text-sm text-rose-600">Não foi possível carregar os detalhes da saída.</p>
      <Button type="button" variant="secondary" @click="retry">Tentar novamente</Button>
    </div>

    <div v-if="payload" class="space-y-6">
      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Informações gerais</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">ID</dt>
            <dd class="text-sm text-slate-800">{{ payload.id }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Data/Hora</dt>
            <dd class="text-sm text-slate-800">{{ formatDate(payload.dispatched_at) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Ordem de fabricação</dt>
            <dd class="text-sm text-slate-800">{{ payload.manufacturing_order_number ?? '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Requisição</dt>
            <dd class="text-sm text-slate-800">
              <span v-if="payload.production_pointing_id">#{{ payload.production_pointing_id }}</span>
              <span v-else>—</span>
              <span v-if="payload.sheet_number != null" class="text-slate-500"> (Ficha {{ payload.sheet_number }})</span>
            </dd>
          </div>
        </dl>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Itens</h2>
        <div v-if="(payload.items || []).length === 0" class="text-sm text-slate-500">—</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="text-left text-slate-600">
              <tr>
                <th class="py-2 pr-4">Bloco</th>
                <th class="py-2 pr-4">Tipo</th>
                <th class="py-2 pr-4">Ficha</th>
                <th class="py-2 pr-4">Dimensão (mm)</th>
                <th class="py-2 pr-4">Peso (kg)</th>
                <th class="py-2 pr-4">m³</th>
                <th class="py-2 pr-4">Densidade (kg/m³)</th>
                <th class="py-2 pr-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="it in payload.items" :key="it.block_production_id">
                <td class="py-2 pr-4 font-semibold text-slate-900">#{{ it.block_production_id }}</td>
                <td class="py-2 pr-4 text-slate-700">{{ it.block_type_name ?? '—' }}</td>
                <td class="py-2 pr-4 text-slate-700">{{ it.sheet_number ?? '—' }}</td>
                <td class="py-2 pr-4 text-slate-700">
                  <span v-if="it.length_mm != null && it.width_mm != null && it.height_mm != null">
                    {{ it.length_mm }} x {{ it.width_mm }} x {{ it.height_mm }}
                  </span>
                  <span v-else>—</span>
                </td>
                <td class="py-2 pr-4 text-slate-700">{{ it.weight != null ? nf2.format(it.weight) : '—' }}</td>
                <td class="py-2 pr-4 text-slate-700">
                  <span v-if="calcM3(it) != null">{{ nf3.format(calcM3(it)) }}</span>
                  <span v-else>—</span>
                </td>
                <td class="py-2 pr-4 text-slate-700">
                  <span v-if="calcDensity(it) != null">{{ nf0.format(calcDensity(it)) }}</span>
                  <span v-else>—</span>
                </td>
                <td class="py-2 pr-4">
                  <span v-if="it.is_scrap" class="text-xs font-semibold text-rose-700">Refugo</span>
                  <span v-else class="text-xs font-semibold text-emerald-700">OK</span>
                </td>
              </tr>
            </tbody>
          </table>
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
