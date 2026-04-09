<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import ProductionPointingForm from '@/components/productionPointings/ProductionPointingForm.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  rawMaterials: { type: Array, default: () => [] },
  operators: { type: Array, default: () => [] },
  silos: { type: Array, default: () => [] },
});

const formatDateTime = (date) => {
  const pad = (value) => String(value).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const form = useForm({
  status: 'active',
  sheet_number: null,
  raw_material_id: null,
  quantity: null,
  started_at: formatDateTime(new Date()),
  ended_at: formatDateTime(new Date()),
  operator_ids: [],
  silo_ids: [],
});

const submit = () => {
  form.post(route('production-pointings.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Criar Apontamento de Produção" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Cadastrar Apontamento de Produção
          </h1>
          <p class="mt-2 text-sm text-slate-500">Preencha os dados para registrar um novo apontamento.</p>
        </div>
      </div>

      <ProductionPointingForm :form="form"
                              :raw-materials="props.rawMaterials"
                              :operators="props.operators"
                              :silos="props.silos"
                              :submit-label="'Salvar apontamento'"
                              :cancel-href="route('production-pointings.index')"
                              @submit="submit" />
    </section>
  </AdminLayout>
</template>
