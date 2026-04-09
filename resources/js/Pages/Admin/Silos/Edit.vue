<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import SiloForm from '@/components/silos/SiloForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  silo: { type: Object, required: true },
});

const form = useForm({
  name: props.silo.name,
  status: props.silo.status,
});

const submit = () => {
  form.patch(route('silos.update', props.silo.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Silo" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Editar Silo
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados do silo.</p>
        </div>
      </div>

      <SiloForm :form="form" :submit-label="'Salvar alterações'" :cancel-href="route('silos.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
