<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import ReasonTypeForm from '@/components/reasonTypes/ReasonTypeForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  reasonType: { type: Object, required: true },
});

const form = useForm({
  name: props.reasonType.name,
  status: props.reasonType.status,
});

const submit = () => {
  form.patch(route('reason-types.update', props.reasonType.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Tipo de Motivo" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="swatch" outline class="h-7 w-7 text-slate-700" />
            Editar tipo de motivo
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados do tipo de motivo.</p>
        </div>
      </div>

      <ReasonTypeForm :form="form" :submit-label="'Salvar alterações'" :cancel-href="route('reason-types.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
