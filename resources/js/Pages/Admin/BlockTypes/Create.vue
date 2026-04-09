<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import BlockTypeForm from '@/components/blockTypes/BlockTypeForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const form = useForm({
  name: '',
  raw_material_percentage: null,
  status: 'active',
});

const submit = () => {
  form.post(route('block-types.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Criar Tipo de Bloco" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Cadastrar Tipo de Bloco
          </h1>
          <p class="mt-2 text-sm text-slate-500">Preencha os dados para registrar um novo tipo de bloco.</p>
        </div>
      </div>

      <BlockTypeForm :form="form" :submit-label="'Salvar tipo de bloco'" :cancel-href="route('block-types.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>