<script setup>
import { defineAsyncComponent } from 'vue';
const TimelineScroll = defineAsyncComponent(() => import('@/components/timeline/TimelineScroll.vue'));
import TimelineCard from '@/components/timeline/TimelineCard.vue';
import Button from '@/components/Button.vue';
import InputText from '@/components/InputText.vue';
import InputSelect from '@/components/InputSelect.vue';
import InputTextarea from '@/components/InputTextarea.vue';
import InputDatePicker from '@/components/InputDatePicker.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Switch from '@/components/ui/Switch.vue';
import HeroIcon from '@/components/icons/HeroIcon.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { formatPhone } from '@/utils/masks.js';
import axios from 'axios';
import { ref, computed, nextTick } from 'vue';
import { useToasts } from '@/components/toast/useToasts.js';

const { error: toastError, success: toastSuccess } = useToasts();

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Salvar' },
  cancelHref: { type: String, required: true },
  isEditing: { type: Boolean, default: false },
  leadId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['submit']);

// Estado para edição inline de interações
const editingInteractionIndex = ref(-1);
const showAddForm = ref(false);
const newInteraction = ref({
  type: 'phone_call',
  interacted_at: '',
  description: ''
});
const interactionErrors = ref({});

// Estado para confirmação de exclusão de interação
const deleteInteractionState = ref({ open: false, processing: false, interactionIndex: null });

// Estado para filtros
const filters = ref({
  dateRange: 'all', // all, today, week, month, custom
  customRange: { start: '', end: '' }, // para período personalizado
  types: [], // array de tipos selecionados
  createdBy: ''
});

// Computed para interações filtradas
const filteredInteractions = computed(() => {
  let filtered = [...sortedInteractions.value];

  // Filtro por data
  if (filters.value.dateRange !== 'all') {
    const now = new Date();
    let startDate = new Date();
    let endDate = new Date();

    switch (filters.value.dateRange) {
      case 'today':
        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(23, 59, 59, 999);
        break;
      case 'week':
        startDate.setDate(now.getDate() - 7);
        startDate.setHours(0, 0, 0, 0);
        break;
      case 'month':
        startDate.setMonth(now.getMonth() - 1);
        startDate.setHours(0, 0, 0, 0);
        break;
      case 'custom':
        if (filters.value.customRange.start) {
          startDate = new Date(filters.value.customRange.start);
          startDate.setHours(0, 0, 0, 0);
        }
        if (filters.value.customRange.end) {
          endDate = new Date(filters.value.customRange.end);
          endDate.setHours(23, 59, 59, 999);
        }
        break;
    }

    if (filters.value.dateRange === 'custom' && (!filters.value.customRange.start || !filters.value.customRange.end)) {
      // Se custom mas não preenchido, não filtra
    } else {
      filtered = filtered.filter(interaction => {
        const interactionDateStr = interaction.interacted_at || interaction.created_at;
        if (!interactionDateStr) return false;
        const interactionDate = new Date(interactionDateStr);
        if (isNaN(interactionDate.getTime())) return false;
        return interactionDate >= startDate && interactionDate <= endDate;
      });
    }
  }

  // Filtro por tipos
  if (filters.value.types.length > 0) {
    filtered = filtered.filter(interaction => filters.value.types.includes(interaction.type));
  }

  // Filtro por quem criou
  if (filters.value.createdBy.trim()) {
    const searchTerm = filters.value.createdBy.toLowerCase();
    filtered = filtered.filter(interaction =>
      (interaction.created_by || '').toLowerCase().includes(searchTerm)
    );
  }

  return filtered;
});

// Função para limpar filtros
const clearFilters = () => {
  filters.value = {
    dateRange: 'all',
    customRange: { start: '', end: '' },
    types: [],
    createdBy: ''
  };
};

// Inicializar interações se não existir
if (!props.form.interactions) {
  props.form.interactions = [];
}

// Computed para data/hora atual
const currentDateTime = computed(() => {
  const now = new Date();
  return now.toISOString().slice(0, 16).replace('T', ' '); // Formato YYYY-MM-DD HH:MM
});

// Função para obter data/hora atual fresca
const getCurrentDateTime = () => {
  const now = new Date();
  return now.toISOString().slice(0, 16).replace('T', ' '); // Formato YYYY-MM-DD HH:MM
};

