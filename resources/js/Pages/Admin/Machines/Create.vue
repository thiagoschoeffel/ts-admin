<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import MachineForm from '@/components/machines/MachineForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

defineProps({
  sectors: Array,
});

const form = useForm({
  sector_id: '',
  name: '',
  status: 'active',
});

const submit = () => {
  form.post(route('machines.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Cadastrar Máquina" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="cpu-chip" outline class="h-7 w-7 text-slate-700" />
            Cadastrar máquina
          </h1>
          <p class="mt-2 text-sm text-slate-500">Preencha os dados para registrar uma nova máquina.</p>
        </div>
      </div>

      <MachineForm :form="form" :submit-label="'Salvar máquina'" :cancel-href="route('machines.index')" :sectors="sectors" @submit="submit" />
    </section>
  </AdminLayout>
</template>
