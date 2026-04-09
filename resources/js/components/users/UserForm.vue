<script setup>
import Switch from '@/components/ui/Switch.vue';
import Button from '@/components/Button.vue';
import PermissionsMatrix from '@/components/users/PermissionsMatrix.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  form: { type: Object, required: true },
  resources: { type: Object, required: true },
  isEdit: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
});

const emit = defineEmits(['submit']);

const onSubmit = () => emit('submit');
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="form-label">
        Nome *
        <InputText v-model="form.name" required autocomplete="name" :error="!!form.errors.name" />
        <span v-if="form.errors.name" class="text-sm font-medium text-rose-600">{{ form.errors.name }}</span>
      </label>

      <label class="form-label">
        E-mail *
        <InputText type="email" v-model="form.email" required autocomplete="email" :error="!!form.errors.email" />
        <span v-if="form.errors.email" class="text-sm font-medium text-rose-600">{{ form.errors.email }}</span>
      </label>

      <div class="switch-field sm:col-span-2">
        <span class="switch-label">Status do usuário</span>
        <Switch v-model="form.status" true-value="active" false-value="inactive" />
        <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
          {{ form.status === 'active' ? 'Ativo' : 'Inativo' }}
        </span>
      </div>
      <span v-if="form.errors.status" class="text-sm font-medium text-rose-600 sm:col-span-2">{{ form.errors.status }}</span>
    </div>

    <fieldset class="space-y-3" v-if="!isEdit">
      <legend class="text-sm font-semibold text-slate-700">Credenciais de acesso</legend>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="form-label">
          Senha *
          <InputText type="password" v-model="form.password" required autocomplete="new-password" :error="!!form.errors.password" />
          <span v-if="form.errors.password" class="text-sm font-medium text-rose-600">{{ form.errors.password }}</span>
        </label>
        <label class="form-label">
          Confirmar senha *
          <InputText type="password" v-model="form.password_confirmation" required autocomplete="new-password" />
        </label>
      </div>
    </fieldset>

    <fieldset class="space-y-3" v-if="isEdit">
      <legend class="text-sm font-semibold text-slate-700">Alterar senha</legend>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="form-label">
          Nova senha
          <InputText type="password" v-model="form.password" autocomplete="new-password" :error="!!form.errors.password" />
          <span v-if="form.errors.password" class="text-sm font-medium text-rose-600">{{ form.errors.password }}</span>
        </label>
        <label class="form-label">
          Confirmar senha
          <InputText type="password" v-model="form.password_confirmation" autocomplete="new-password" />
        </label>
      </div>
      <p class="text-sm text-slate-500">Preencha apenas se desejar definir uma nova senha para o usuário.</p>
    </fieldset>

    <fieldset class="space-y-3">
      <legend class="text-sm font-semibold text-slate-700">Perfil de acesso</legend>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="form-label">
          Função *
          <InputSelect v-model="form.role" :options="[
            { value: 'user', label: 'Usuário comum' },
            { value: 'admin', label: 'Administrador' }
          ]" placeholder="" required :error="!!form.errors.role" />
        </label>

      </div>
      <span v-if="form.errors.role" class="text-sm font-medium text-rose-600">{{ form.errors.role }}</span>
      <span class="text-sm text-blue-500 mt-1 flex items-center gap-1">
          <HeroIcon name="information-circle" class="h-4 w-4" />
          Administradores podem gerenciar usuários. Demais perfis possuem acesso restrito às próprias operações.
        </span>
    </fieldset>

    <PermissionsMatrix :resources="props.resources"
                       :role="form.role"
                       v-model="form.permissions"
                       v-model:modules="form.modules" />

    <div class="flex flex-wrap gap-3">
      <Button variant="primary" :loading="form.processing" type="submit">{{ submitLabel }}</Button>
      <Button variant="ghost" :href="cancelHref">Cancelar</Button>
    </div>
  </form>
</template>
