<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import OperatorForm from '@/components/operators/OperatorForm.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  operator: { type: Object, required: true },
  sectors: { type: Array, default: () => [] },
});

const form = useForm({
  name: props.operator.name,
  sector_id: props.operator.sector_id,
  status: props.operator.status,
});

function submit() {
  form.patch(route('operators.update', props.operator.id));
}
</script>

<template>
  <AdminLayout>
    <Head title="Editar Operador" />

    <section class="card space-y-8">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
          <HeroIcon name="user-group" outline class="h-7 w-7 text-slate-700" />
          Editar operador
        </h1>
        <p class="mt-2 text-sm text-slate-500">Atualize os dados do operador.</p>
      </div>

      <OperatorForm :form="form" :submit-label="'Salvar operador'" :cancel-href="route('operators.index')" :sectors="sectors" @submit="submit" />
    </section>
  </AdminLayout>
</template>
