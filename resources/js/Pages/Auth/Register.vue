<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/components/Button.vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputText from '@/components/InputText.vue';
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/register');
};
</script>

<template>
  <PublicLayout>
    <section class="card space-y-6 max-w-xl mx-auto">
      <h1 class="text-2xl font-semibold text-slate-900">Cadastrar</h1>

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
          E-mail
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
          Senha
          <InputText
            v-model="form.password"
            type="password"
            required
            autocomplete="new-password"
            :error="!!form.errors.password"
            placeholder="Digite sua senha"
          />
          <span v-if="form.errors.password" class="text-sm font-medium text-rose-600">{{ form.errors.password }}</span>
        </label>

        <label class="form-label">
          Confirmar senha
          <InputText
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            placeholder="Confirme sua senha"
          />
        </label>

        <div class="flex flex-wrap items-center gap-3">
          <Button variant="primary" :loading="form.processing" type="submit">
            <span v-if="!form.processing">Cadastrar</span>
            <span v-else>Enviando…</span>
          </Button>
          <Button variant="ghost" :href="route('login')">Já tenho conta</Button>
        </div>
      </form>
    </section>
  </PublicLayout>

</template>
