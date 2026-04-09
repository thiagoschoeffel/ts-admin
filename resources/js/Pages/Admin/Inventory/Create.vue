<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import FormMovement from '@/components/inventory/FormMovement.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';

const props = defineProps({
    rawMaterials: { type: Array, default: () => [] },
    silos: { type: Array, default: () => [] },
    blockTypes: { type: Array, default: () => [] },
    almoxarifados: { type: Array, default: () => [] },
    moldTypes: { type: Array, default: () => [] },
});

const form = useForm({
    item_type: 'raw_material',
    raw_material_id: null,
    mold_type_id: null,
    block_type_id: null,
    length_mm: null,
    width_mm: null,
    height_mm: null,
    consumed_raw_material_id: null,
    consumed_quantity_kg: null,
    occurred_at: null,
    direction: 'in',
    quantity: null,
    location_type: 'none',
    location_id: null,
    notes: '',
});

const submit = () => {
    form.post(route('inventory.movements.store'));
};
</script>

<template>
    <AdminLayout>

        <Head title="Novo Movimento de Estoque" />

        <section class="card space-y-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
                        <HeroIcon name="command-line" class="h-7 w-7 text-slate-700" />
                        Novo Movimento de Estoque
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">Lançamento manual de matéria-prima (entrada/saída/ajuste).
                    </p>
                </div>
            </div>

            <FormMovement :form="form" :raw-materials="props.rawMaterials" :silos="props.silos"
                :block-types="props.blockTypes" :almoxarifados="props.almoxarifados" :mold-types="props.moldTypes"
                submit-label="Salvar movimento" :cancel-href="route('inventory.movements.index')" @submit="submit" />
        </section>
    </AdminLayout>

</template>
