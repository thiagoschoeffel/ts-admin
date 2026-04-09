<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import ProductionPointingForm from '@/components/productionPointings/ProductionPointingForm.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  productionPointing: {
    type: Object,
    required: true,
  },
  rawMaterials: { type: Array, default: () => [] },
  operators: { type: Array, default: () => [] },
  silos: { type: Array, default: () => [] },
});

const toDateTimeInput = (value) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (v) => String(v).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const form = useForm({
  status: props.productionPointing.status ?? 'active',
  sheet_number: props.productionPointing.sheet_number ?? null,
  raw_material_id: props.productionPointing.raw_material_id ?? null,
  quantity: props.productionPointing.quantity ?? null,
  started_at: toDateTimeInput(props.productionPointing.started_at),
  ended_at: toDateTimeInput(props.productionPointing.ended_at),
  operator_ids: (props.productionPointing.operators || []).map((operator) => operator.id),
  silo_ids: (props.productionPointing.silos || []).map((silo) => silo.id),
});

const submit = () => {
  form.patch(route('production-pointings.update', props.productionPointing.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Apontamento de Produção" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Editar Apontamento de Produção
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados do apontamento selecionado.</p>
        </div>
      </div>

      <ProductionPointingForm :form="form"
                              :raw-materials="props.rawMaterials"
                              :operators="props.operators"
                              :silos="props.silos"
                              :submit-label="'Atualizar apontamento'"
                              :cancel-href="route('production-pointings.index')"
                              @submit="submit" />
    </section>
  </AdminLayout>
</template>
