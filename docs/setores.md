# Módulo Setores

## Visão Geral

O módulo Setores permite o gerenciamento completo de setores da empresa, com funcionalidades de CRUD (Criar, Ler, Atualizar, Excluir), auditoria de alterações, permissões granulares e testes abrangentes.

## Funcionalidades

### Listagem

-   **Colunas**: Nome, Status, Atualizado em, Ações
-   **Filtros**: Por status (Ativo/Inativo) e busca por nome
-   **Paginação**: 10/25/50/100 itens por página
-   **Ações**: Ver detalhes (modal), Editar, Excluir (conforme permissões)

### Cadastro/Edição

-   **Campos**: Nome (obrigatório, único), Status (Ativo/Inativo)
-   **Validações**: Nome min/max conforme padrão, status obrigatório

### Detalhes

-   **Modal**: Nome, Status (badge), Criado em, Atualizado em
-   **Auditoria**: Últimas alterações (em desenvolvimento)

### Permissões

-   `sectors.view`: Visualizar setores
-   `sectors.create`: Criar setores
-   `sectors.update`: Editar setores
-   `sectors.delete`: Excluir setores

### Auditoria

-   Registro de criação, atualização e exclusão com usuário e deltas de campos
-   Logs armazenados via observer no modelo Sector

## Estrutura Técnica

### Modelos

-   `App\Models\Sector`: Modelo principal com scopes `active()` e `search()`

### Controladores

-   `App\Http\Controllers\SectorController`: CRUD completo

### Políticas

-   `App\Policies\SectorPolicy`: Controle de permissões

### Form Requests

-   `App\Http\Requests\StoreSectorRequest`: Validação de criação
-   `App\Http\Requests\UpdateSectorRequest`: Validação de atualização

### Factories/Seeders

-   `Database\Factories\SectorFactory`: Geração de dados de teste
-   `Database\Seeders\SectorSeeder`: Seeds base com setores padrão

### Views (Inertia/Vue)

-   `Admin/Sectors/Index.vue`: Listagem com filtros e DataTable
-   `Admin/Sectors/Create.vue`: Formulário de criação
-   `Admin/Sectors/Edit.vue`: Formulário de edição
-   `Admin/Sectors/SectorForm.vue`: Componente de formulário reutilizável
-   `Admin/Sectors/SectorDetailsModal.vue`: Modal de detalhes

### Rotas

-   `GET /admin/sectors`: Index
-   `GET /admin/sectors/create`: Create
-   `POST /admin/sectors`: Store
-   `GET /admin/sectors/{sector}/edit`: Edit
-   `PATCH /admin/sectors/{sector}`: Update
-   `DELETE /admin/sectors/{sector}`: Destroy
-   `GET /admin/sectors/{sector}/modal`: Modal details (JSON)

### Menu

-   Localizado em "Cadastros" > "Setores" (após Produtos, com divider acima)

### Testes

-   `Tests\Feature\SectorControllerTest`: CRUD, filtros, validações, permissões
-   `Tests\Unit\SectorPolicyTest`: Políticas de acesso

## Como Usar

1. **Acesso**: No menu lateral, "Cadastros" > "Setores"
2. **Criar**: Clique em "Novo Setor" e preencha nome e status
3. **Editar**: Na listagem, clique em "Editar" no setor desejado
4. **Excluir**: Na listagem, clique em "Excluir" (confirme a ação)
5. **Detalhes**: Na listagem, clique no nome do setor para abrir modal

## Permissões Necessárias

Para acessar o módulo, o usuário deve ter pelo menos `sectors.view`. Para ações específicas:

-   Criar: `sectors.create`
-   Editar: `sectors.update`
-   Excluir: `sectors.delete`

Administradores têm acesso total automaticamente.

## Validações

-   **Nome**: Obrigatório, string, 1-255 caracteres, único (ignorando o próprio registro na edição)
-   **Status**: Obrigatório, deve ser 'active' ou 'inactive'

## Auditoria

Todas as operações (criar, editar, excluir) são registradas nos logs com:

-   ID do setor
-   Campos alterados
-   ID do usuário responsável
-   Timestamp

## Seeds

Setores base incluídos:

-   Produção
-   Manutenção
-   Qualidade
-   Expedição
-   Logística
-   Recursos Humanos
-   Financeiro
-   Compras
-   Vendas
-   TI
-   Administração
-   Marketing
-   Pesquisa e Desenvolvimento
-   Segurança
-   Limpeza

## Futuro

-   Integração com Paradas de Máquina
-   Relatórios de setores
-   Hierarquia de setores
-   Métricas por setor
