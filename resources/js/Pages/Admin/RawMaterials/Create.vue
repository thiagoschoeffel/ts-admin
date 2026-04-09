<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import RawMaterialForm from '@/components/rawMaterials/RawMaterialForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const form = useForm({
  name: '',
  status: 'active',
});

const submit = () => {
  form.post(route('raw-materials.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Criar Matéria-Prima" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Cadastrar Matéria-Prima
          </h1>
          <p class="mt-2 text-sm text-slate-500">Preencha os dados para registrar uma nova matéria-prima.</p>
        </div>
      </div>

      <RawMaterialForm :form="form" :submit-label="'Salvar matéria-prima'" :cancel-href="route('raw-materials.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>