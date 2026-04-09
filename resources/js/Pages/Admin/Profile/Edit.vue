<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useToasts } from '@/components/toast/useToasts';
import Badge from '@/components/Badge.vue';

const props = defineProps({
  user: Object,
});

const page = usePage();
const { error: toastError } = useToasts();

const form = useForm({
  name: props.user?.name || '',
  email: props.user?.email || '',
  current_password: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.patch('/admin/profile');
};

const confirmDelete = ref(false);
const destroyAccount = () => {
  confirmDelete.value = true;
};

const deleteAccount = () => {
  router.delete('/admin/profile');
};

// Verificar erros de perfil ao montar o componente
onMounted(() => {
  if (page.props.errors?.profile) {
    toastError(page.props.errors.profile);
    confirmDelete.value = false; // Fecha o modal se houver erro ao montar
  }
});

// Watcher para erros que podem aparecer após redirecionamento
watch(() => page.props.errors, (newErrors) => {
  if (newErrors?.profile) {
    toastError(newErrors.profile);
    confirmDelete.value = false; // Fecha o modal se houver erro
  }
}, { deep: true });
</script>

<template>
  <AdminLayout>
    <Head title="Meu perfil" />

    <section class="card mx-auto max-w-2xl space-y-8">
      <div class="space-y-3">
        <h1 class="text-2xl font-semibold text-slate-900">Gerenciar minha conta</h1>
        <p class="text-sm text-slate-500">Atualize suas informações pessoais, e-mail e senha.</p>
      </div>



      <form @submit.prevent="submit" class="space-y-4">
        <label class="form-label">
          Nome
          <InputText
            v-model="form.name"
            type="text"
            required
            autocomplete="name"
            :error="!!form.errors.name"
            placeholder="Digite seu nome completo"
          />
          <span v-if="form.errors.name" class="text-sm font-medium text-rose-600">{{ form.errors.name }}</span>
        </label>

        <label class="form-label">
          <span class="flex items-center gap-2">
            E-mail
            <Badge :variant="props.user?.email_verified_at ? 'success' : 'danger'">
              {{ props.user?.email_verified_at ? 'Verificado' : 'Não verificado' }}
            </Badge>
          </span>
          <InputText
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            :error="!!form.errors.email"
            placeholder="Digite seu e-mail"
          />
          <span v-if="form.errors.email" class="text-sm font-medium text-rose-600">{{ form.errors.email }}</span>
        </label>

        <label class="form-label">
          Perfil de acesso
          <InputText
            :model-value="props.user?.role === 'admin' ? 'Administrador' : 'Usuário comum'"
            disabled
            readonly
          />
        </label>

        <div class="h-px bg-slate-200"></div>

        <div class="space-y-2">
          <h2 class="text-lg font-semibold text-slate-900">Alterar senha</h2>
          <p class="text-sm text-slate-500">Informe sua senha atual para definir uma nova. Deixe em branco para manter a senha existente.</p>
        </div>

        <label class="form-label">
          Senha atual
          <InputText
            v-model="form.current_password"
            type="password"
            autocomplete="current-password"
            :error="!!form.errors.current_password"
            placeholder="Digite sua senha atual"
          />
          <span v-if="form.errors.current_password" class="text-sm font-medium text-rose-600">{{ form.errors.current_password }}</span>
        </label>

        <label class="form-label">
          Nova senha
          <InputText
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            :error="!!form.errors.password"
            placeholder="Digite sua nova senha"
          />
          <span v-if="form.errors.password" class="text-sm font-medium text-rose-600">{{ form.errors.password }}</span>
        </label>

        <label class="form-label">
          Confirmar nova senha
          <InputText
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            placeholder="Confirme sua nova senha"
          />
        </label>

        <Button variant="primary" :loading="form.processing" type="submit">Salvar alterações</Button>
      </form>

      <div class="h-px bg-slate-200"></div>

      <div class="space-y-4 rounded-xl border border-rose-100 bg-rose-50 p-6">
        <div class="space-y-2">
          <h2 class="text-lg font-semibold text-rose-700">Excluir conta</h2>
          <p class="text-sm text-rose-600">Esta ação é permanente. Ao confirmar, sua conta será removida e você será desconectado imediatamente.</p>
        </div>
        <Button variant="danger" :loading="form.processing" @click="destroyAccount">Excluir minha conta</Button>
        <ConfirmModal v-model="confirmDelete"
                      title="Excluir conta"
                      message="Tem certeza que deseja remover sua conta? Esta ação não pode ser desfeita."
                      confirm-text="Excluir"
                      variant="danger"
                      :processing="form.processing"
                      @confirm="deleteAccount" />
      </div>
    </section>
  </AdminLayout>

</template>
