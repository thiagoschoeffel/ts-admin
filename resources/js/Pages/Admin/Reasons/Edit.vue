<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import ReasonForm from '@/components/reasons/ReasonForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  reason: { type: Object, required: true },
  reasonTypes: { type: Array, default: () => [] },
});

const form = useForm({
  reason_type_id: props.reason.reason_type_id,
  name: props.reason.name,
  status: props.reason.status,
});

const submit = () => {
  form.patch(route('reasons.update', props.reason.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Motivo" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="tag" outline class="h-7 w-7 text-slate-700" />
            Editar motivo
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados do motivo.</p>
        </div>
      </div>

      <ReasonForm :form="form" :reason-types="reasonTypes" :submit-label="'Salvar alterações'" :cancel-href="route('reasons.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
