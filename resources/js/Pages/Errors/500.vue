<template>
  <ErrorLayoutSelector>
    <section class="card max-w-3xl mx-auto">
      <h1 class="error-title">Erro interno ({{ props.status }})</h1>
      <p class="error-message">Ocorreu um erro interno. Tente novamente mais tarde.</p>
      <div class="actions">
        <Button variant="primary" :href="backHref">Voltar ao início</Button>
      </div>
    </section>
  </ErrorLayoutSelector>
</template>

<script setup>
import ErrorLayoutSelector from '@/components/ErrorLayoutSelector.vue'
import Button from '@/components/Button.vue'
import { route } from '@/ziggy-client'
import { computed } from 'vue'

const props = defineProps({ status: { type: Number, default: 500 }, url: { type: String, default: null } })

const backHref = computed(() => {
  const u = props.url || (typeof window !== 'undefined' ? window.location.pathname : '/')
  if (u.startsWith('/admin')) return route('dashboard')
  return route('home')
})
</script>

<style scoped>
.error-title { font-size:1.5rem; font-weight:700; color:#0f172a; }
.error-message { margin-top:.5rem; color:#475569; }
.actions { margin-top:1rem; display:flex; gap:.75rem; }
</style>
