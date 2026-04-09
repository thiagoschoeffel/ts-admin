# Ziggy Route Helper

Ziggy permite usar nomes de rotas do Laravel no JavaScript/Vue, mantendo as URLs sincronizadas automaticamente.

## Como usar

### Em componentes Vue:
```vue
<script setup>
import { route } from '@/ziggy-client';
</script>

<template>
  <!-- Link relativo (padrão para Inertia) -->
  <Link :href="route('users.index')">Usuários</Link>

  <!-- Com parâmetros -->
  <Link :href="route('users.show', { user: user.id })">Ver usuário</Link>

  <!-- URL absoluta -->
  <a :href="route('home', {}, true)">Home (absoluta)</a>
</template>
```

### No JavaScript:
```js
import { route } from '@/ziggy-client';

const url = route('clients.create'); // '/admin/clients/create'
const editUrl = route('clients.edit', { client: 123 }); // '/admin/clients/123/edit'
```

## Comandos úteis

- `npm run ziggy` - Regenera o arquivo de rotas
- `npm run ziggy:check` - Regenera e confirma atualização
- `php artisan ziggy:generate` - Comando Artisan equivalente

## Funcionalidades

- ✅ URLs relativas por padrão (compatível com Inertia)
- ✅ Origem do navegador em runtime (evita CORS)
- ✅ Logs de erro em desenvolvimento
- ✅ Sincronização automática com rotas Laravel
- ✅ Suporte a parâmetros e query strings

## Troubleshooting

Se uma rota não for encontrada:
1. Execute `npm run ziggy` para atualizar
2. Verifique se a rota existe em `routes/web.php`
3. Confirme se a rota tem um `name()` definido

## Integração com build

O Ziggy é automaticamente regenerado em:
- `npm run dev` - Desenvolvimento
- `npm run build` - Produção</content>
<parameter name="filePath">/home/thiagoscrn/Documents/Workspace/example-app/docs/ziggy.md
