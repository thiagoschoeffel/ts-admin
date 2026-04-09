<script setup>
import { computed, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import Button from '@/components/Button.vue';
import ToastContainer from '@/components/toast/ToastContainer.vue';
import { useToasts } from '@/components/toast/useToasts';

const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);

// Flash -> toasts
const { success, error } = useToasts();
let lastFlash = '';
watch(() => page.props.flash, (f) => {
  const key = JSON.stringify(f||{});
  if (key === lastFlash) return;
  lastFlash = key;
  if (!f) return;
  if (f.status) success(f.status);
  if (f.success) success(f.success);
  if (f.error) error(f.error);
}, { deep: true, immediate: true });
</script>

<template>
  <div class="min-h-screen bg-white text-slate-900">
    <header class="sticky top-0 z-50 bg-slate-900 text-white">
      <nav class="container-default flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
          <Link class="text-lg font-semibold tracking-tight text-white transition hover:text-blue-200" :href="route('home')">
            {{ $page.props.app?.name ?? 'Example App' }}
          </Link>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:justify-end">
          <template v-if="!isAuth">
            <Button variant="ghost-inverse" :href="route('login')">Entrar</Button>
            <Button variant="inverse" :href="route('register')">Registrar</Button>
          </template>
          <template v-else>
            <Button variant="inverse" :href="route('dashboard')">Acessar painel</Button>
          </template>
        </div>
      </nav>
    </header>

    <main class="container-default py-10 pt-20">
      <slot />
    </main>

    <footer class="container-default py-8 text-center text-sm text-slate-500">
      &copy; {{ new Date().getFullYear() }} {{ $page.props.app?.name ?? 'Example App' }}. Todos os direitos reservados.
    </footer>
  </div>
  <ToastContainer />
</template>

<style scoped>
.container-default { max-width: 72rem; margin: 0 auto; padding-left: 1rem; padding-right: 1rem; }
</style>
