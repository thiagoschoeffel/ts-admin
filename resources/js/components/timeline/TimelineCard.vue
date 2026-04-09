<template>
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 w-full transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 group">
    <div class="flex justify-between items-center mb-3">
      <div class="flex items-center gap-2">
        <HeroIcon :name="getTypeIcon(interaction.type)" class="w-5 h-5" :class="getTypeIconClass(interaction.type)" />
        <span class="font-semibold text-sm text-slate-700">{{ getTypeLabel(interaction.type) }}</span>
      </div>
      <div v-if="showActions" class="flex gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
        <Button
          type="button"
          variant="primary"
          size="sm"
          @click="$emit('edit', interaction)"
          title="Editar interação"
        >
          <HeroIcon name="pencil" class="h-4 w-4" />
        </Button>
        <Button
          type="button"
          variant="danger"
          size="sm"
          @click="$emit('delete', interaction)"
          title="Excluir interação"
        >
          <HeroIcon name="trash" class="h-4 w-4" />
        </Button>
      </div>
    </div>
    <div class="mb-3">
      <p class="text-sm leading-relaxed text-slate-600 m-0">{{ interaction.description }}</p>
    </div>
    <div class="border-t border-slate-100 pt-3">
      <div class="flex justify-between items-center text-xs text-slate-500">
        <span class="font-medium">{{ formatInteractionDate(interaction.interacted_at) }}</span>
        <span class="italic">{{ interaction.created_by || 'Sistema' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import HeroIcon from '@/components/icons/HeroIcon.vue';
import Button from '@/components/Button.vue';

const props = defineProps({
  interaction: {
    type: Object,
    required: true
  },
  showActions: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['edit', 'delete']);

// Funções auxiliares para interações
const getTypeIcon = (type) => {
  const icons = {
    'phone_call': 'phone',
    'email': 'envelope',
    'meeting': 'user-group',
    'message': 'device-phone-mobile',
    'visit': 'office-building',
    'other': 'chat-bubble-oval-left-ellipsis'
  };
  return icons[type] || 'chat-bubble-oval-left-ellipsis';
};

const getTypeLabel = (type) => {
  const labels = {
    'phone_call': 'Ligação Telefônica',
    'email': 'E-mail',
    'meeting': 'Reunião',
    'message': 'Mensagem',
    'visit': 'Visita',
    'other': 'Outro'
  };
  return labels[type] || 'Outro';
};

const getTypeIconClass = (type) => {
  const classes = {
    'phone_call': 'text-green-600',
    'email': 'text-red-600',
    'meeting': 'text-purple-600',
    'message': 'text-blue-600',
    'visit': 'text-orange-600',
    'other': 'text-slate-500'
  };
  return classes[type] || 'text-slate-500';
};

const formatInteractionDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return dateString;

  const now = new Date();
  const diffInHours = (now - date) / (1000 * 60 * 60);

  if (diffInHours < 24) {
    // Menos de 24 horas - mostrar "há X horas" ou "há X minutos"
    if (diffInHours < 1) {
      const diffInMinutes = Math.floor((now - date) / (1000 * 60));
      return diffInMinutes <= 1 ? 'agora mesmo' : `há ${diffInMinutes}min`;
    }
    const hours = Math.floor(diffInHours);
    return hours === 1 ? 'há 1h' : `há ${hours}h`;
  } else if (diffInHours < 24 * 7) {
    // Menos de 7 dias - mostrar dia da semana
    const days = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    return days[date.getDay()];
  } else {
    // Mais antigo - mostrar data
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    });
  }
};
</script>
