<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import MoldTypeForm from '@/components/moldTypes/MoldTypeForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  moldType: { type: Object, required: true },
});

const form = useForm({
  name: props.moldType.name,
  pieces_per_package: props.moldType.pieces_per_package,
  status: props.moldType.status,
});

const submit = () => {
  form.patch(route('mold-types.update', props.moldType.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Tipo de Moldado" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Editar Tipo de Moldado
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados do tipo de moldado.</p>
        </div>
      </div>

      <MoldTypeForm :form="form" :submit-label="'Salvar alterações'" :cancel-href="route('mold-types.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>