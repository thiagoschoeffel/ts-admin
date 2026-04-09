<script setup>
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import Button from '@/components/Button.vue';
import Switch from '@/components/ui/Switch.vue';

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
  reasonTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(['submit']);
</script>

<template>
  <form v-if="form" @submit.prevent="$emit('submit')" class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <label class="form-label">
        Nome *
        <InputText v-model="form.name" required :error="!!form.errors?.name" />
        <span v-if="form.errors?.name" class="text-sm font-medium text-rose-600">{{ form.errors.name }}</span>
      </label>

      <label class="form-label">
        Tipo de Motivo *
        <InputSelect
          v-model="form.reason_type_id"
          :options="reasonTypes.map(rt => ({ value: rt.id, label: rt.name }))"
          required
          :error="!!form.errors?.reason_type_id"
          placeholder="Selecione um tipo de motivo"
        />
        <span v-if="form.errors?.reason_type_id" class="text-sm font-medium text-rose-600">{{ form.errors.reason_type_id }}</span>
      </label>

      <div class="switch-field sm:col-span-2 lg:col-span-3">
        <span class="switch-label">Status do motivo</span>
        <Switch v-model="form.status" true-value="active" false-value="inactive" />
        <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
          {{ form.status === 'active' ? 'Ativo' : 'Inativo' }}
        </span>
      </div>
      <span v-if="form.errors?.status" class="text-sm font-medium text-rose-600 sm:col-span-2 lg:col-span-3">{{ form.errors.status }}</span>
    </div>

    <div class="flex flex-wrap gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">{{ submitLabel }}</Button>
      <Button :href="cancelHref" variant="ghost">Cancelar</Button>
    </div>
  </form>
  <div v-else class="text-center py-8">
    <p class="text-slate-500">Carregando formulário...</p>
  </div>
</template>

<style scoped>
.form-label { display:flex; flex-direction:column; gap:.5rem; font-weight:600; color:#334155 }
</style>