// Computed para interações ordenadas (mais novo primeiro)
const sortedInteractions = computed(() => {
  if (!props.form.interactions) return [];
  return [...props.form.interactions].sort((a, b) => {
    const dateA = new Date(a.interacted_at || a.created_at || new Date());
    const dateB = new Date(b.interacted_at || b.created_at || new Date());
    return dateB.getTime() - dateA.getTime(); // Mais novo primeiro
  });
});

// Funções para gerenciar interações
const addInteraction = async () => {
  interactionErrors.value = {};

  // Validação básica
  if (!newInteraction.value.type) {
    interactionErrors.value.type = 'Tipo é obrigatório';
    return;
  }
  if (!newInteraction.value.interacted_at) {
    interactionErrors.value.interacted_at = 'Data e hora são obrigatórios';
    return;
  }
  if (!newInteraction.value.description.trim()) {
    interactionErrors.value.description = 'Descrição é obrigatória';
    return;
  }

  if (props.isEditing) {
    // Modo edição: salvar diretamente no banco
    await saveInteractionToDatabase();
  } else {
    // Modo criação: adicionar à lista em memória
    if (editingInteractionIndex.value >= 0) {
      // Salvar edição em memória
      props.form.interactions[editingInteractionIndex.value] = { ...newInteraction.value };
      editingInteractionIndex.value = -1;
      toastSuccess('Interação atualizada com sucesso!');
    } else {
      // Adicionar novo
      props.form.interactions.push({
        ...newInteraction.value,
        id: Date.now(), // ID temporário para frontend
        type_label: getTypeLabel(newInteraction.value.type),
        created_by: 'Você', // Placeholder
        created_at: new Date().toISOString() // Adicionar data de criação
      });
      toastSuccess('Interação adicionada com sucesso!');
    }
    resetNewInteraction();
    showAddForm.value = false;
  }
};

const editInteraction = (index) => {
  // No modo edição, só permite editar interações que já existem no backend (id real)
  if (props.isEditing && (!props.form.interactions[index].id || String(props.form.interactions[index].id).length > 10)) {
    toastError('Salve o lead antes de editar esta interação.');
    return;
  }
  const interaction = props.form.interactions[index];
  editingInteractionIndex.value = index;
  newInteraction.value = {
    type: interaction.type,
    interacted_at: interaction.interacted_at ? new Date(interaction.interacted_at).toISOString().slice(0, 16).replace('T', ' ') : getCurrentDateTime(),
    description: interaction.description
  };
  showAddForm.value = true;
  focusFirstInteractionField();
};

const saveInteractionToDatabase = async () => {
  try {
    const interactionData = {
      type: newInteraction.value.type,
      description: newInteraction.value.description,
      interacted_at: newInteraction.value.interacted_at
    };
    let result;

    if (editingInteractionIndex.value >= 0) {
      // Editando interação existente
      const interactionId = props.form.interactions[editingInteractionIndex.value].id;
      const { data } = await axios.patch(`/admin/leads/${props.leadId}/interactions/${interactionId}`, interactionData);
      result = data;
    } else {
      // Criando nova interação
      const { data } = await axios.post(`/admin/leads/${props.leadId}/interactions`, interactionData);
      result = data;
    }

    if (editingInteractionIndex.value >= 0) {
      props.form.interactions[editingInteractionIndex.value] = result.interaction;
      editingInteractionIndex.value = -1;
      toastSuccess('Interação atualizada com sucesso!');
    } else {
      props.form.interactions.unshift(result.interaction);
      toastSuccess('Interação adicionada com sucesso!');
    }

    resetNewInteraction();
    showAddForm.value = false;
  } catch (error) {
    console.error('Erro ao salvar interação:', error);
    toastError(error.response?.data?.message || error.message || 'Erro ao salvar interação. Tente novamente.');
  }
};

const confirmDeleteInteraction = (index) => {
  deleteInteractionState.value = { open: true, processing: false, interactionIndex: index };
};

