# TimelineScroll Component

Componente Vue 3 para timeline horizontal com scroll avançado, otimizado para interações de leads.

## Arquitetura Modular

O componente foi refatorado para ser altamente modular e configurável:

### Composables
- **`useTimelineScroll`** - Gerencia estado básico do scroll e visibilidade das setas
- **`useTimelineNavigation`** - Gerencia navegação por teclado e cliques nas setas
- **`useTimelineTouch`** - Gerencia touch/drag com efeito de inércia

### Componentes
- **`TimelineArrow`** - Componente separado para as setas de navegação
- **`TimelineScroll`** - Componente principal orquestrador

## Props de Configuração

### Comportamento
```javascript
{
  scrollStep: 320,           // Passo do scroll (px)
  arrowMargin: 20,           // Margem para mostrar/ocultar setas
  inertiaDecay: 0.92,        // Decaimento da inércia (0-1)
  minVelocity: 2,            // Velocidade mínima para inércia
  enableWheelScroll: true,   // Habilitar scroll com roda do mouse
  enableTouchDrag: true,     // Habilitar drag com touch
  enableKeyboardNav: true,   // Habilitar navegação por teclado
}
```

### Aparência
```javascript
{
  gap: 'gap-8',               // Espaçamento entre itens (Tailwind)
  padding: 'py-2',            // Padding interno (Tailwind)
  lineColor: '#e2e8f0',       // Cor da linha horizontal
  markerColor: '#3b82f6',     // Cor dos marcadores
  markerSize: '8px',          // Tamanho dos marcadores
  lineHeight: '2px',          // Altura da linha horizontal
  verticalLineHeight: '1rem',  // Altura das linhas verticais
}
```

### Acessibilidade
```javascript
{
  ariaLabel: 'Linha do tempo de interações',
  ariaPrevLabel: 'Rolar para a esquerda',
  ariaNextLabel: 'Rolar para a direita',
  dir: 'ltr',                 // Direção do texto
}
```

## Exemplo de Uso

```vue
<template>
  <TimelineScroll
    :scroll-step="400"
    :line-color="'#10b981'"
    :marker-color="'#059669'"
    :gap="'gap-6'"
    aria-label="Timeline de atividades"
  >
    <div v-for="item in items" :key="item.id" class="timeline-item">
      <!-- Conteúdo do item -->
    </div>
  </TimelineScroll>
</template>

<script setup>
import TimelineScroll from '@/components/timeline/TimelineScroll.vue';
</script>
```

## Funcionalidades

- ✅ Scroll horizontal suave
- ✅ Navegação por teclado (setas, Home, End)
- ✅ Drag/touch com inércia
- ✅ Scroll com roda do mouse
- ✅ Setas de navegação automática
- ✅ Acessibilidade completa
- ✅ Altamente configurável
- ✅ Performance otimizada

## Extensibilidade

Para adicionar novas funcionalidades:

1. **Novo tipo de navegação**: Crie um novo composable seguindo o padrão dos existentes
2. **Novo estilo visual**: Adicione novas props e variáveis CSS
3. **Nova interação**: Estenda os handlers de eventos existentes

O design modular permite fácil manutenção e evolução do componente.