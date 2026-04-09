<script setup>
import { ref, watch } from 'vue';
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar ação' },
  message: { type: String, default: 'Deseja prosseguir?' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  variant: { type: String, default: 'primary' }, // primary | danger
  processing: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);
const open = ref(props.modelValue);

watch(() => props.modelValue, (v) => open.value = v);
watch(open, (v) => emit('update:modelValue', v));

const onConfirm = () => emit('confirm');
const onCancel = () => { open.value = false; emit('cancel'); };
</script>

<template>
  <Modal v-model="open" :title="title" size="sm" :lockScroll="true" :closeOnBackdrop="false">
    <p class="text-slate-700">{{ message }}</p>
    <template #footer>
      <Button variant="outline" :disabled="processing" @click="onCancel">{{ cancelText }}</Button>
      <Button :variant="variant" :loading="processing" @click="onConfirm">
        <span v-if="!processing">{{ confirmText }}</span>
        <span v-else>Processando…</span>
      </Button>
    </template>
  </Modal>
</template>

<style scoped>
</style>

