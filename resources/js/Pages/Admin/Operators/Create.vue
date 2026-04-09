<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import OperatorForm from '@/components/operators/OperatorForm.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  sectors: { type: Array, default: () => [] },
});

const form = useForm({
  name: '',
  sector_id: '',
  status: 'active',
});

function submit() {
  form.post(route('operators.store'));
}
</script>

<template>
  <AdminLayout>
    <Head title="Cadastrar Operador" />

    <section class="card space-y-8">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
          <HeroIcon name="user-group" outline class="h-7 w-7 text-slate-700" />
          Cadastrar operador
        </h1>
        <p class="mt-2 text-sm text-slate-500">Preencha os dados para registrar um novo operador.</p>
      </div>

      <OperatorForm :form="form" :submit-label="'Salvar operador'" :cancel-href="route('operators.index')" :sectors="sectors" @submit="submit" />
    </section>
  </AdminLayout>
</template>
