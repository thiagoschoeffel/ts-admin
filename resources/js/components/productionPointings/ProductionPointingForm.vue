<script setup>
import InputText from '@/components/InputText.vue';
import InputNumber from '@/components/InputNumber.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import Button from '@/components/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Switch from '@/components/ui/Switch.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
  rawMaterials: { type: Array, default: () => [] },
  operators: { type: Array, default: () => [] },
  silos: { type: Array, default: () => [] },
});

const emit = defineEmits(['submit']);
const form = props.form;

const rawMaterialInput = ref('');
const rawMaterialSuggestions = computed(() => {
  const q = rawMaterialInput.value.trim().toLowerCase();
  if (!q) return props.rawMaterials.slice(0, 10);
  return props.rawMaterials.filter((item) => item.name.toLowerCase().includes(q)).slice(0, 10);
});

const syncRawMaterialFromId = () => {
  const current = props.rawMaterials.find((rm) => rm.id === form.raw_material_id);
  rawMaterialInput.value = current?.name || '';
};

const handleRawMaterialInput = () => {
  const exact = props.rawMaterials.find((rm) => rm.name.toLowerCase() === rawMaterialInput.value.trim().toLowerCase());
  form.raw_material_id = exact ? exact.id : null;
};

watch(() => form.raw_material_id, syncRawMaterialFromId, { immediate: true });

if (!Array.isArray(form.operator_ids)) {
  form.operator_ids = [];
}
if (!Array.isArray(form.silo_ids)) {
  form.silo_ids = [];
}

const submit = () => {
  if (form.sheet_number === '') form.sheet_number = null;
  if (form.ended_at === '') form.ended_at = null;
  emit('submit');
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <label class="form-label">
        Número da ficha *
        <InputNumber v-model="form.sheet_number" :formatted="true" :precision="0" :min="1" :step="1" placeholder="0" required :error="!!form.errors.sheet_number" />
        <span v-if="form.errors.sheet_number" class="text-sm font-medium text-rose-600">{{ form.errors.sheet_number }}</span>
      </label>

      <label class="form-label">
        Matéria-prima *
        <InputText v-model="rawMaterialInput"
                   type="text"
                   list="raw-materials-list"
                   placeholder="Digite para buscar"
                   required
                   :error="!!form.errors.raw_material_id"
                   @input="handleRawMaterialInput"
                   @change="handleRawMaterialInput" />
        <span v-if="form.errors.raw_material_id" class="text-sm font-medium text-rose-600">{{ form.errors.raw_material_id }}</span>
        <datalist id="raw-materials-list">
          <option v-for="item in rawMaterialSuggestions" :key="item.id" :value="item.name">{{ item.name }}</option>
        </datalist>
      </label>

      <label class="form-label">
        Quantidade (kg) *
        <InputNumber v-model="form.quantity" :formatted="true" :precision="2" :min="0.01" :step="0.01" placeholder="0,00" required :error="!!form.errors.quantity" />
        <span v-if="form.errors.quantity" class="text-sm font-medium text-rose-600">{{ form.errors.quantity }}</span>
      </label>

      <label class="form-label">
        Início *
        <InputDatePicker v-model="form.started_at" :withTime="true" required :error="!!form.errors.started_at" />
        <span v-if="form.errors.started_at" class="text-sm font-medium text-rose-600">{{ form.errors.started_at }}</span>
      </label>

      <label class="form-label">
        Fim *
        <InputDatePicker v-model="form.ended_at" :withTime="true" required :error="!!form.errors.ended_at" />
        <span v-if="form.errors.ended_at" class="text-sm font-medium text-rose-600">{{ form.errors.ended_at }}</span>
      </label>
    </div>

    <div class="space-y-3">
      <h3 class="text-sm font-semibold text-slate-600">Operadores *</h3>
      <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <Checkbox v-for="operator in operators" :key="operator.id" v-model="form.operator_ids" :value="operator.id" class="w-full">
          {{ operator.name }}
        </Checkbox>
        <p v-if="operators.length === 0" class="text-sm text-slate-500 sm:col-span-2 lg:col-span-3">Nenhum operador cadastrado.</p>
      </div>
      <span v-if="form.errors.operator_ids" class="text-sm font-medium text-rose-600">{{ form.errors.operator_ids }}</span>
    </div>

    <div class="space-y-3">
      <h3 class="text-sm font-semibold text-slate-600">Silos *</h3>
      <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <Checkbox v-for="silo in silos" :key="silo.id" v-model="form.silo_ids" :value="silo.id" class="w-full">
          {{ silo.name }}
        </Checkbox>
        <p v-if="silos.length === 0" class="text-sm text-slate-500 sm:col-span-2 lg:col-span-3">Nenhum silo cadastrado.</p>
      </div>
      <span v-if="form.errors.silo_ids" class="text-sm font-medium text-rose-600">{{ form.errors.silo_ids }}</span>
    </div>

    <div class="switch-field">
      <span class="switch-label">Status do apontamento</span>
      <Switch v-model="form.status" true-value="active" false-value="inactive" />
      <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
        {{ form.status === 'active' ? 'Ativo' : 'Inativo' }}
      </span>
    </div>
    <span v-if="form.errors.status" class="text-sm font-medium text-rose-600">{{ form.errors.status }}</span>

    <div class="flex flex-wrap gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">{{ submitLabel }}</Button>
      <Button :href="cancelHref" variant="ghost">Cancelar</Button>
    </div>
  </form>
</template>

<style scoped>
.form-label { display:flex; flex-direction:column; gap:.5rem; font-weight:600; color:#334155 }
</style>
