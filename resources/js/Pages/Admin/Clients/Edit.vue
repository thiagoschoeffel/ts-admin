<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import ClientForm from '@/components/clients/ClientForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  states: { type: Array, required: true },
  client: { type: Object, required: true },
});

const form = useForm({
  name: props.client.name || '',
  person_type: props.client.person_type || 'individual',
  document: props.client.document || '',
  observations: props.client.observations || '',
  contact_name: props.client.contact_name || '',
  contact_phone_primary: props.client.contact_phone_primary || '',
  contact_phone_secondary: props.client.contact_phone_secondary || '',
  contact_email: props.client.contact_email || '',
  status: props.client.status || 'active',
  addresses: props.client.addresses || [],
});

const submit = () => {
  form.patch(`/admin/clients/${props.client.id}`);
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar cliente" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="identification" class="h-7 w-7 text-slate-700" />
            Editar cliente
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize as informações de {{ props.client.name }}.</p>
        </div>
      </div>

  <ClientForm :form="form" :states="props.states" :submit-label="'Salvar alterações'" :cancel-href="route('clients.index')" :is-editing="true" :client-id="props.client.id" @submit="submit" />
    </section>
  </AdminLayout>
</template>

