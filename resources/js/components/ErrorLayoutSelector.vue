<template>
  <component :is="layout">
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const page = usePage()

const layout = computed(() => {
  // Preferir a URL passada pelo servidor (Inertia props) quando disponível.
  const serverUrl = page.props?.url || null
  let path = ''
  if (serverUrl) {
    try {
      // serverUrl pode conter query string; extrair apenas o pathname
      const parsed = new URL(serverUrl, typeof window !== 'undefined' ? window.location.origin : 'http://localhost')
      path = parsed.pathname
    } catch (e) {
      path = serverUrl
    }
  } else if (typeof window !== 'undefined') {
    path = window.location.pathname
  }

  if (path && path.startsWith('/admin')) return AdminLayout
  return PublicLayout
})
</script>
