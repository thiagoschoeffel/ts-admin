<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import OpportunityForm from '@/components/opportunities/OpportunityForm.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  leads: { type: Array, required: true },
  clients: { type: Array, required: true },
  products: { type: Array, required: true },
});

const form = useForm({
  lead_id: '',
  client_id: '',
  title: '',
  description: '',
  stage: 'new',
  probability: 0,
  expected_value: '',
  expected_close_date: '',
  status: 'active',
  items: [],
});

const submit = () => {
  form.post(route('opportunities.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Nova Oportunidade" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="document-currency-dollar" class="h-7 w-7 text-slate-700" />
            Cadastrar oportunidade
          </h1>
          <p class="mt-2 text-sm text-slate-500">Adicione uma nova oportunidade de venda.</p>
        </div>
      </div>

      <OpportunityForm :form="form" :leads="props.leads" :clients="props.clients" :products="props.products" :submit-label="'Salvar oportunidade'" :cancel-href="route('opportunities.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
