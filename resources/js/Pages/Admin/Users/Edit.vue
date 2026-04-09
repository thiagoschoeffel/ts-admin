<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import UserForm from '@/components/users/UserForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  resources: { type: Object, required: true },
  user: { type: Object, required: true },
});

// Derive modules state from permissions (any ability true => module enabled)
const deriveModules = (permissions = {}, resources = {}) => {
  const mods = {};
  Object.entries(resources).forEach(([key, res]) => {
    const abilities = Object.keys(res.abilities || {});
    mods[key] = abilities.some((a) => !!(permissions?.[key]?.[a]));
  });
  return mods;
};

const form = useForm({
  name: props.user.name || '',
  email: props.user.email || '',
  status: props.user.status || 'active',
  role: props.user.role || 'user',
  permissions: JSON.parse(JSON.stringify(props.user.permissions || {})),
  modules: deriveModules(props.user.permissions, props.resources),
  password: '',
  password_confirmation: '',
});

const submit = () => {
  const data = {
    name: form.name,
    email: form.email,
    status: form.status,
    role: form.role,
    permissions: form.permissions,
    modules: form.modules,
  };

  // Only include password fields if password is provided
  if (form.password && form.password.trim() !== '') {
    data.password = form.password;
    data.password_confirmation = form.password_confirmation;
  }

  form.patch(`/admin/users/${props.user.id}`, data);
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar usuário" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="users" class="h-7 w-7 text-slate-700" />
            Editar usuário
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize as informações de {{ props.user.name }}.</p>
        </div>
      </div>

      <UserForm :form="form"
                :resources="props.resources"
                :is-edit="true"
                submit-label="Salvar alterações"
                :cancel-href="route('users.index')"
                @submit="submit" />
    </section>
  </AdminLayout>
</template>

