<script setup>
import { ref, watch, computed, getCurrentInstance } from 'vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  downtimeId: { type: [Number, String, null], default: null },
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
watch(() => props.downtimeId, () => { if (open.value) tryFetch(); });

async function tryFetch() {
  if (!props.downtimeId) return;
  loading.value = true;
  error.value = false;
  payload.value = null;
  try {
    const res = await fetch(route('machine_downtimes.modal', props.downtimeId), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('failed');
    const data = await res.json();
    if (!data || !data.downtime) throw new Error('invalid');
    payload.value = data.downtime;
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
  <Modal v-model="open" title="Detalhes da Parada de Máquina" size="lg" :lockScroll="true" :closeOnBackdrop="true">
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
      <span class="sr-only">Carregando detalhes da parada de máquina...</span>
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 text-center text-sm text-slate-500">
      <p class="text-sm text-rose-600">Não foi possível carregar os detalhes da parada.</p>
      <Button type="button" variant="secondary" @click="retry">Tentar novamente</Button>
    </div>

    <div v-if="payload" class="space-y-6">
      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Informações gerais</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Máquina</dt>
            <dd class="text-sm text-slate-800">{{ payload.machine_name }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Motivo</dt>
            <dd class="text-sm text-slate-800">{{ payload.reason_name }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Início</dt>
            <dd class="text-sm text-slate-800">{{ formatDate(payload.started_at) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Fim</dt>
            <dd class="text-sm text-slate-800">{{ formatDate(payload.ended_at) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Duração</dt>
            <dd class="text-sm text-slate-800">{{ payload.duration || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Status</dt>
            <dd>
              <Badge :variant="payload.status === 'active' ? 'success' : 'danger'">
                {{ payload.status === 'active' ? 'Ativo' : 'Inativo' }}
              </Badge>
            </dd>
          </div>
          <div class="space-y-1 sm:col-span-2">
            <dt class="text-sm font-semibold text-slate-500">Observações</dt>
            <dd class="text-sm text-slate-800">{{ payload.notes || '—' }}</dd>
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

