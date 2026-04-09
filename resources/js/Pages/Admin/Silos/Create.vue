<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { route } from '@/ziggy-client';
import { Head, useForm } from '@inertiajs/vue3';
import SiloForm from '@/components/silos/SiloForm.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const form = useForm({
  name: '',
  status: 'active',
});

const submit = () => {
  form.post(route('silos.store'));
};
</script>

<template>
  <AdminLayout>
    <Head title="Criar Silo" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" outline class="h-7 w-7 text-slate-700" />
            Cadastrar Silo
          </h1>
          <p class="mt-2 text-sm text-slate-500">Preencha os dados para registrar um novo silo.</p>
        </div>
      </div>

      <SiloForm :form="form" :submit-label="'Salvar silo'" :cancel-href="route('silos.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>
