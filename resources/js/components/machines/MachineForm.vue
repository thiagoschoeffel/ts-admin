<script setup>
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import Button from '@/components/Button.vue';
import Switch from '@/components/ui/Switch.vue';
import { computed } from 'vue';

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
  sectors: { type: Array, default: () => [] },
});

const emit = defineEmits(['submit']);

const sectorOptions = computed(() => {
  return props.sectors.map(sector => ({ value: sector.id, label: sector.name }));
});

// Set default sector_id to the first sector if not already set
if (!props.form.sector_id && props.sectors.length > 0) {
  props.form.sector_id = props.sectors[0].id;
}
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
        Setor *
  <InputSelect v-model="form.sector_id" :options="sectorOptions" required :error="!!form.errors.sector_id" :placeholder="null" />
        <span v-if="form.errors.sector_id" class="text-sm font-medium text-rose-600">{{ form.errors.sector_id }}</span>
      </label>

      <div class="switch-field sm:col-span-2 lg:col-span-3">
        <span class="switch-label">Status da máquina</span>
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
