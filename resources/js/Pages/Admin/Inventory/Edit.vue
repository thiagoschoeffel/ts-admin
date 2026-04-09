<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import FormMovement from '@/components/inventory/FormMovement.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
    movement: { type: Object, required: true },
    rawMaterials: { type: Array, default: () => [] },
    silos: { type: Array, default: () => [] },
    blockTypes: { type: Array, default: () => [] },
    almoxarifados: { type: Array, default: () => [] },
    moldTypes: { type: Array, default: () => [] },
    relatedConsumption: { type: Object, default: null },
});

const itemType = props.movement.item_type || (props.movement.block_type_id ? 'block' : (props.movement.mold_type_id ? 'molded' : 'raw_material'));

const form = useForm({
    item_type: itemType,
    raw_material_id: itemType === 'raw_material' ? props.movement.item_id : null,
    mold_type_id: itemType === 'molded' ? props.movement.item_id : null,
    block_type_id: itemType === 'block' ? props.movement.block_type_id : null,
    length_mm: props.movement.length_mm,
    width_mm: props.movement.width_mm,
    height_mm: props.movement.height_mm,
    consumed_raw_material_id: props.relatedConsumption ? props.relatedConsumption.item_id : null,
    consumed_quantity_kg: props.relatedConsumption ? props.relatedConsumption.quantity : null,
    occurred_at: props.movement.occurred_at,
    direction: props.movement.direction,
    quantity: props.movement.quantity,
    location_type: props.movement.location_type,
    location_id: props.movement.location_id,
    notes: props.movement.notes,
});

const submit = () => {
    form.patch(route('inventory.movements.update', props.movement.id));
};
</script>

<template>
    <AdminLayout>

        <Head title="Editar Movimento de Estoque" />

        <section class="card space-y-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
                        <HeroIcon name="command-line" class="h-7 w-7 text-slate-700" />
                        Editar Movimento #{{ movement.id }}
                    </h1>
                </div>
            </div>

            <FormMovement :form="form" :raw-materials="props.rawMaterials" :silos="props.silos"
                :block-types="props.blockTypes" :almoxarifados="props.almoxarifados" :mold-types="props.moldTypes"
                submit-label="Salvar alterações" :cancel-href="route('inventory.movements.index')" @submit="submit" />
        </section>
    </AdminLayout>

</template>
