<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
  <PublicLayout>
    <section class="card max-w-md mx-auto">
      <h1 class="text-2xl font-semibold mb-4">Esqueci minha senha</h1>
      <p class="mb-4">Digite seu e-mail e enviaremos um link para redefinir sua senha.</p>
      <form @submit.prevent="submit" class="space-y-4">
        <label class="form-label">
          E-mail
          <InputText
            type="email"
            v-model="form.email"
            placeholder="Digite seu e-mail"
            :error="!!form.errors.email"
            required
          />
          <span v-if="form.errors.email" class="text-sm font-medium text-rose-600">{{ form.errors.email }}</span>
        </label>
        <div class="flex gap-3 items-center">
          <Button variant="primary" :loading="form.processing" type="submit">Enviar link</Button>
          <Button variant="ghost" :href="route('login')">Voltar ao login</Button>
        </div>
      </form>
    </section>
  </PublicLayout>
</template>
