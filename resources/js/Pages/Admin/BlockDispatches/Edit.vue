<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/ziggy-client';
import InputDatePicker from '@/components/InputDatePicker.vue';
import InputText from '@/components/InputText.vue';
import InputNumber from '@/components/InputNumber.vue';
import Button from '@/components/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import { computed, ref, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  blockDispatch: { type: Object, required: true },
});

const form = useForm({
  dispatched_at: props.blockDispatch.dispatched_at,
  manufacturing_order_number: props.blockDispatch.manufacturing_order_number,
  production_pointing_id: props.blockDispatch.production_pointing_id,
  block_production_ids: Array.isArray(props.blockDispatch.block_production_ids) ? [...props.blockDispatch.block_production_ids] : [],
});

const loadingBlocks = ref(false);
const productionPointing = ref(null);
const blocks = ref([]);
const fetchError = ref(null);

const availableBlocks = computed(() => blocks.value.filter((b) => b.can_dispatch));
const selectedCount = computed(() => form.block_production_ids.length);

function resetBlocksState() {
  blocks.value = [];
  productionPointing.value = null;
  form.block_production_ids = [];
  fetchError.value = null;
}

async function loadBlocks(ppId, { keepSelection = false } = {}) {
  if (!ppId) {
    resetBlocksState();
    return;
  }

  const currentSelection = keepSelection ? [...form.block_production_ids] : [];

  loadingBlocks.value = true;
  fetchError.value = null;
  try {
    const { data } = await axios.get(route('block-dispatches.available-blocks'), {
      params: { production_pointing_id: ppId, block_dispatch_id: props.blockDispatch.id },
    });
    productionPointing.value = data?.productionPointing || null;
    blocks.value = Array.isArray(data?.data) ? data.data : [];
    form.block_production_ids = currentSelection;
  } catch (e) {
    resetBlocksState();
    fetchError.value = 'Não foi possível carregar os blocos desta requisição.';
  } finally {
    loadingBlocks.value = false;
  }
}

let debounceTimer = null;
watch(
  () => form.production_pointing_id,
  (val, oldVal) => {
    clearTimeout(debounceTimer);
    const keepSelection = String(val) === String(oldVal);
    debounceTimer = setTimeout(() => loadBlocks(val, { keepSelection }), 350);
  }
);

function selectAllAvailable() {
  form.block_production_ids = availableBlocks.value.map((b) => b.id);
}

function clearSelection() {
  form.block_production_ids = [];
}

function submit() {
  form.patch(route('block-dispatches.update', props.blockDispatch.id));
}

onMounted(() => {
  loadBlocks(form.production_pointing_id, { keepSelection: true });
});

const nf2 = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
  <AdminLayout>
    <Head :title="`Editar Saída de Blocos #${blockDispatch.id}`" />

    <section class="card space-y-8">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <HeroIcon name="command-line" class="h-7 w-7 text-slate-700" />
            Editar saída de blocos #{{ blockDispatch.id }}
          </h1>
          <p class="mt-2 text-sm text-slate-500">Atualize os dados e a seleção de blocos desta saída.</p>
        </div>
      </div>

      <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
            Código da requisição *
            <InputNumber
              v-model="form.production_pointing_id"
              :formatted="true"
              :precision="0"
              :min="1"
              :step="1"
              placeholder="0"
              required
              :error="!!form.errors.production_pointing_id"
            />
            <span v-if="form.errors.production_pointing_id" class="text-sm font-medium text-rose-600">{{ form.errors.production_pointing_id }}</span>
          </label>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-slate-700">
              <span class="font-semibold">Requisição:</span>
              <span v-if="productionPointing?.id">#{{ productionPointing.id }}</span>
              <span v-else>—</span>
              <span class="ml-3 font-semibold">Ficha:</span>
              <span>{{ productionPointing?.sheet_number ?? '—' }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <Button type="button" variant="outline" size="sm" :disabled="loadingBlocks || !availableBlocks.length" @click="selectAllAvailable">
                Selecionar disponíveis ({{ availableBlocks.length }})
              </Button>
              <Button type="button" variant="ghost" size="sm" :disabled="!selectedCount" @click="clearSelection">
                Limpar seleção
              </Button>
            </div>
          </div>

          <div v-if="fetchError" class="text-sm font-medium text-rose-600">{{ fetchError }}</div>
          <div v-if="loadingBlocks" class="text-sm text-slate-500">Carregando blocos…</div>

          <div v-else-if="form.production_pointing_id && blocks.length === 0" class="text-sm text-slate-500">
            Nenhum bloco encontrado para esta requisição.
          </div>

          <div v-else-if="blocks.length" class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="text-left text-slate-600">
                <tr>
                  <th class="py-2 pr-4">Selecionar</th>
                  <th class="py-2 pr-4">Bloco</th>
                  <th class="py-2 pr-4">Tipo</th>
                  <th class="py-2 pr-4">Dimensão (mm)</th>
                  <th class="py-2 pr-4">Peso (kg)</th>
                  <th class="py-2 pr-4">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr v-for="b in blocks" :key="b.id" class="align-top">
                  <td class="py-2 pr-4">
                    <Checkbox v-model="form.block_production_ids" :value="b.id" :disabled="!b.can_dispatch" />
                  </td>
                  <td class="py-2 pr-4 text-slate-900">
                    <div class="font-semibold">#{{ b.id }}</div>
                    <div class="text-slate-500">Ficha {{ b.sheet_number }}</div>
                  </td>
                  <td class="py-2 pr-4 text-slate-700">{{ b.block_type_name ?? '—' }}</td>
                  <td class="py-2 pr-4 text-slate-700">{{ b.length_mm }} x {{ b.width_mm }} x {{ b.height_mm }}</td>
                  <td class="py-2 pr-4 text-slate-700">{{ b.weight != null ? nf2.format(b.weight) : '—' }}</td>
                  <td class="py-2 pr-4">
                    <span v-if="b.is_scrap" class="text-xs font-semibold text-rose-700">Refugo</span>
                    <span v-else-if="b.already_dispatched" class="text-xs font-semibold text-amber-700">Já baixado</span>
                    <span v-else class="text-xs font-semibold text-emerald-700">Disponível</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
            <div class="text-sm text-slate-700">
              Selecionados: <span class="font-semibold">{{ selectedCount }}</span>
            </div>
            <div v-if="form.errors.block_production_ids" class="text-sm font-medium text-rose-600">
              {{ form.errors.block_production_ids }}
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <Button type="submit" variant="primary" :loading="form.processing" :disabled="!selectedCount">
            Salvar alterações
          </Button>
          <Button type="button" variant="ghost" :href="route('block-dispatches.index')">
            Cancelar
          </Button>
        </div>
      </form>
    </section>
  </AdminLayout>
</template>
