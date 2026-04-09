<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  userId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(props.modelValue);
const loading = ref(false);
const error = ref(false);
const payload = ref(null); // structured user data (required)

watch(() => props.modelValue, (v) => { open.value = v; if (v) tryFetch(); });
watch(open, (v) => emit('update:modelValue', v));
watch(() => props.userId, () => { if (open.value) tryFetch(); });

async function tryFetch() {
  if (!props.userId) return;
  loading.value = true;
  error.value = false;
  payload.value = null;
  try {
    const res = await fetch(`/admin/users/${props.userId}/modal`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('failed');
    const data = await res.json();
    if (!data || !data.user) throw new Error('invalid');
    payload.value = data.user;
  } catch (_) {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

function retry() { tryFetch(); }

const isAdmin = computed(() => {
  if (!payload.value) return false;
  return payload.value.is_admin === true || payload.value.role === 'admin';
});

const groupedPermissions = computed(() => {
  const perms = payload.value?.permissions ?? {};
  // perms expected shape: { resourceKey: { ability: true, ... }, ... }
  return Object.keys(perms).map((resource) => {
    const abilitiesObj = perms[resource] || {};
    const granted = Object.keys(abilitiesObj).filter((a) => abilitiesObj[a]).map(abilityLabel);
    return [resource, granted];
  });
});

const lastUpdatedAt = computed(() => {
  if (payload.value?.updated_at === payload.value?.created_at) return null;
  return payload.value?.updated_at;
});

function abilityLabel(key) {
  const map = {
    create: 'Criar',
    view: 'Visualizar',
    read: 'Visualizar',
    update: 'Editar',
    delete: 'Excluir',
    list: 'Listar',
    manage: 'Gerenciar',
    restore: 'Restaurar',
  };
  if (map[key]) return map[key];
  // fallback: humanize
  return String(key).replace(/[_-]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function resourceLabel(key) {
  if (!key) return key;
  // humanize resource key: users -> Usuários, clients -> Clientes (basic)
  // naive plural handling: capitalize and replace underscores
  const human = String(key).replace(/[_-]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
  return human;
}

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
    // fallback
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
  }
}
</script>

<template>
  <Modal v-model="open" title="Detalhes do usuário" size="lg" :lockScroll="true" :closeOnBackdrop="true">
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
      <span class="sr-only">Carregando detalhes do usuário...</span>
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 text-center text-sm text-slate-500">
      <p class="text-sm text-rose-600">Não foi possível carregar os detalhes do usuário.</p>
      <button type="button" class="btn-secondary" @click="retry">Tentar novamente</button>
    </div>

    <div v-if="payload" class="space-y-6">
      <header class="space-y-2">
        <h2 id="user-details-modal-title" class="text-xl font-semibold text-slate-900">{{ payload.name }}</h2>
      </header>

      <section class="space-y-3">
        <h3 class="text-lg font-semibold text-slate-900">Informações gerais</h3>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Status</dt>
            <dd>
              <Badge :variant="payload.status === 'active' ? 'success' : 'danger'">
                {{ payload.status === 'active' ? 'Ativo' : 'Inativo' }}
              </Badge>
            </dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">E-mail</dt>
            <dd class="text-sm text-slate-800">
              {{ payload.email }}
              <Badge :variant="payload.email_verified_at ? 'success' : 'danger'" class="ml-2">
                {{ payload.email_verified_at ? 'Verificado' : 'Não verificado' }}
              </Badge>
            </dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Perfil</dt>
            <dd class="text-sm text-slate-800">{{ payload.role === 'admin' ? 'Administrador' : 'Usuário comum' }}</dd>
          </div>
        </dl>
      </section>

      <section class="space-y-3">
        <h3 class="text-lg font-semibold text-slate-900">Permissões</h3>
        <div v-if="isAdmin" class="text-sm text-slate-600">Administrador: todas as permissões habilitadas.</div>
        <div v-else>
          <dl class="space-y-3">
            <template v-for="([resource, granted]) in groupedPermissions" :key="resource">
              <div class="space-y-1">
                <dt class="text-sm font-semibold text-slate-500">{{ resourceLabel(resource) }}</dt>
                <dd class="text-sm text-slate-800">
                  <span v-if="!granted || granted.length === 0" class="text-slate-400">Nenhuma permissão</span>
                  <span v-else>{{ granted.join(', ') }}</span>
                </dd>
              </div>
            </template>
          </dl>
        </div>
      </section>

      <section class="space-y-3">
        <h3 class="text-lg font-semibold text-slate-900">Auditoria</h3>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Criado em</dt>
            <dd class="text-sm text-slate-800">{{ formatDate(payload.created_at) }}</dd>
          </div>
          <div class="space-y-1">
            <dt class="text-sm font-semibold text-slate-500">Atualizado em</dt>
            <dd class="text-sm text-slate-800">{{ formatDate(lastUpdatedAt) }}</dd>
          </div>
        </dl>
      </section>
    </div>
  </Modal>
</template>

