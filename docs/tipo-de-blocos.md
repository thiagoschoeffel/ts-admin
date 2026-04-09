# Módulo Matérias-Primas

## Visão Geral

O módulo Matérias-Primas permite o gerenciamento completo de matérias-primas da empresa, com funcionalidades de CRUD (Criar, Ler, Atualizar, Excluir), auditoria de alterações, permissões granulares e testes abrangentes.

## Funcionalidades

### Listagem

-   **Colunas**: Nome, Matéria Prima Virgem (%), Status, Atualizado em, Ações
-   **Filtros**: Por status (Ativo/Inativo) e busca por nome
-   **Paginação**: 10/25/50/100 itens por página
-   **Ações**: Ver detalhes (modal), Editar, Excluir (conforme permissões)

### Cadastro/Edição

-   **Campos**: Nome (obrigatório, único), Matéria Prima Virgem (%) (obrigatório, 0-100%), Status (Ativo/Inativo)
-   **Validações**: Nome min/max conforme padrão, percentual numérico opcional, status obrigatório

### Detalhes

-   **Modal**: Nome, Matéria Prima Virgem (%), Status (badge), Criado em, Atualizado em
-   **Auditoria**: Últimas alterações (em desenvolvimento)

### Permissões

-   `block_types.view`: Visualizar tipos de blocos
-   `block_types.create`: Criar tipos de blocos
-   `block_types.update`: Editar tipos de blocos
-   `block_types.delete`: Excluir tipos de blocos

### Auditoria

-   Registro de criação, atualização e exclusão com usuário e deltas de campos
-   Logs armazenados via observer no modelo BlockType

## Estrutura Técnica

### Modelos

-   `App\Models\BlockType`: Modelo principal com scopes `active()` e `search()`

### Controladores

-   `App\Http\Controllers\BlockTypeController`: CRUD completo

### Políticas

-   `App\Policies\BlockTypePolicy`: Controle de permissões

### Form Requests

-   `App\Http\Requests\StoreRawMaterialRequest`: Validação de criação
-   `App\Http\Requests\UpdateRawMaterialRequest`: Validação de atualização

### Factories/Seeders

-   `Database\Factories\RawMaterialFactory`: Geração de dados de teste
-   `Database\Seeders\RawMaterialSeeder`: Seeds base com matérias-primas padrão

### Views (Inertia/Vue)

-   `Admin/RawMaterials/Index.vue`: Listagem com filtros e DataTable
-   `Admin/RawMaterials/Create.vue`: Formulário de criação
-   `Admin/RawMaterials/Edit.vue`: Formulário de edição
-   `Admin/RawMaterials/RawMaterialForm.vue`: Componente de formulário reutilizável
-   `Admin/RawMaterials/RawMaterialDetailsModal.vue`: Modal de detalhes

### Rotas

-   `GET /admin/block-types`: Index
-   `GET /admin/block-types/create`: Create
-   `POST /admin/block-types`: Store
-   `GET /admin/block-types/{rawMaterial}/edit`: Edit
-   `PATCH /admin/block-types/{rawMaterial}`: Update
-   `DELETE /admin/block-types/{rawMaterial}`: Destroy
-   `GET /admin/block-types/{rawMaterial}/modal`: Modal details (JSON)

### Menu

-   Localizado em "Industrialização EPS" > "Tipos de Blocos" (abaixo de Matérias-Primas)

### Testes

-   `Tests\Feature\RawMaterialControllerTest`: CRUD, filtros, validações, permissões
-   `Tests\Unit\RawMaterialPolicyTest`: Políticas de acesso

## Como Usar

1. **Acesso**: No menu lateral, "Industrialização EPS" > "Tipos de Blocos"
2. **Criar**: Clique em "Novo Tipo de Bloco" e preencha nome, matéria prima virgem (%) (obrigatório) e status
3. **Editar**: Na listagem, clique em "Editar" no tipo de bloco desejado
4. **Excluir**: Na listagem, clique em "Excluir" (confirme a ação)
5. **Detalhes**: Na listagem, clique no nome do tipo de bloco para abrir modal

## Permissões Necessárias

Para acessar o módulo, o usuário deve ter pelo menos `block_types.view`. Para ações específicas:

-   Criar: `block_types.create`
-   Editar: `block_types.update`
-   Excluir: `block_types.delete`

Administradores têm acesso total automaticamente.

## Validações

-   **Nome**: Obrigatório, string, 1-255 caracteres, único (ignorando o próprio registro na edição)
-   **Matéria Prima Virgem (%)**: Obrigatório, numérico, 0-100%, até 2 casas decimais
-   **Status**: Obrigatório, deve ser 'active' ou 'inactive'

## Auditoria

Todas as operações (criar, editar, excluir) são registradas nos logs com:

-   ID da tipo de bloco
-   Campos alterados
-   ID do usuário responsável
-   Timestamp

## Seeds

Matérias-primas base incluídas:

-   Aço
-   Alumínio
-   Cobre
-   Ferro
-   Plástico
-   Madeira
-   Vidro
-   Tecido
-   Couro
-   Papel
-   Borracha
-   Cerâmica
-   Concreto
-   Fibra de carbono
-   Silicone

## Futuro

-   Integração com Paradas de Máquina
-   Relatórios de matérias-primas
-   Hierarquia de matérias-primas
-   Métricas por tipo de bloco
