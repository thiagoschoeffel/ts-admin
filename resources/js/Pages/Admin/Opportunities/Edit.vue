<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import OpportunityForm from '@/components/opportunities/OpportunityForm.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  opportunity: { type: Object, required: true },
  leads: { type: Array, required: true },
  clients: { type: Array, required: true },
  products: { type: Array, required: true },
});

const form = useForm({
  lead_id: props.opportunity.lead_id || '',
  client_id: props.opportunity.client_id || '',
  title: props.opportunity.title || '',
  description: props.opportunity.description || '',
  stage: props.opportunity.stage || 'new',
  probability: props.opportunity.probability || 0,
  expected_value: props.opportunity.expected_value || '',
  expected_close_date: props.opportunity.expected_close_date || '',
  status: props.opportunity.status || 'active',
  items: props.opportunity.items ? props.opportunity.items.map(item => ({
    product_id: item.product_id || '',
    quantity: item.quantity || 1,
    unit_price: item.unit_price || '',
    subtotal: item.subtotal || '',
  })) : [],
});

const submit = () => {
  form.patch(route('opportunities.update', props.opportunity.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Oportunidade" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="document-currency-dollar" class="h-7 w-7 text-slate-700" />
            Editar oportunidade
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize as informações da oportunidade.</p>
        </div>
      </div>

      <OpportunityForm :form="form" :leads="props.leads" :clients="props.clients" :products="props.products" :submit-label="'Salvar alterações'" :cancel-href="route('opportunities.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
