<script setup>
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputNumber from '@/components/InputNumber.vue';
import Button from '@/components/Button.vue';
import Switch from '@/components/ui/Switch.vue';

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
});

const emit = defineEmits(['submit']);
</script>

<template>
  <form @submit.prevent="$emit('submit')" class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <label class="form-label">
        Nome *
        <InputText v-model="form.name" required :error="!!form.errors.name" />
        <span v-if="form.errors.name" class="text-sm font-medium text-rose-600">{{ form.errors.name }}</span>
      </label>

      <label class="form-label">
        Matéria Prima Virgem (%) *
        <InputNumber v-model="form.raw_material_percentage" :formatted="true" :precision="2" :min="0" :max="100" :step="0.01" placeholder="0.00" :error="!!form.errors.raw_material_percentage" />
        <span v-if="form.errors.raw_material_percentage" class="text-sm font-medium text-rose-600">{{ form.errors.raw_material_percentage }}</span>
      </label>

      <div class="switch-field sm:col-span-2 lg:col-span-3">
        <span class="switch-label">Status do tipo de bloco</span>
        <Switch v-model="form.status" true-value="active" false-value="inactive" />
        <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
          {{ form.status === 'active' ? 'Ativo' : 'Inativo' }}
        </span>
      </div>
      <span v-if="form.errors.status" class="text-sm font-medium text-rose-600 sm:col-span-2 lg:col-span-3">{{ form.errors.status }}</span>
    </div>

    <div class="flex flex-wrap gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">{{ submitLabel }}</Button>
      <Button :href="cancelHref" variant="ghost">Cancelar</Button>
    </div>
  </form>
</template>

<style scoped>
.form-label { display:flex; flex-direction:column; gap:.5rem; font-weight:600; color:#334155 }
</style>