<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import LeadForm from '@/components/leads/LeadForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const form = useForm({
  name: '',
  email: '',
  phone: '',
  company: '',
  source: 'manual',
  status: 'new',
  interactions: [],
});

const submit = () => {
  form.post(route('leads.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Novo Lead" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="chat-bubble-left-right" class="h-7 w-7 text-slate-700" />
            Cadastrar Lead
          </h1>
          <p class="mt-2 text-sm text-slate-500">Adicione um novo lead ao sistema.</p>
        </div>
      </div>

      <LeadForm :form="form" :submit-label="'Salvar lead'" :cancel-href="route('leads.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