const performDeleteInteraction = async () => {
  if (deleteInteractionState.value.interactionIndex === null) return;

  deleteInteractionState.value.processing = true;
  try {
    const index = deleteInteractionState.value.interactionIndex;

    // Verificar se o array existe
    if (!props.form.interactions || !Array.isArray(props.form.interactions)) {
      toastError('Lista de interações não encontrada.');
      return;
    }

    // Verificar se o índice é válido
    if (index < 0 || index >= props.form.interactions.length) {
      toastError('Interação não encontrada. Tente novamente.');
      return;
    }

    const interaction = props.form.interactions[index];

    // Verificar se a interação existe
    if (!interaction) {
      toastError('Interação não encontrada. Tente novamente.');
      return;
    }

    // Só deleta do backend se o id for real (não temporário) E estiver no modo edição
    const isRealId = interaction.id && String(interaction.id).length < 10 && Number.isInteger(Number(interaction.id));
    if (props.isEditing && isRealId) {
      // Modo edição: deletar do banco
      await deleteInteractionFromDatabase(interaction.id);
      // Após deletar com sucesso do banco, remover da lista local
      props.form.interactions.splice(index, 1);
      if (editingInteractionIndex.value === index) {
        cancelEdit();
      }
    } else {
      // Modo criação ou interação temporária: remover da lista em memória
      props.form.interactions.splice(index, 1);
      if (editingInteractionIndex.value === index) {
        cancelEdit();
      }
      toastSuccess('Interação removida com sucesso!');
    }
  } catch (error) {
    // Se houve erro na deleção do banco, não remove da lista
    console.error('Erro ao deletar interação:', error);
    toastError('Erro ao remover interação. Tente novamente.');
  } finally {
    deleteInteractionState.value.processing = false;
    deleteInteractionState.value.open = false;
    deleteInteractionState.value.interactionIndex = null;
  }
};

const deleteInteractionFromDatabase = async (interactionId) => {
  try {
    await axios.delete(`/admin/leads/${props.leadId}/interactions/${interactionId}`);
    toastSuccess('Interação excluída com sucesso!');
  } catch (error) {
    console.error('Erro ao excluir interação:', error);
    // Se a interação não existe (404), trata como sucesso (já foi removida)
    if (error.response?.status === 404) {
      toastSuccess('Interação excluída com sucesso!');
      return;
    }
    toastError(error.response?.data?.message || error.message || 'Erro ao excluir interação. Tente novamente.');
    throw error; // Re-throw para impedir a remoção da lista se falhou no banco
  }
};

const deleteInteraction = async (index) => {
  // Sempre mostrar confirmação antes de excluir
  confirmDeleteInteraction(index);
};

const cancelEdit = () => {
  editingInteractionIndex.value = -1;
  newInteraction.value = {
    type: 'phone_call',
    interacted_at: getCurrentDateTime(),
    description: ''
  };
  showAddForm.value = false;
  interactionErrors.value = {};
};

const resetNewInteraction = () => {
  newInteraction.value = {
    type: 'phone_call',
    interacted_at: getCurrentDateTime(),
    description: ''
  };
  interactionErrors.value = {};
};

const focusFirstInteractionField = () => {
  nextTick(() => {
    const firstField = document.querySelector('#interaction-form select');
    if (firstField) firstField.focus();
  });
};

const getTypeLabel = (type) => {
  const types = {
    phone_call: 'Ligação Telefônica',
    email: 'E-mail',
    meeting: 'Reunião',
    message: 'Mensagem',
    visit: 'Visita',
    other: 'Outro'
  };
  return types[type] || type;
};

const getTypeIcon = (type) => {
  const icons = {
    phone_call: 'phone',
    email: 'envelope',
    meeting: 'user-group',
    message: 'device-phone-mobile',
    visit: 'office-building',
    other: 'chat-bubble-oval-left-ellipsis'
  };
  return icons[type] || 'chat-bubble-oval-left-ellipsis';
};

const getTypeIconClass = (type) => {
  const classes = {
    phone_call: 'text-green-600',
    email: 'text-red-600',
    meeting: 'text-purple-600',
    message: 'text-blue-600',
    visit: 'text-orange-600',
    other: 'text-slate-500'
  };
  return classes[type] || 'text-slate-500';
};

// Computed para status como toggle (ativo/inativo)
const isActiveStatus = computed({
  get: () => props.form.status !== 'discarded',
  set: (value) => {
    props.form.status = value ? 'new' : 'discarded';
  }
});

const onSubmit = () => emit('submit');

