<script setup>
import { computed, watch, reactive } from 'vue';
import Checkbox from '@/components/ui/Checkbox.vue';

const props = defineProps({
  resources: { type: Object, required: true },
  role: { type: String, default: 'user' },
  modelValue: { type: Object, default: () => ({}) }, // permissions
  modules: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue', 'update:modules']);

const isAdmin = computed(() => props.role === 'admin');

const permissions = reactive(JSON.parse(JSON.stringify(props.modelValue || {})));
const modules = reactive(JSON.parse(JSON.stringify(props.modules || {})));

const ensureStructure = () => {
  Object.entries(props.resources || {}).forEach(([key, resource]) => {
    if (!permissions[key]) permissions[key] = {};
    if (modules[key] == null) modules[key] = false;
    const abilities = Object.keys(resource.abilities || {});
    abilities.forEach((a) => {
      if (permissions[key][a] == null) permissions[key][a] = false;
    });
  });
};

ensureStructure();

// Sync out
watch(() => permissions, (v) => emit('update:modelValue', v), { deep: true });
watch(() => modules, (v) => emit('update:modules', v), { deep: true });

// When admin, force-enable all
watch(isAdmin, (val) => {
  if (val) {
    Object.entries(props.resources || {}).forEach(([key, resource]) => {
      modules[key] = true;
      Object.keys(resource.abilities || {}).forEach((a) => {
        permissions[key][a] = true;
      });
    });
  }
});

const toggleModule = (key, on) => {
  modules[key] = !!on;
  if (!on) {
    Object.keys(props.resources?.[key]?.abilities || {}).forEach((a) => {
      permissions[key][a] = false;
    });
  }
};
</script>

<template>
  <fieldset class="space-y-3">
    <legend class="text-sm font-semibold text-slate-700">Permissões</legend>
    <div class="space-y-8">
      <fieldset v-for="(resource, key) in resources" :key="key" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <legend class="flex items-center justify-between gap-4 px-1 text-sm font-semibold text-slate-800">
          <span>{{ resource.label }}</span>
          <Checkbox
            :model-value="isAdmin ? true : !!modules[key]"
            :disabled="isAdmin"
            @update:modelValue="(v) => toggleModule(key, v)"
          >
            <span class="text-xs font-medium text-slate-600">Acesso ao módulo</span>
          </Checkbox>
        </legend>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <Checkbox v-for="(label, ability) in resource.abilities"
                    :key="ability"
                    :model-value="isAdmin ? true : !!permissions[key][ability]"
                    :disabled="isAdmin || !modules[key]"
                    @update:modelValue="(v) => permissions[key][ability] = !!v">
            {{ label }}
          </Checkbox>
        </div>
        <p class="mt-2 text-xs text-slate-500" v-if="isAdmin">
          Todas as permissões e módulos estão habilitados para administradores.
        </p>
      </fieldset>
    </div>
  </fieldset>
</template>
