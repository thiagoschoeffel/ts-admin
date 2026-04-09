<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, getCurrentInstance } from 'vue';
import Dropdown from '@/components/Dropdown.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import UserDetailsModal from '@/components/users/UserDetailsModal.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import Badge from '@/components/Badge.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
  users: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const canCreate = computed(() => isAdmin.value || !!user.value?.permissions?.users?.create);
const canUpdate = computed(() => isAdmin.value || !!user.value?.permissions?.users?.update);
const canDelete = computed(() => isAdmin.value || !!user.value?.permissions?.users?.delete);
const meId = computed(() => page.props.auth?.user?.id);

// Ziggy `route` helper from app globalProperties
const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const filtering = ref(false);
const submitFilters = () => {
  filtering.value = true;
  router.get('/admin/users', { search: search.value, status: status.value }, { preserveState: true, replace: true, onFinish: () => filtering.value = false });
};

const resetFilters = () => {
  search.value = '';
  status.value = '';
  submitFilters();
};

const deleteState = ref({ open: false, processing: false, user: null });
const confirmDelete = (user) => { deleteState.value = { open: true, processing: false, user }; };
const performDelete = async () => {
  if (!deleteState.value.user) return;
  deleteState.value.processing = true;
  try {
    await router.delete(`/admin/users/${deleteState.value.user.id}`);
  } finally {
    deleteState.value.processing = false;
    deleteState.value.open = false;
    deleteState.value.user = null;
  }
};

const details = ref({ open: false, userId: null });
const openDetails = (user) => {
  details.value.userId = user.id;
  details.value.open = true;
};

// DataTable configuration
const columns = [
  {
    header: 'Nome',
    key: 'name',
    component: 'button',
    props: (user) => ({
      type: 'button',
      class: 'font-bold text-blue-600 cursor-pointer',
      onClick: () => openDetails(user)
    })
  },
  {
    header: 'E-mail',
    key: 'email'
  },
  {
    header: 'Perfil',
    key: 'role',
    formatter: (value) => value === 'admin' ? 'Administrador' : 'Usuário comum'
  },
  {
    header: 'Status',
    key: 'status',
    component: Badge,
    props: (user) => ({
      variant: user.status === 'active' ? 'success' : 'danger'
    }),
    formatter: (value) => value === 'active' ? 'Ativo' : 'Inativo'
  }
];

const actions = computed(() => {
  return (user, index, route) => {
    const acts = [];
    if (user.id !== meId.value) {
      if (canUpdate.value) {
        acts.push({
          key: 'edit',
          label: 'Editar',
          icon: 'pencil',
          component: Link,
          props: () => ({
            href: `/admin/users/${user.id}/edit`,
            class: 'menu-panel-link'
          })
        });
      }
      if (canDelete.value) {
        acts.push({
          key: 'delete',
          label: 'Excluir',
          icon: 'trash',
          class: 'menu-panel-link text-rose-600 hover:text-rose-700'
        });
      }
    } else {
      acts.push({
        key: 'profile',
        label: 'Meu perfil',
        icon: 'user-circle',
        component: Link,
        props: () => ({
          href: route('profile.edit'),
          class: 'menu-panel-link'
        })
      });
    }
    return acts;
  };
});

const handleTableAction = ({ action, item }) => {
  if (action.key === 'delete') {
    confirmDelete(item);
  }
};
</script>

<template>
  <AdminLayout>
    <Head title="Usuários" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="users" outline class="h-7 w-7 text-slate-700" />
            Usuários
          </h1>
          <p class="mt-2 text-sm text-slate-500">Gerencie os usuários do sistema ou cadastre novos membros.</p>
        </div>
        <Button v-if="canCreate" variant="primary" :href="route('users.create')">Novo usuário</Button>
      </div>

      <form @submit.prevent="submitFilters" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Buscar por nome ou e-mail
            <InputText
              v-model="search"
              type="text"
              placeholder="Digite para buscar"
            />
          </label>
          <label class="form-label">
            Status
            <InputSelect
              v-model="status"
              :options="[
                { value: '', label: 'Todos' },
                { value: 'active', label: 'Ativos' },
                { value: 'inactive', label: 'Inativos' }
              ]"
              placeholder=""
            />
          </label>
        </div>
        <div class="flex flex-wrap gap-3">
          <Button variant="primary" :loading="filtering" type="submit">
            <HeroIcon name="funnel" class="h-5 w-5" />
            <span v-if="!filtering">Filtrar</span>
            <span v-else>Filtrando…</span>
          </Button>
          <Button variant="ghost" @click="resetFilters">Limpar filtros</Button>
        </div>
      </form>



      <div class="flex items-center justify-end">
        <PerPageSelector :current="users.per_page ?? users.perPage ?? 10" />
      </div>

      <DataTable
        :columns="columns"
        :data="users.data"
        :actions="actions"
        empty-message="Nenhum usuário encontrado."
        @action="handleTableAction"
      />

      <Pagination v-if="users && users.total" :paginator="users" />
    </section>

    <ConfirmModal v-model="deleteState.open"
                  :processing="deleteState.processing"
                  :title="`Excluir usuário`"
                  :message="deleteState.user ? `Tem certeza que deseja remover ${deleteState.user.name}?` : ''"
                  confirm-text="Excluir"
                  variant="danger"
                  @confirm="performDelete" />
  </AdminLayout>

  <UserDetailsModal v-model="details.open" :user-id="details.userId" />
</template>
