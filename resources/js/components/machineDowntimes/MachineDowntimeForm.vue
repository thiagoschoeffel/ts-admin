<script setup>
import InputSelect from '@/components/InputSelect.vue';
import InputText from '@/components/InputText.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import InputTextarea from '@/components/InputTextarea.vue';
import Switch from '@/components/ui/Switch.vue';
import Button from '@/components/Button.vue';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  form: { type: Object, required: true },
  machines: { type: Array, default: () => [] },
  reasons: { type: Array, default: () => [] },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, default: '#' },
});

const emit = defineEmits(['submit']);

// Autocomplete state for Machine
const machineInput = ref('');
const machineSuggestions = computed(() => {
  const q = machineInput.value.trim().toLowerCase();
  if (!q) return [];
  return props.machines.filter(m => m.name.toLowerCase().includes(q)).slice(0, 10);
});
const syncMachineFromId = () => {
  const current = props.machines.find(m => m.id === props.form.machine_id);
  machineInput.value = current?.name || '';
};
const handleMachineInput = () => {
  const exact = props.machines.find(m => m.name.toLowerCase() === machineInput.value.trim().toLowerCase());
  props.form.machine_id = exact ? exact.id : null;
};

// Autocomplete state for Reason
const reasonInput = ref('');
const reasonSuggestions = computed(() => {
  const q = reasonInput.value.trim().toLowerCase();
  if (!q) return [];
  return props.reasons.filter(r => r.name.toLowerCase().includes(q)).slice(0, 10);
});
const syncReasonFromId = () => {
  const current = props.reasons.find(r => r.id === props.form.reason_id);
  reasonInput.value = current?.name || '';
};
const handleReasonInput = () => {
  const exact = props.reasons.find(r => r.name.toLowerCase() === reasonInput.value.trim().toLowerCase());
  props.form.reason_id = exact ? exact.id : null;
};

watch(() => props.form.machine_id, syncMachineFromId, { immediate: true });
watch(() => props.form.reason_id, syncReasonFromId, { immediate: true });

const submit = () => emit('submit');

onMounted(() => {
  const pad = (n) => String(n).padStart(2, '0')
  const now = new Date()
  const nowStr = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}`
  if (!props.form.started_at) props.form.started_at = nowStr
  if (!props.form.ended_at) props.form.ended_at = nowStr
});
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="form-label">
        Máquina *
        <InputText v-model="machineInput"
                   type="text"
                   list="machines-list"
                   placeholder="Digite para buscar a máquina"
                   required
                   :error="!!form.errors?.machine_id"
                   @input="handleMachineInput" @change="handleMachineInput" />
        <span v-if="form.errors?.machine_id" class="text-sm font-medium text-rose-600">{{ form.errors.machine_id }}</span>
        <datalist id="machines-list">
          <option v-for="m in machineSuggestions" :key="m.id" :value="m.name">{{ m.name }}</option>
        </datalist>
      </label>
      <label class="form-label">
        Motivo *
        <InputText v-model="reasonInput"
                   type="text"
                   list="reasons-list"
                   placeholder="Digite para buscar o motivo"
                   required
                   :error="!!form.errors?.reason_id"
                   @input="handleReasonInput" @change="handleReasonInput" />
        <span v-if="form.errors?.reason_id" class="text-sm font-medium text-rose-600">{{ form.errors.reason_id }}</span>
        <datalist id="reasons-list">
          <option v-for="r in reasonSuggestions" :key="r.id" :value="r.name">{{ r.name }}</option>
        </datalist>
      </label>
      <label class="form-label">
        Início *
        <InputDatePicker v-model="form.started_at" :withTime="true" :allowManualInput="true" required :error="!!form.errors?.started_at" />
        <span v-if="form.errors?.started_at" class="text-sm font-medium text-rose-600">{{ form.errors.started_at }}</span>
      </label>
      <label class="form-label">
        Fim *
        <InputDatePicker v-model="form.ended_at" :withTime="true" :allowManualInput="true" required :error="!!form.errors?.ended_at" />
        <span v-if="form.errors?.ended_at" class="text-sm font-medium text-rose-600">{{ form.errors.ended_at }}</span>
      </label>
      <label class="form-label sm:col-span-2">
        Observações
        <InputTextarea v-model="form.notes" placeholder="Observações (opcional)" :error="!!form.errors?.notes" />
        <span v-if="form.errors?.notes" class="text-sm font-medium text-rose-600">{{ form.errors.notes }}</span>
      </label>
      <div class="switch-field sm:col-span-2">
        <span class="switch-label">Status da parada</span>
        <Switch v-model="form.status" true-value="active" false-value="inactive" />
        <span class="switch-status" :class="{ 'inactive': form.status !== 'active' }">
          {{ form.status === 'active' ? 'Ativo' : 'Inativo' }}
        </span>
      </div>
      <span v-if="form.errors?.status" class="text-sm font-medium text-rose-600 sm:col-span-2">{{ form.errors.status }}</span>
    </div>

    <div class="flex gap-3">
      <Button type="submit" variant="primary">{{ submitLabel }}</Button>
      <Button as="a" :href="cancelHref" variant="ghost">Cancelar</Button>
    </div>
  </form>
</template>
