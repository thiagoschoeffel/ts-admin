<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import InputDatePicker from '@/components/InputDatePicker.vue';
import InputText from '@/components/InputText.vue';
import InputNumber from '@/components/InputNumber.vue';
import InputSelect from '@/components/InputSelect.vue';
import Button from '@/components/Button.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import { computed } from 'vue';

const props = defineProps({
  moldTypes: { type: Array, default: () => [] },
});

function nowYMDHM() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const moldTypeOptions = computed(() => (props.moldTypes || []).map((t) => ({ value: t.id, label: t.name })));

const form = useForm({
  dispatched_at: nowYMDHM(),
  manufacturing_order_number: '',
  mold_type_id: null,
  quantity: 1,
});

function submit() {
  form.post(route('molded-dispatches.store'));
}
</script>

<template>
  <AdminLayout>
    <Head title="Nova Saída de Moldados" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" class="h-7 w-7 text-slate-700" />
            Nova saída de moldados
          </h1>
          <p class="mt-2 text-sm text-slate-500">Registre a saída (baixa) de moldados.</p>
        </div>
      </div>

      <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <label class="form-label">
            Data/Hora de saída *
            <InputDatePicker
              v-model="form.dispatched_at"
              :withTime="true"
              :allowManualInput="true"
              required
              :error="!!form.errors.dispatched_at"
            />
            <span v-if="form.errors.dispatched_at" class="text-sm font-medium text-rose-600">{{ form.errors.dispatched_at }}</span>
          </label>

          <label class="form-label">
            Número ordem de fabricação *
            <InputText
              v-model="form.manufacturing_order_number"
              placeholder="Ex.: OF-123"
              required
              :error="!!form.errors.manufacturing_order_number"
            />
            <span v-if="form.errors.manufacturing_order_number" class="text-sm font-medium text-rose-600">{{ form.errors.manufacturing_order_number }}</span>
          </label>

          <label class="form-label">
            Tipo de moldado *
            <InputSelect
              v-model="form.mold_type_id"
              :options="moldTypeOptions"
              placeholder="Selecione"
              required
              :error="!!form.errors.mold_type_id"
            />
            <span v-if="form.errors.mold_type_id" class="text-sm font-medium text-rose-600">{{ form.errors.mold_type_id }}</span>
          </label>

          <label class="form-label">
            Quantidade de saída *
            <InputNumber
              v-model="form.quantity"
              :formatted="true"
              :precision="0"
              :min="1"
              :step="1"
              placeholder="0"
              required
              :error="!!form.errors.quantity"
            />
            <span v-if="form.errors.quantity" class="text-sm font-medium text-rose-600">{{ form.errors.quantity }}</span>
          </label>
        </div>

        <div class="flex flex-wrap gap-3">
          <Button type="submit" variant="primary" :loading="form.processing">
            Registrar saída
          </Button>
          <Button type="button" variant="ghost" :href="route('molded-dispatches.index')">
            Cancelar
          </Button>
        </div>
      </form>
    </section>
  </AdminLayout>
</template>
