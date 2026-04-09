<script setup>
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import InputNumber from '@/components/InputNumber.vue';
import InputTextarea from '@/components/InputTextarea.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
    form: { type: Object, required: true },
    rawMaterials: { type: Array, default: () => [] },
    silos: { type: Array, default: () => [] },
    blockTypes: { type: Array, default: () => [] },
    almoxarifados: { type: Array, default: () => [] },
    moldTypes: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Salvar' },
    cancelHref: { type: String, required: true },
});

const itemTypes = [
    { value: 'raw_material', label: 'Matéria-prima' },
    { value: 'block', label: 'Bloco' },
    { value: 'molded', label: 'Moldado' },
];

const directions = [
    { value: 'in', label: 'Entrada' },
    { value: 'out', label: 'Saída' },
    { value: 'adjust', label: 'Ajuste' },
];
const locTypes = [
    { value: 'none', label: 'Nenhum' },
    { value: 'silo', label: 'Silo' },
    { value: 'almoxarifado', label: 'Almoxarifado' },
];
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="form-label">
                Tipo de Item
                <InputSelect v-model="form.item_type" :options="itemTypes" :placeholder="null" required />
                <span v-if="form.errors?.item_type" class="text-sm font-medium text-rose-600">{{ form.errors.item_type
                    }}</span>
            </label>
            <label class="form-label" v-if="form.item_type === 'raw_material'">
                Matéria-prima
                <InputSelect v-model="form.raw_material_id"
                    :options="rawMaterials.map(r => ({ value: r.id, label: r.name }))" required />
                <span v-if="form.errors?.raw_material_id" class="text-sm font-medium text-rose-600">{{
                    form.errors.raw_material_id }}</span>
            </label>
            <label class="form-label" v-else-if="form.item_type === 'molded'">
                Tipo de Moldado
                <InputSelect v-model="form.mold_type_id" :options="moldTypes.map(m => ({ value: m.id, label: m.name }))"
                    required />
                <span v-if="form.errors?.mold_type_id" class="text-sm font-medium text-rose-600">{{
                    form.errors.mold_type_id }}</span>
            </label>
            <template v-if="form.item_type === 'molded'">
                <label class="form-label">
                    Matéria-prima consumida
                    <InputSelect v-model="form.consumed_raw_material_id"
                        :options="rawMaterials.map(r => ({ value: r.id, label: r.name }))" />
                    <span v-if="form.errors?.consumed_raw_material_id" class="text-sm font-medium text-rose-600">{{
                        form.errors.consumed_raw_material_id }}</span>
                </label>
                <label class="form-label" v-if="form.consumed_raw_material_id">
                    Quantidade consumida (kg)
                    <InputNumber v-model="form.consumed_quantity_kg" :formatted="true" :precision="2" :min="0"
                        :step="0.001" />
                    <span v-if="form.errors?.consumed_quantity_kg" class="text-sm font-medium text-rose-600">{{
                        form.errors.consumed_quantity_kg }}</span>
                </label>
            </template>
            <label class="form-label" v-else-if="form.item_type === 'block'">
                Tipo de Bloco
                <InputSelect v-model="form.block_type_id"
                    :options="blockTypes.map(b => ({ value: b.id, label: b.name }))" required />
                <span v-if="form.errors?.block_type_id" class="text-sm font-medium text-rose-600">{{
                    form.errors.block_type_id }}</span>
            </label>
            <template v-if="form.item_type === 'block'">
                <label class="form-label">
                    Comprimento (mm)
                    <InputNumber v-model="form.length_mm" :formatted="true" :precision="0" :min="0" :step="1"
                        required />
                    <span v-if="form.errors?.length_mm" class="text-sm font-medium text-rose-600">{{
                        form.errors.length_mm }}</span>
                </label>
                <label class="form-label">
                    Largura (mm)
                    <InputNumber v-model="form.width_mm" :formatted="true" :precision="0" :min="0" :step="1" required />
                    <span v-if="form.errors?.width_mm" class="text-sm font-medium text-rose-600">{{ form.errors.width_mm
                        }}</span>
                </label>
                <label class="form-label">
                    Altura (mm)
                    <InputNumber v-model="form.height_mm" :formatted="true" :precision="0" :min="0" :step="1"
                        required />
                    <span v-if="form.errors?.height_mm" class="text-sm font-medium text-rose-600">{{
                        form.errors.height_mm }}</span>
                </label>
                <label class="form-label">
                    Matéria-prima consumida
                    <InputSelect v-model="form.consumed_raw_material_id"
                        :options="rawMaterials.map(r => ({ value: r.id, label: r.name }))" />
                    <span v-if="form.errors?.consumed_raw_material_id" class="text-sm font-medium text-rose-600">{{
                        form.errors.consumed_raw_material_id }}</span>
                </label>
                <label class="form-label" v-if="form.consumed_raw_material_id">
                    Quantidade consumida (kg)
                    <InputNumber v-model="form.consumed_quantity_kg" :formatted="true" :precision="2" :min="0"
                        :step="0.001" />
                    <span v-if="form.errors?.consumed_quantity_kg" class="text-sm font-medium text-rose-600">{{
                        form.errors.consumed_quantity_kg }}</span>
                </label>
            </template>
            <label class="form-label">
                Direção
                <InputSelect v-model="form.direction" :options="directions" :placeholder="null" required />
                <span v-if="form.errors?.direction" class="text-sm font-medium text-rose-600">{{ form.errors.direction
                    }}</span>
            </label>
            <label class="form-label">
                Quantidade {{ form.item_type === 'raw_material' ? '(kg)' : '(unidades)' }}
                <InputNumber v-model="form.quantity" :formatted="true"
                    :precision="form.item_type === 'raw_material' ? 2 : 0" :min="form.direction === 'adjust' ? null : 0"
                    :allow-negative="form.direction === 'adjust'" :step="form.item_type === 'raw_material' ? 0.001 : 1"
                    required />
                <span v-if="form.errors?.quantity" class="text-sm font-medium text-rose-600">{{
                    form.errors.quantity }}</span>
            </label>

            <label class="form-label">
                Local
                <InputSelect v-model="form.location_type" :options="locTypes" :placeholder="null" required />
                <span v-if="form.errors?.location_type" class="text-sm font-medium text-rose-600">{{
                    form.errors.location_type
                    }}</span>
            </label>
            <label class="form-label" v-if="form.location_type === 'silo'">
                Silo
                <InputSelect v-model="form.location_id" :options="silos.map(s => ({ value: s.id, label: s.name }))"
                    required />
                <span v-if="form.errors?.location_id" class="text-sm font-medium text-rose-600">{{
                    form.errors.location_id }}</span>
            </label>
            <label class="form-label" v-else-if="form.location_type === 'almoxarifado'">
                Almoxarifado
                <InputSelect v-model="form.location_id"
                    :options="almoxarifados.map(a => ({ value: a.id, label: a.name }))" required />
                <span v-if="form.errors?.location_id" class="text-sm font-medium text-rose-600">{{
                    form.errors.location_id }}</span>
            </label>
            <label class="form-label">
                Data/Hora
                <InputDatePicker v-model="form.occurred_at" :withTime="true" />
                <span v-if="form.errors?.occurred_at" class="text-sm font-medium text-rose-600">{{
                    form.errors.occurred_at }}</span>
            </label>

            <label class="form-label sm:col-span-2 lg:col-span-3">
                Observações
                <InputTextarea v-model="form.notes" />
                <span v-if="form.errors?.notes" class="text-sm font-medium text-rose-600">{{ form.errors.notes }}</span>
            </label>
        </div>

        <div class="flex flex-wrap gap-3">
            <Button type="submit" variant="primary" :loading="form.processing">{{ submitLabel }}</Button>
            <Button :href="cancelHref" variant="ghost">Cancelar</Button>
        </div>
    </form>
</template>
