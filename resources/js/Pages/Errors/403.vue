<script setup>
import ErrorLayoutSelector from '@/components/ErrorLayoutSelector.vue'
import Button from '@/components/Button.vue'
import { route } from '@/ziggy-client'
import { computed } from 'vue'

const props = defineProps({ status: { type: Number, default: 403 }, url: { type: String, default: null } })

const backHref = computed(() => {
  const u = props.url || (typeof window !== 'undefined' ? window.location.pathname : '/')
  if (u.startsWith('/admin')) return route('dashboard')
  return route('home')
})
</script>

<template>
  <ErrorLayoutSelector>
    <section class="card max-w-3xl mx-auto">
      <h1 class="error-title">Acesso negado ({{ props.status }})</h1>
      <p class="error-message">Você não tem permissão para acessar este recurso.</p>
      <div class="actions">
        <Button variant="primary" :href="backHref">Ir para a página inicial</Button>
      </div>
    </section>
  </ErrorLayoutSelector>
</template>

<style scoped>
.error-title { font-size:1.5rem; font-weight:700; color:#0f172a; }
.error-message { margin-top:.5rem; color:#475569; }
.actions { margin-top:1rem; display:flex; gap:.75rem; }
</style>

