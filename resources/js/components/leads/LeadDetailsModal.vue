<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import DataTable from '@/components/DataTable.vue';
import { defineAsyncComponent } from 'vue';
const TimelineScroll = defineAsyncComponent(() => import('@/components/timeline/TimelineScroll.vue'));
import TimelineCard from '@/components/timeline/TimelineCard.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
    modelValue: Boolean,
    leadId: Number,
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const lead = ref(null);
const loading = ref(false);
const error = ref(false);
const payload = ref(null); // structured lead data (required)

watch(() => props.modelValue, (v) => { open.value = v; if (v) tryFetch(); });
watch(open, (v) => emit('update:modelValue', v));
watch(() => props.leadId, () => { if (open.value) tryFetch(); });

async function tryFetch() {
  if (!props.leadId) return;
  loading.value = true;
  error.value = false;
  payload.value = null;
  try {
    const res = await fetch(`/admin/leads/${props.leadId}/modal`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('failed');
    const data = await res.json();
    if (!data || !data.lead) throw new Error('invalid');
    payload.value = data.lead;
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

function formatPhone(phone) {
  if (!phone) return '—';
  const digits = String(phone).replace(/\D/g, '');
  if (digits.length === 11) return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
  if (digits.length === 10) return digits.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
  return phone;
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
  <Modal v-model="open" title="Detalhes do lead" size="lg" :lockScroll="true" :closeOnBackdrop="true">
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
          <div class="space-y-3">
            <div class="skeleton h-4 w-24 rounded-md"></div>
            <div class="skeleton h-4 w-28 rounded-md"></div>
          </div>
        </div>
      </div>
      <span class="sr-only">Carregando detalhes do lead...</span>
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 text-center text-sm text-slate-500">
      <p class="text-sm text-rose-600">Não foi possível carregar os detalhes do lead.</p>
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
            <dt class="text-sm font-semibold text-slate-500">Email</dt>
            <dd class="text-sm text-slate-800">{{ payload.email || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Telefone</dt>
            <dd class="text-sm text-slate-800">{{ formatPhone(payload.phone) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Empresa</dt>
            <dd class="text-sm text-slate-800">{{ payload.company || '—' }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Origem</dt>
            <dd class="text-sm text-slate-800">
              {{ payload.source === 'site' ? 'Site' : payload.source === 'indicacao' ? 'Indicação' : payload.source === 'evento' ? 'Evento' : payload.source === 'manual' ? 'Manual' : payload.source }}
            </dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Status</dt>
            <dd>
              <Badge :variant="payload.status === 'qualified' ? 'success' : payload.status === 'in_contact' ? 'warning' : payload.status === 'new' ? 'info' : 'secondary'">
                {{ payload.status === 'new' ? 'Novo' : payload.status === 'in_contact' ? 'Em contato' : payload.status === 'qualified' ? 'Qualificado' : payload.status === 'discarded' ? 'Descartado' : payload.status }}
              </Badge>
            </dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Dono</dt>
            <dd class="text-sm text-slate-800">{{ payload.owner?.name || '—' }}</dd>
          </div>
        </dl>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-slate-900">Histórico de Interações</h2>
        <div v-if="!payload.interactions || payload.interactions.length === 0" class="mt-4">
          <div class="timeline-container">
            <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
              <HeroIcon name="chat-bubble-left-right" class="w-12 h-12 text-slate-300" />
              <p class="mt-4 text-sm text-slate-500">Nenhuma interação registrada para este lead.</p>
            </div>
          </div>
        </div>
        <div v-else class="mt-4 min-w-0">
          <TimelineScroll aria-label="Linha do tempo de interações">
            <div
              v-for="(interaction, index) in payload.interactions"
              :key="interaction.id || index"
              class="relative flex flex-col items-center flex-shrink-0 min-w-72 max-w-80"
            >
              <TimelineCard
                :interaction="interaction"
                :show-actions="false"
              />
            </div>
          </TimelineScroll>
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
