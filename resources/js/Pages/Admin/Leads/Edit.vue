<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import LeadForm from '@/components/leads/LeadForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({ lead: { type: Object, required: true } });

const form = useForm({
  name: props.lead.name || '',
  email: props.lead.email || '',
  phone: props.lead.phone || '',
  company: props.lead.company || '',
  source: props.lead.source || 'manual',
  status: props.lead.status || 'new',
  interactions: props.lead.interactions || [],
});

const submit = () => {
  form.patch(route('leads.update', props.lead.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Lead" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="chat-bubble-left-right" class="h-7 w-7 text-slate-700" />
            Editar Lead
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize as informações do lead.</p>
        </div>
      </div>

      <LeadForm :form="form" :submit-label="'Salvar alterações'" :cancel-href="route('leads.index')" :is-editing="true" :lead-id="lead.id" @submit="submit" />
    </section>
  </AdminLayout>
</template>
