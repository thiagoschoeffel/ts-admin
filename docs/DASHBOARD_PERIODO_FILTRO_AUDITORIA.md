# Auditoria de Filtros de Período no Dashboard

**Data da Auditoria:** 30 de Outubro de 2025  
**Objetivo:** Garantir que todos os componentes e endpoints do dashboard respeitem corretamente o filtro de período selecionado pelo usuário.

---

## ✅ COMPONENTES FRONTEND - STATUS

### 1. **Dashboard.vue** (Componente Principal)

-   ✅ Period state: `ref({ start, end })`
-   ✅ Watch: Atualiza automaticamente quando período muda
-   ✅ Passa `period` para componentes filhos via props

### 2. **Cards de Resumo** (11 cards)

-   ✅ Fonte: `summary` (endpoint `/inventory/summary`)
-   ✅ Dados: Atualizam via `fetchSummary()` que respeita período
-   **Cards:**
    -   Entrada MP ✅
    -   Consumo MP ✅
    -   Produção Blocos ✅
    -   Refugos Blocos (und) ✅
    -   Refugos Blocos (kg) ✅
    -   Produção Blocos m³ ✅
    -   MP Virgem p/ Blocos ✅
    -   MP Reciclada p/ Blocos ✅
    -   Produção Moldados ✅
    -   Refugos Moldados ✅
    -   MP Virgem p/ Moldados ✅

### 3. **ReservationsBarChart**

-   ✅ Recebe `period` via props
-   ✅ Watch: `watch(() => props.period, fetchData, { deep: true })`
-   ✅ Endpoint: `/inventory/production/kg-by-day`

### 4. **ProductionByMaterialBarChart**

-   ✅ Recebe `period` via props
-   ✅ Watch: `watch(() => props.period, fetchData, { deep: true })`
-   ✅ Endpoint: `/inventory/production/kg-by-material-type`

### 5. **BlocksProducedByDayChart**

-   ✅ Recebe `period` via props
-   ✅ Watch: `watch(() => props.period, fetchData, { deep: true })`
-   ✅ Endpoint: `/inventory/blocks/produced-by-day`

### 6. **BlockProductionTable**

-   ✅ Recebe `period` via props
-   ✅ Watch: `watch(() => props.period, fetchData, { deep: true })`
-   ✅ Endpoint: `/inventory/block/production-by-type-and-dimensions`

### 7. **MoldedProductionAndScrapChart**

-   ✅ Recebe `period` via props
-   ✅ Watch: `watch(() => props.period, fetchData, { deep: true })`
-   ✅ Endpoint: `/inventory/molded/production-and-scrap-by-day`

### 8. **MoldedProductionRanking**

-   ✅ Recebe `data` do `summary.molded_loss_ranking`
-   ✅ Atualiza via `fetchSummary()` que respeita período

### 9. **MoldedProductionYieldCard**

-   ✅ Recebe `produced` e `scrap` do `summary`
-   ✅ Atualiza via `fetchSummary()` que respeita período

### 10. **Cargas por Silo**

-   ✅ Fonte: `silos` (endpoint `/inventory/silos/load`)
-   ✅ Atualiza via `fetchSiloLoads()` que respeita período

### 11. **RawMaterialStockTable**

-   ✅ Recebe `period` via props
-   ✅ Recebe `data` do parent (atualiza via `fetchRawMaterialStock()`)
-   ✅ Endpoint: `/inventory/raw-material-stock`

### 12. **Tabela de Estoque de Blocos**

-   ✅ Fonte: `blockStock` (endpoint `/inventory/block-stock`)
-   ✅ Atualiza via `fetchBlockStock()` que respeita período

### 13. **Tabela de Estoque de Moldados**

-   ✅ Fonte: `moldedStock` (endpoint `/inventory/molded-stock`)
-   ✅ Atualiza via `fetchMoldedStock()` que respeita período

---

## ✅ ENDPOINTS BACKEND - STATUS E CAMPOS DE DATA

### 1. **`/inventory/summary`** ✅ CORRIGIDO

**Campos de data usados:**

-   ✅ `inventory_movements.occurred_at` - Para MP in/out, blocos, moldados
-   ✅ `block_productions.started_at` - Para MP virgem/reciclada (CORRIGIDO de `created_at`)
-   ✅ `block_productions.started_at` - Para refugos kg (CORRIGIDO de `created_at`)
-   ✅ `molded_productions.started_at` - Para scraps e ranking (CORRIGIDO de `created_at`)
-   ✅ `molded_productions.started_at` - Para MP virgem moldados (CORRIGIDO de `created_at`)

**Correções aplicadas:**

