<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import MachineForm from '@/components/machines/MachineForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  machine: { type: Object, required: true },
  sectors: Array,
});

const form = useForm({
  sector_id: props.machine.sector_id,
  name: props.machine.name,
  status: props.machine.status,
});

const submit = () => {
  form.patch(route('machines.update', props.machine.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Máquina" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="cpu-chip" outline class="h-7 w-7 text-slate-700" />
            Editar máquina
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados da máquina.</p>
        </div>
      </div>

      <MachineForm :form="form" :submit-label="'Salvar alterações'" :cancel-href="route('machines.index')" :sectors="sectors" @submit="submit" />
    </section>
  </AdminLayout>
</template>
