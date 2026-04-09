<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import AlmoxarifadoForm from '@/components/almoxarifados/AlmoxarifadoForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  almoxarifado: { type: Object, required: true },
});

const form = useForm({
  name: props.almoxarifado.name,
  status: props.almoxarifado.status,
});

const submit = () => {
  form.patch(route('almoxarifados.update', props.almoxarifado.id));
};
</script>

<template>
  <AdminLayout>
    <Head title="Editar Almoxarifado" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="view-columns" outline class="h-7 w-7 text-slate-700" />
            Editar Almoxarifado
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados do almoxarifado.</p>
        </div>
      </div>

      <AlmoxarifadoForm :form="form" :submit-label="'Salvar alterações'" :cancel-href="route('almoxarifados.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