const formatPhoneField = () => {
  props.form.phone = formatPhone(props.form.phone);
};
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-6 w-full">
    <div class="grid gap-6 sm:grid-cols-2">
      <label class="form-label">
        Nome *
        <InputText v-model="form.name" required :error="!!form.errors.name" />
        <span v-if="form.errors.name" class="text-sm font-medium text-rose-600">{{ form.errors.name }}</span>
      </label>
      <label class="form-label">
        Email
        <InputText v-model="form.email" type="email" :error="!!form.errors.email" />
        <span v-if="form.errors.email" class="text-sm font-medium text-rose-600">{{ form.errors.email }}</span>
      </label>
      <label class="form-label">
        Telefone
        <InputText v-model="form.phone" placeholder="(11) 99999-9999" :error="!!form.errors.phone" @input="formatPhoneField" maxlength="15" />
        <span v-if="form.errors.phone" class="text-sm font-medium text-rose-600">{{ form.errors.phone }}</span>
      </label>
      <label class="form-label">
        Empresa
        <InputText v-model="form.company" :error="!!form.errors.company" />
        <span v-if="form.errors.company" class="text-sm font-medium text-rose-600">{{ form.errors.company }}</span>
      </label>
      <label class="form-label">
        Origem *
        <InputSelect v-model="form.source" :options="[
          { value: 'site', label: 'Site' },
          { value: 'indicacao', label: 'Indicação' },
          { value: 'evento', label: 'Evento' },
          { value: 'manual', label: 'Manual' }
        ]" :error="!!form.errors.source" :placeholder="null" />
        <span v-if="form.errors.source" class="text-sm font-medium text-rose-600">{{ form.errors.source }}</span>
      </label>

      <div class="switch-field sm:col-span-2">
        <span class="switch-label">Status do lead</span>
        <Switch v-model="isActiveStatus" :true-value="true" :false-value="false" />
        <span class="switch-status" :class="{ 'inactive': !isActiveStatus }">
          {{ isActiveStatus ? 'Ativo' : 'Descartado' }}
        </span>
      </div>
      <span v-if="form.errors.status" class="text-sm font-medium text-rose-600 sm:col-span-2">{{ form.errors.status }}</span>
    </div>

  <fieldset class="space-y-3 max-w-full w-full min-w-0 box-border">
      <legend class="text-sm font-semibold text-slate-700">Histórico de Interações</legend>

      <!-- Formulário inline para adicionar/editar interação -->
      <div v-if="showAddForm" class="border border-slate-200 rounded-lg p-4 bg-slate-50">
        <div id="interaction-form" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Tipo *
            <InputSelect v-model="newInteraction.type" :options="[
              { value: 'phone_call', label: 'Ligação Telefônica' },
              { value: 'email', label: 'E-mail' },
              { value: 'meeting', label: 'Reunião' },
              { value: 'message', label: 'Mensagem' },
              { value: 'visit', label: 'Visita' },
              { value: 'other', label: 'Outro' }
            ]" required :error="!!interactionErrors.type" :placeholder="null" />
            <span v-if="interactionErrors.type" class="text-sm font-medium text-rose-600">{{ interactionErrors.type }}</span>
          </label>
          <label class="form-label">
            Data e Hora *
            <InputDatePicker v-model="newInteraction.interacted_at" :withTime="true" :error="!!interactionErrors.interacted_at" />
            <span v-if="interactionErrors.interacted_at" class="text-sm font-medium text-rose-600">{{ interactionErrors.interacted_at }}</span>
          </label>
          <label class="form-label sm:col-span-2 lg:col-span-3">
            Descrição *
            <InputTextarea v-model="newInteraction.description" required :error="!!interactionErrors.description" />
            <span v-if="interactionErrors.description" class="text-sm font-medium text-rose-600">{{ interactionErrors.description }}</span>
          </label>
          <div class="flex items-end gap-2 lg:col-span-3">
            <Button type="button" @click="addInteraction" variant="primary" size="sm">
              {{ editingInteractionIndex >= 0 ? 'Salvar' : 'Adicionar' }}
            </Button>
            <Button type="button" @click="cancelEdit" variant="ghost" size="sm">
              Cancelar
            </Button>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div v-if="(form.interactions || []).length > 0" class="border border-slate-200 rounded-lg p-4 bg-slate-50 space-y-4">
        <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          <label class="form-label">
            Período
            <InputSelect v-model="filters.dateRange" :options="[
              { value: 'all', label: 'Todos' },
              { value: 'today', label: 'Hoje' },
              { value: 'week', label: 'Última semana' },
              { value: 'month', label: 'Último mês' },
              { value: 'custom', label: 'Personalizado' }
            ]" placeholder="" class="w-full" />
          </label>
          <label v-if="filters.dateRange === 'custom'" class="form-label">
            Período personalizado
            <InputDatePicker v-model="filters.customRange" :range="true" :withTime="true" placeholder="Selecionar período e horário" class="w-full" />
          </label>
          <label class="form-label">
            Quem interagiu
            <InputText v-model="filters.createdBy" placeholder="Nome do usuário" class="w-full" />
          </label>
        </div>
        <div>
          <div class="text-sm font-semibold text-slate-700 mb-2">Tipos</div>
          <div class="flex flex-wrap gap-6">
            <Checkbox v-for="type in [
              { value: 'phone_call', label: 'Ligação' },
              { value: 'email', label: 'E-mail' },
              { value: 'meeting', label: 'Reunião' },
              { value: 'message', label: 'Mensagem' },
              { value: 'visit', label: 'Visita' },
              { value: 'other', label: 'Outro' }
            ]" :key="type.value" v-model="filters.types" :value="type.value">
              {{ type.label }}
            </Checkbox>
          </div>
        </div>
        <div class="flex justify-end">
          <Button type="button" @click="clearFilters" variant="outline-danger" size="sm">
            Limpar filtros
          </Button>
        </div>
      </div>

      <!-- Linha do tempo horizontal de interações -->
      <div v-if="filteredInteractions.length > 0" class="mt-4 min-w-0">
        <TimelineScroll aria-label="Linha do tempo de interações">
          <div
            v-for="(interaction, index) in filteredInteractions"
            :key="interaction.id || index"
            class="relative flex flex-col items-center flex-shrink-0 min-w-72 max-w-80"
          >
            <TimelineCard
              :interaction="interaction"
              :show-actions="true"
              @edit="editInteraction(props.form.interactions.indexOf(interaction))"
              @delete="deleteInteraction(props.form.interactions.indexOf(interaction))"
            />
          </div>
        </TimelineScroll>
      </div>

      <div v-else-if="(form.interactions || []).length > 0" class="mt-4">
        <div class="timeline-container">
          <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
            <HeroIcon name="funnel" class="w-12 h-12 text-slate-300" />
            <p class="mt-4 text-sm text-slate-500">Nenhuma interação encontrada com os filtros aplicados.</p>
          </div>
        </div>
      </div>

      <div v-else class="mt-4">
        <div class="timeline-container">
          <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
            <HeroIcon name="chat-bubble-oval-left-ellipsis" class="w-12 h-12 text-slate-300" />
            <p class="mt-4 text-sm text-slate-500">Nenhuma interação registrada ainda.</p>
            <p class="text-xs text-slate-400 mt-1">Adicione a primeira interação para começar o histórico.</p>
          </div>
        </div>
      </div>      <!-- Botão para adicionar nova interação -->
      <div v-if="!showAddForm" class="flex justify-center pt-4">
        <Button type="button" @click="showAddForm = true; newInteraction.interacted_at = getCurrentDateTime(); focusFirstInteractionField()" variant="ghost" size="sm">
          Adicionar nova interação
        </Button>
      </div>
    </fieldset>

    <div class="flex flex-wrap gap-3">
      <Button type="submit" variant="primary" :loading="form.processing">
        <HeroIcon name="check" class="h-5 w-5" />
        <span v-if="!form.processing">{{ submitLabel }}</span>
        <span v-else>Salvando…</span>
      </Button>
      <Button type="button" variant="ghost" :href="cancelHref">Cancelar</Button>
    </div>
  </form>

  <ConfirmModal v-model="deleteInteractionState.open"
                :processing="deleteInteractionState.processing"
                title="Excluir interação"
                message="Deseja realmente remover esta interação?"
                confirm-text="Excluir"
                variant="danger"
                @confirm="performDeleteInteraction" />
</template>

<style scoped>
.form-label { display:flex; flex-direction:column; gap:.5rem; font-weight:600; color:#334155 }
</style>
