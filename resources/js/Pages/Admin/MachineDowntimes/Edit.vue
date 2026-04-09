<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, getCurrentInstance } from 'vue';
import MachineDowntimeForm from '@/components/machineDowntimes/MachineDowntimeForm.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
  downtime: { type: Object, required: true },
  machines: { type: Array, default: () => [] },
  reasons: { type: Array, default: () => [] },
});

const instance = getCurrentInstance();
const route = instance.appContext.config.globalProperties.route;

const form = reactive({
  machine_id: props.downtime.machine_id,
  reason_id: props.downtime.reason_id,
  started_at: props.downtime.started_at ? new Date(props.downtime.started_at).toISOString().slice(0, 16).replace('T', ' ') : '',
  ended_at: props.downtime.ended_at ? new Date(props.downtime.ended_at).toISOString().slice(0, 16).replace('T', ' ') : '',
  notes: props.downtime.notes || '',
  status: props.downtime.status || 'active',
  errors: {},
});

async function submit() {
  form.errors = {};
  await router.patch(route('machine_downtimes.update', props.downtime.id), form, {
    onError: (e) => form.errors = e,
  });
}
</script>

<template>
  <AdminLayout>
    <Head title="Editar Parada de Máquina" />

    <section class="card space-y-8">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="pause-circle" class="h-7 w-7 text-slate-700" />
            Editar Parada de Máquina
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados da parada.</p>
        </div>
      </div>

      <MachineDowntimeForm :form="form" :machines="props.machines" :reasons="props.reasons" :submit-label="'Salvar alterações'" :cancel-href="route('machine_downtimes.index')" @submit="submit" />
    </section>
  </AdminLayout>
</template>

