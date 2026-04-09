# InputDatePicker

Componente DatePicker nativo seguindo o tema e padrões de inputs do projeto.

Local: `resources/js/components/InputDatePicker.vue`

## Recursos

- Seleção de data simples (com e sem horário)
- Seleção de período (range) sem tempo e com tempo
- Comportamento e estilos consistentes com os demais inputs (bordas, tamanhos, estados)
- Fechamento automático para seleção simples sem horário
- Botão de limpar opcional

## API

- `v-model` (`modelValue`)
  - Single sem horário: `string | null` no formato `YYYY-MM-DD`
  - Single com horário: `string | null` no formato `YYYY-MM-DD HH:mm`
  - Range sem horário: `{ start: string|null, end: string|null }`
  - Range com horário: `{ start: string|null, end: string|null }` (ambos `YYYY-MM-DD HH:mm`)
- `range` (`boolean`, default `false`): modo período
- `withTime` (`boolean`, default `false`): inclui seleção de horário
- `size` (`'sm'|'md'|'lg'`, default `md`): tamanho do input
- `placeholder` (`string`): placeholder do campo
- `disabled`, `readonly` (`boolean`): estados do input
- `error`, `success` (`boolean`): variações de borda (vermelho/verde)
- `clearable` (`boolean`, default `true`): mostra botão para limpar valor
- `minDate`, `maxDate` (`string`): limites permitidos; aceita `YYYY-MM-DD` e `YYYY-MM-DD HH:mm`

## Exemplos

Single, sem horário:

```vue
<script setup>
import { ref } from 'vue'
import InputDatePicker from '@/components/InputDatePicker.vue'

const date = ref(null) // YYYY-MM-DD
</script>

<template>
  <InputDatePicker v-model="date" placeholder="Selecione a data" />
</template>
```

Single, com horário:

```vue
<InputDatePicker v-model="dateTime" :withTime="true" placeholder="Data e horário" />
<!-- v-model: YYYY-MM-DD HH:mm -->
```

Range, sem horário:

```vue
<InputDatePicker v-model="period" :range="true" placeholder="Período" />
<!-- v-model: { start: 'YYYY-MM-DD' | null, end: 'YYYY-MM-DD' | null } -->
```

Range, com horário:

```vue
<InputDatePicker v-model="periodWithTime" :range="true" :withTime="true" placeholder="Período e horário" />
<!-- v-model: { start: 'YYYY-MM-DD HH:mm' | null, end: 'YYYY-MM-DD HH:mm' | null } -->
```

Com limites:

```vue
<InputDatePicker v-model="date" minDate="2025-01-01" maxDate="2025-12-31" />
```

## Notas

- Exibição no input é formatada em pt-BR (`dd/MM/yyyy` e `dd/MM/yyyy HH:mm`).
- Emissão do valor (`v-model`) é padrão ISO amigável a back-end Laravel.
- O painel utiliza `Dropdown.vue` com `Teleport` para evitar clipping em tabelas/containers.

