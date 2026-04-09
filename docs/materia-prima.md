# Módulo Matérias-Primas

## Visão Geral

O módulo Matérias-Primas permite o gerenciamento completo de matérias-primas da empresa, com funcionalidades de CRUD (Criar, Ler, Atualizar, Excluir), auditoria de alterações, permissões granulares e testes abrangentes.

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

-   `raw_materials.view`: Visualizar matérias-primas
-   `raw_materials.create`: Criar matérias-primas
-   `raw_materials.update`: Editar matérias-primas
-   `raw_materials.delete`: Excluir matérias-primas

### Auditoria

-   Registro de criação, atualização e exclusão com usuário e deltas de campos
-   Logs armazenados via observer no modelo RawMaterial

## Estrutura Técnica

### Modelos

-   `App\Models\RawMaterial`: Modelo principal com scopes `active()` e `search()`

### Controladores

-   `App\Http\Controllers\RawMaterialController`: CRUD completo

### Políticas

-   `App\Policies\RawMaterialPolicy`: Controle de permissões

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

-   `GET /admin/raw-materials`: Index
-   `GET /admin/raw-materials/create`: Create
-   `POST /admin/raw-materials`: Store
-   `GET /admin/raw-materials/{rawMaterial}/edit`: Edit
-   `PATCH /admin/raw-materials/{rawMaterial}`: Update
-   `DELETE /admin/raw-materials/{rawMaterial}`: Destroy
-   `GET /admin/raw-materials/{rawMaterial}/modal`: Modal details (JSON)

### Menu

-   Localizado em "Cadastros" > "Matérias-Primas" (após Produtos, com divider acima)

### Testes

-   `Tests\Feature\RawMaterialControllerTest`: CRUD, filtros, validações, permissões
-   `Tests\Unit\RawMaterialPolicyTest`: Políticas de acesso

## Como Usar

1. **Acesso**: No menu lateral, "Cadastros" > "Matérias-Primas"
2. **Criar**: Clique em "Nova Matéria-Prima" e preencha nome e status
3. **Editar**: Na listagem, clique em "Editar" na matéria-prima desejada
4. **Excluir**: Na listagem, clique em "Excluir" (confirme a ação)
5. **Detalhes**: Na listagem, clique no nome da matéria-prima para abrir modal

## Permissões Necessárias

Para acessar o módulo, o usuário deve ter pelo menos `raw_materials.view`. Para ações específicas:

-   Criar: `raw_materials.create`
-   Editar: `raw_materials.update`
-   Excluir: `raw_materials.delete`

Administradores têm acesso total automaticamente.

## Validações

-   **Nome**: Obrigatório, string, 1-255 caracteres, único (ignorando o próprio registro na edição)
-   **Status**: Obrigatório, deve ser 'active' ou 'inactive'

## Auditoria

Todas as operações (criar, editar, excluir) são registradas nos logs com:

-   ID da matéria-prima
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
-   Métricas por matéria-prima
