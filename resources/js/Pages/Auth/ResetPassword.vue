<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ token: String, email: String });
const form = useForm({ token: props.token || '', email: props.email || '', password: '', password_confirmation: '' });
const submit = () => form.post('/reset-password');
</script>

<template>
  <PublicLayout>
    <section class="card max-w-md mx-auto">
      <h1 class="text-2xl font-semibold mb-4">Redefinir senha</h1>
      <form @submit.prevent="submit" class="space-y-4">
        <label class="form-label">
          E-mail
          <InputText type="email" v-model="form.email" required />
        </label>
        <label class="form-label">
          Nova senha
          <InputText type="password" v-model="form.password" required />
        </label>
        <label class="form-label">
          Confirmar senha
          <InputText type="password" v-model="form.password_confirmation" required />
        </label>
        <div class="flex gap-3">
          <Button variant="primary" :loading="form.processing" type="submit">Redefinir senha</Button>
        </div>
      </form>
    </section>
  </PublicLayout>
</template>
