<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/components/Button.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const sending = ref(false);
const resend = async () => {
  if (sending.value) return;
  sending.value = true;
  try {
    await router.post('/email/verification-notification');
  } finally {
    sending.value = false;
  }
};
</script>

<template>
  <PublicLayout>
    <section class="card space-y-6 max-w-xl mx-auto">
      <h1 class="text-2xl font-semibold mb-4">Confirme seu e-mail</h1>
      <p class="mb-4">Verificamos seu cadastro. Por favor, verifique seu e-mail e clique no link de confirmação enviado para você.</p>
      <div class="flex gap-3">
        <Button variant="primary" :loading="sending" @click="resend">Reenviar link de verificação</Button>
        <Button variant="ghost" :href="route('login')">Voltar ao login</Button>
      </div>
    </section>
  </PublicLayout>
</template>
