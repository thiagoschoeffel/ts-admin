<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/components/Button.vue';
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);
</script>

<template>
  <PublicLayout>
    <section class="card space-y-6">
      <h1 class="text-2xl font-semibold text-slate-900">Bem-vindo ao painel da aplicação</h1>
      <p class="max-w-prose text-base leading-relaxed text-slate-600">
        Esta é a área pública do sistema. Faça login para acessar o painel administrativo, onde você poderá ver o conteúdo restrito a usuários autenticados.
      </p>

      <div v-if="!isAuth" class="flex flex-wrap gap-3">
        <Button variant="primary" :href="route('login')">Entrar</Button>
        <Button variant="ghost" :href="route('register')">Criar uma conta</Button>
      </div>
      <p v-else class="font-semibold text-slate-700">
  Você já está autenticado. Acesse o seu <Link class="link" :href="route('dashboard')">dashboard</Link>.
      </p>
    </section>
  </PublicLayout>

</template>

<style scoped>
.link { color:#2563eb; text-decoration: underline; }
</style>