```php
// ANTES (ERRADO):
$virginMpKgForBlocks = ... ->where('block_productions.created_at', '>=', $from)
$recycledMpKgForBlocks = ... ->where('block_productions.created_at', '>=', $from)
$virginMpKgForMolded = ... ->where('molded_productions.created_at', '>=', $from)
$blockLossKg = ... ->where('block_productions.created_at', '>=', $from)

// DEPOIS (CORRETO):
$virginMpKgForBlocks = ... ->where('block_productions.started_at', '>=', $from)
$recycledMpKgForBlocks = ... ->where('block_productions.started_at', '>=', $from)
$virginMpKgForMolded = ... ->where('molded_productions.started_at', '>=', $from)
$blockLossKg = ... ->where('block_productions.started_at', '>=', $from)
```

### 2. **`/inventory/raw-material-stock`** ✅

-   ✅ `inventory_movements.occurred_at`

### 3. **`/inventory/production/kg-by-day`** ✅

-   ✅ `production_pointings.started_at`

### 4. **`/inventory/production/kg-by-material-type`** ✅

-   ✅ `production_pointings.started_at`

### 5. **`/inventory/blocks/produced-by-day`** ✅

-   ✅ `inventory_movements.occurred_at`

### 6. **`/inventory/molded/production-and-scrap-by-day`** ✅

-   ✅ `molded_productions.started_at` (produção e scraps via JOIN)

### 7. **`/inventory/block/production-by-type-and-dimensions`** ✅

-   ✅ `inventory_movements.occurred_at`

### 8. **`/inventory/silos/load`** ✅

-   ✅ `inventory_movements.occurred_at`

### 9. **`/inventory/block-stock`** ✅

-   ✅ `inventory_movements.occurred_at`

### 10. **`/inventory/molded-stock`** ✅

-   ✅ `inventory_movements.occurred_at`

---

## 📊 CAMPOS DE DATA POR TABELA

### `inventory_movements`

-   **✅ `occurred_at`** - Data/hora em que o movimento ocorreu (PRINCIPAL)
-   `created_at` - Data de criação do registro
-   `updated_at` - Data de atualização do registro

### `block_productions`

-   **✅ `started_at`** - Data/hora de início da produção (PRINCIPAL)
-   `ended_at` - Data/hora de término da produção
-   `created_at` - Data de criação do registro
-   `updated_at` - Data de atualização do registro

### `molded_productions`

-   **✅ `started_at`** - Data/hora de início da produção (PRINCIPAL)
-   `ended_at` - Data/hora de término da produção
-   `created_at` - Data de criação do registro
-   `updated_at` - Data de atualização do registro

### `production_pointings`

-   **✅ `started_at`** - Data/hora de início do apontamento (PRINCIPAL)
-   `created_at` - Data de criação do registro
-   `updated_at` - Data de atualização do registro

### `molded_production_scraps`

-   ⚠️ Não tem campo de data próprio
-   ✅ Usa JOIN com `molded_productions.started_at` via `molded_production_id`

---

## 🎯 RESUMO DAS CORREÇÕES

### Problemas Encontrados e Corrigidos:

1. ✅ **`summary()` - virginMpKgForBlocks**: Trocado `created_at` → `started_at`
2. ✅ **`summary()` - recycledMpKgForBlocks**: Trocado `created_at` → `started_at`
3. ✅ **`summary()` - virginMpKgForMolded**: Trocado `created_at` → `started_at`
4. ✅ **`summary()` - blockLossKg**: Trocado `created_at` → `started_at`
5. ✅ **`summary()` - moldedLossUnits**: Adicionado JOIN para usar `started_at`
6. ✅ **`summary()` - moldedLossRanking**: Adicionado JOIN para usar `started_at`

### Rationale:

-   **`created_at`**: Data em que o registro foi inserido no banco (pode ser diferente da produção real)
-   **`started_at`**: Data real em que a produção/movimento começou (data correta para filtros)
-   **`occurred_at`**: Data em que o movimento de estoque ocorreu (equivalente a `started_at` para movements)

---

## ✅ CONCLUSÃO

**Status:** ✅ **TODOS OS COMPONENTES E ENDPOINTS AUDITADOS E CORRIGIDOS**

Todos os componentes do dashboard agora respeitam corretamente o filtro de período selecionado pelo usuário. As datas utilizadas são consistentes e refletem o momento real das operações (produção, movimentações, etc.), não apenas a data de criação dos registros no banco de dados.

### Testes Recomendados:

1. ✅ Selecionar diferentes períodos e verificar se todos os cards atualizam
2. ✅ Verificar se gráficos refletem apenas dados do período selecionado
3. ✅ Confirmar que tabelas mostram saldos corretos para o período
4. ✅ Testar com períodos extremos (1 dia, 1 ano)
5. ✅ Validar cálculos de totais e rankings

---

**Última Atualização:** 30/10/2025  
**Responsável:** AI Assistant  
**Status:** ✅ Completo e Auditado
