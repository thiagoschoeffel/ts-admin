<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import SectorForm from '@/components/sectors/SectorForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const form = useForm({
  name: '',
  status: 'active',
});

const submit = () => {
  form.post(route('sectors.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Criar Setor" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="rectangle-group" outline class="h-7 w-7 text-slate-700" />
            Cadastrar Setor
          </h1>
          <p class="mt-2 text-sm text-slate-500">Preencha os dados para registrar um novo setor.</p>
        </div>
      </div>

      <SectorForm :form="form" :submit-label="'Salvar setor'" :cancel-href="route('sectors.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
