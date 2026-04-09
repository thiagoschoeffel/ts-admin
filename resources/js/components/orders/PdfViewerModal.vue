<script setup>
import { ref, watch } from 'vue';
import Modal from '@/components/Modal.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  pdfUrl: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(props.modelValue);
const loading = ref(false);

watch(() => props.modelValue, (v) => {
  open.value = v;
  if (v) {
    loading.value = true;
    // Hide skeleton after 1 second, assuming PDF has started loading
    setTimeout(() => loading.value = false, 1000);
  }
});
watch(open, (v) => emit('update:modelValue', v));
</script><template>
  <Modal v-model="open" title="Visualizar PDF do Pedido" size="xl" :lockScroll="true" :closeOnBackdrop="true">
    <div v-if="loading" class="space-y-4">
      <div class="skeleton h-96 w-full rounded-md"></div>
    </div>
    <div v-else-if="pdfUrl" class="w-full">
      <iframe :key="pdfUrl" :src="pdfUrl" width="100%" height="600" style="border: none;"></iframe>
    </div>
    <div v-else class="text-center text-slate-500">
      URL do PDF não fornecida.
    </div>
  </Modal>
</template>
