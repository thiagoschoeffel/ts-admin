<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\RawMaterial;
use App\Models\Silo;
use App\Models\Almoxarifado;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // Estoque atual de matéria-prima por tipo
    public function rawMaterialStock(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        // Busca todos os tipos de matéria-prima
        $rawMaterials = RawMaterial::query()->get();

        $result = $rawMaterials->map(function ($raw) use ($from, $to) {
            // Inicial: saldo antes do período
            $initial = $from ? InventoryMovement::query()
                ->where('item_type', 'raw_material')
                ->where('item_id', $raw->id)
                ->where('occurred_at', '<', $from)
                ->selectRaw('SUM(CASE WHEN direction = \'in\' THEN quantity WHEN direction = \'out\' THEN -quantity WHEN direction = \'adjust\' THEN quantity ELSE 0 END) as saldo')
                ->value('saldo') ?? 0 : 0;

            // Entradas no período
            $input = InventoryMovement::query()
                ->where('item_type', 'raw_material')
                ->where('item_id', $raw->id)
                ->when($from, fn($q) => $q->where('occurred_at', '>=', $from))
                ->when($to, fn($q) => $q->where('occurred_at', '<=', $to))
                ->where('direction', 'in')
                ->sum('quantity') ?? 0;

            // Requisições (saídas) no período
            $requested = InventoryMovement::query()
                ->where('item_type', 'raw_material')
                ->where('item_id', $raw->id)
                ->when($from, fn($q) => $q->where('occurred_at', '>=', $from))
                ->when($to, fn($q) => $q->where('occurred_at', '<=', $to))
                ->where('direction', 'out')
                ->sum('quantity') ?? 0;

            // Saldo final
            $balance = $initial + $input - $requested;

            return [
                'raw_material_id' => $raw->id,
                'raw_material_name' => $raw->name,
                'initial_kg' => (float) $initial,
                'input_kg' => (float) $input,
                'requested_kg' => (float) $requested,
                'balance_kg' => (float) $balance,
            ];
        })->filter(function ($item) {
            // Filtrar apenas matérias-primas com estoque no período
            return $item['balance_kg'] != 0;
        });

        return response()->json(['data' => $result]);
    }
    // Produção de matéria-prima por dia (gráfico de linha)
    public function productionKgByDay(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        $query = \App\Models\ProductionPointing::query()
            ->selectRaw('DATE(started_at) as day, SUM(quantity) as total_kg')
            ->whereNotNull('started_at');

        if ($from) {
            $query->where('started_at', '>=', $from);
        }
        if ($to) {
            $query->where('started_at', '<=', $to);
        }

        $result = $query->groupBy(DB::raw('DATE(started_at)'))
            ->orderBy('day')
            ->get();

        return response()->json(['data' => $result]);
    }
    // Saldo de blocos por tipo e dimensões
    public function blockStockByTypeAndDimension(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $query = InventoryMovement::query()
            ->where('item_type', 'block');

        // Filtros opcionais
        if ($blockTypeId = $request->query('block_type_id')) {
            $query->where('block_type_id', $blockTypeId);
        }
        if ($length = $request->query('length_mm')) {
            $query->where('length_mm', $length);
        }
        if ($width = $request->query('width_mm')) {
            $query->where('width_mm', $width);
        }
        if ($height = $request->query('height_mm')) {
            $query->where('height_mm', $height);
        }

        $stock = $query
            ->select(
                'block_type_id',
                'length_mm',
                'width_mm',
                'height_mm',
                DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity WHEN direction = 'out' THEN -quantity WHEN direction = 'adjust' THEN quantity ELSE 0 END) as saldo")
            )
            ->groupBy('block_type_id', 'length_mm', 'width_mm', 'height_mm')
            ->get();

        return response()->json(['stock' => $stock]);
    }

    // Produção de matéria-prima por tipo (gráfico de barras)
    public function productionKgByMaterialType(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        $result = \App\Models\ProductionPointing::query()
            ->join('raw_materials', 'production_pointings.raw_material_id', '=', 'raw_materials.id')
            ->select('raw_materials.name as raw_material_name', DB::raw('SUM(production_pointings.quantity) as total_kg'))
            ->whereNotNull('raw_materials.name')
            ->when($from, fn($q) => $q->where('production_pointings.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('production_pointings.started_at', '<=', $to))
            ->groupBy('raw_materials.name')
            ->orderByDesc('total_kg')
            ->get();

        return response()->json(['data' => $result]);
    }

    // Produção de blocos por dia (gráfico de linha)
    public function blocksProducedByDay(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        $query = InventoryMovement::query()
            ->select(DB::raw('DATE(occurred_at) as day'), DB::raw('SUM(quantity) as total_units'))
            ->where('item_type', 'block')
            ->where('direction', 'in')
            ->when($from, fn($q) => $q->where('occurred_at', '>=', $from))
            ->when($to, fn($q) => $q->where('occurred_at', '<=', $to))
            ->groupBy(DB::raw('DATE(occurred_at)'))
            ->orderBy('day')
            ->get();

        return response()->json(['data' => $query]);
    }

    // Produção de moldados e refugos por dia (gráfico de área)
    public function moldedProductionAndScrapByDay(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        // Produção de moldados por dia
        $productionQuery = \App\Models\MoldedProduction::query()
            ->selectRaw('DATE(started_at) as day, SUM(quantity) as total_produced')
            ->whereNotNull('started_at')
            ->when($from, fn($q) => $q->where('started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('started_at', '<=', $to))
            ->groupBy(DB::raw('DATE(started_at)'));

        // Refugos de moldados por dia
        $scrapQuery = DB::table('molded_production_scraps')
            ->join('molded_productions', 'molded_production_scraps.molded_production_id', '=', 'molded_productions.id')
            ->selectRaw('DATE(molded_productions.started_at) as day, SUM(molded_production_scraps.quantity) as total_scrap')
            ->whereNotNull('molded_productions.started_at')
            ->when($from, fn($q) => $q->where('molded_productions.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('molded_productions.started_at', '<=', $to))
            ->groupBy(DB::raw('DATE(molded_productions.started_at)'));

        $productionData = $productionQuery->get()->keyBy('day');
        $scrapData = $scrapQuery->get()->keyBy('day');

        // Combinar os dados
        $allDays = collect(array_merge($productionData->keys()->toArray(), $scrapData->keys()->toArray()))
            ->unique()
            ->sort()
            ->values();

        $result = $allDays->map(function ($day) use ($productionData, $scrapData) {
            return [
                'day' => $day,
                'total_produced' => (int) ($productionData->get($day)->total_produced ?? 0),
                'total_scrap' => (int) ($scrapData->get($day)->total_scrap ?? 0),
            ];
        });

        return response()->json(['data' => $result]);
    }

    // Produção de blocos por tipo e dimensões (tabela)
    public function blockProductionByTypeAndDimensions(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        $query = InventoryMovement::query()
            ->leftJoin('block_productions', function ($join) {
                $join->on('inventory_movements.reference_type', '=', DB::raw("'App\\\\Models\\\\BlockProduction'"))
                    ->on('inventory_movements.reference_id', '=', 'block_productions.id');
            })
            ->join('block_types', 'inventory_movements.block_type_id', '=', 'block_types.id')
            ->select([
                'block_types.name as block_type_name',
                'inventory_movements.length_mm',
                'inventory_movements.width_mm',
                'inventory_movements.height_mm',
                DB::raw('SUM(inventory_movements.quantity) as total_units'),
                DB::raw('SUM(CAST(inventory_movements.quantity AS NUMERIC) * CAST(inventory_movements.length_mm AS NUMERIC) * CAST(inventory_movements.width_mm AS NUMERIC) * CAST(inventory_movements.height_mm AS NUMERIC) / 1000000000) as total_m3'),
                DB::raw('SUM(COALESCE(block_productions.weight, CAST(inventory_movements.quantity AS NUMERIC) * CAST(inventory_movements.length_mm AS NUMERIC) * CAST(inventory_movements.width_mm AS NUMERIC) * CAST(inventory_movements.height_mm AS NUMERIC) * 2000 / 1000000000) * (block_types.raw_material_percentage / 100)) as virgin_mp_kg'),
                DB::raw('SUM(COALESCE(block_productions.weight, CAST(inventory_movements.quantity AS NUMERIC) * CAST(inventory_movements.length_mm AS NUMERIC) * CAST(inventory_movements.width_mm AS NUMERIC) * CAST(inventory_movements.height_mm AS NUMERIC) * 2000 / 1000000000) * ((100 - block_types.raw_material_percentage) / 100)) as recycled_mp_kg'),
            ])
            ->where('inventory_movements.item_type', 'block')
            ->where('inventory_movements.direction', 'in')
            ->whereNotNull('inventory_movements.length_mm')
            ->whereNotNull('inventory_movements.width_mm')
            ->whereNotNull('inventory_movements.height_mm')
            ->when($from, fn($q) => $q->where('inventory_movements.occurred_at', '>=', $from))
            ->when($to, fn($q) => $q->where('inventory_movements.occurred_at', '<=', $to))
            ->groupBy([
                'block_types.name',
                'inventory_movements.length_mm',
                'inventory_movements.width_mm',
                'inventory_movements.height_mm',
                'block_types.raw_material_percentage'
            ])
            ->orderBy('block_types.name')
            ->orderBy('inventory_movements.length_mm')
            ->orderBy('inventory_movements.width_mm')
            ->orderBy('inventory_movements.height_mm')
            ->get();

        return response()->json(['data' => $query]);
    }

    public function modal(InventoryMovement $movement): JsonResponse
    {
        $this->authorize('view', $movement);

        $movement->load([
            'rawMaterial',
            'silo',
            'blockType',
            'almoxarifado',
            'moldType',
            'createdBy',
            'updatedBy'
        ]);

        // Carregar movimento de consumo relacionado se existir
        $relatedConsumption = null;
        if ($movement->item_type === 'block' || $movement->item_type === 'molded') {
            $relatedConsumption = InventoryMovement::query()
                ->where('reference_type', 'inventory_movement')
                ->where('reference_id', $movement->id)
                ->where('direction', 'out')
                ->with(['rawMaterial', 'createdBy'])
                ->first();
        }

        return response()->json([
            'movement' => [
                'id' => $movement->id,
                'occurred_at' => $movement->occurred_at?->format('d/m/Y H:i'),
                'item_type' => $movement->item_type,
                'item_type_formatted' => match ($movement->item_type) {
                    'raw_material' => 'Matéria-prima',
                    'block' => 'Bloco',
                    'molded' => 'Moldado',
                    default => $movement->item_type
                },
                'direction' => $movement->direction,
                'direction_formatted' => match ($movement->direction) {
                    'in' => 'Entrada',
                    'out' => 'Saída',
                    'adjust' => 'Ajuste',
                    default => $movement->direction
                },
                'quantity' => $movement->quantity,
                'unit' => $movement->unit,
                'location_type' => $movement->location_type,
                'location_type_formatted' => match ($movement->location_type) {
                    'silo' => 'Silo',
                    'almoxarifado' => 'Almoxarifado',
                    'none' => 'Nenhum',
                    default => $movement->location_type
                },
                'location_name' => match ($movement->location_type) {
                    'silo' => $movement->silo?->name,
                    'almoxarifado' => $movement->almoxarifado?->name,
                    default => null
                },
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'reference_formatted' => $movement->reference_type ?
                    (\Illuminate\Support\Str::of($movement->reference_type)->replace('App\\Models\\', '')->replace('\\', '') . '#' . $movement->reference_id) :
                    null,
                'notes' => $movement->notes,
                'created_at' => $movement->created_at?->format('d/m/Y H:i'),
                'updated_at' => $movement->updated_at?->format('d/m/Y H:i'),
                'created_by' => $movement->createdBy?->name,
                'updated_by' => $movement->updatedBy?->name,

                // Dados específicos do item
                'raw_material' => $movement->rawMaterial ? [
                    'id' => $movement->rawMaterial->id,
                    'name' => $movement->rawMaterial->name,
                ] : null,
                'block_type' => $movement->blockType ? [
                    'id' => $movement->blockType->id,
                    'name' => $movement->blockType->name,
                ] : null,
                'mold_type' => $movement->moldType ? [
                    'id' => $movement->moldType->id,
                    'name' => $movement->moldType->name,
                ] : null,
                'dimensions' => $movement->length_mm ? [
                    'length_mm' => $movement->length_mm,
                    'width_mm' => $movement->width_mm,
                    'height_mm' => $movement->height_mm,
                ] : null,

                // Movimento de consumo relacionado
                'related_consumption' => $relatedConsumption ? [
                    'id' => $relatedConsumption->id,
                    'raw_material' => $relatedConsumption->rawMaterial ? [
                        'id' => $relatedConsumption->rawMaterial->id,
                        'name' => $relatedConsumption->rawMaterial->name,
                    ] : null,
                    'quantity' => $relatedConsumption->quantity,
                    'unit' => $relatedConsumption->unit,
                    'created_by' => $relatedConsumption->createdBy?->name,
                    'created_at' => $relatedConsumption->created_at?->format('d/m/Y H:i'),
                ] : null,
            ],
        ]);
    }

    // Página de dashboard: resumo + cargas de silos
    public function dashboard(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        // Reaproveita as consultas do summary()
        $from = $request->query('from');
        $to = $request->query('to');

        $summary = $this->summary($request)->getData(true);

        // Cargas de silos
        $loads = $this->siloLoads($request)->getData(true)['data'] ?? [];

        return Inertia::render('Admin/Inventory/Dashboard', [
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => $summary,
            'siloLoads' => $loads,
        ]);
    }

    // Página de movimentos: listagem
    public function movementsPage(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);
        $raws = RawMaterial::query()->orderBy('name')->get(['id', 'name']);
        $silos = Silo::query()->orderBy('name')->get(['id', 'name']);

        // Filtros (mesmos da API)
        $query = InventoryMovement::query();
        if ($item = $request->query('item_type')) {
            $query->where('item_type', $item);
        }
        if ($direction = $request->query('direction')) {
            $query->where('direction', $direction);
        }
        if ($from = $request->query('from')) {
            $query->where('occurred_at', '>=', Carbon::parse($from));
        }
        if ($to = $request->query('to')) {
            $query->where('occurred_at', '<=', Carbon::parse($to));
        }
        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 25;
        $paginator = $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Inventory/Index', [
            'rawMaterials' => $raws,
            'silos' => $silos,
            'paginator' => $paginator,
            'filters' => $request->only(['item_type', 'direction', 'from', 'to', 'per_page', 'page']),
        ]);
    }

    // Página: criar movimento manual
    public function createMovement(): InertiaResponse
    {
        $raws = RawMaterial::query()->orderBy('name')->get(['id', 'name']);
        $silos = Silo::query()->orderBy('name')->get(['id', 'name']);
        $blockTypes = \App\Models\BlockType::query()->orderBy('name')->get(['id', 'name']);
        $almoxarifados = Almoxarifado::query()->active()->orderBy('name')->get(['id', 'name']);
        $moldTypes = \App\Models\MoldType::query()->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Inventory/Create', [
            'rawMaterials' => $raws,
            'silos' => $silos,
            'blockTypes' => $blockTypes,
            'almoxarifados' => $almoxarifados,
            'moldTypes' => $moldTypes,
        ]);
    }

    // Página: editar movimento manual
    public function editMovement(InventoryMovement $movement): InertiaResponse
    {
        $raws = RawMaterial::query()->orderBy('name')->get(['id', 'name']);
        $silos = Silo::query()->orderBy('name')->get(['id', 'name']);
        $blockTypes = \App\Models\BlockType::query()->orderBy('name')->get(['id', 'name']);
        $almoxarifados = Almoxarifado::query()->active()->orderBy('name')->get(['id', 'name']);
        $moldTypes = \App\Models\MoldType::query()->active()->orderBy('name')->get(['id', 'name']);

        $movement->load(['rawMaterial', 'silo', 'blockType', 'almoxarifado', 'moldType']);

        // Carregar movimento de consumo relacionado se existir
        $relatedConsumption = null;
        if ($movement->item_type === 'block' || $movement->item_type === 'molded') {
            $relatedConsumption = InventoryMovement::query()
                ->where('reference_type', 'inventory_movement')
                ->where('reference_id', $movement->id)
                ->where('direction', 'out')
                ->first();
        }
        if ($movement->item_type === 'block' && !$movement->block_type_id && $movement->item_id) {
            $blockProduction = \App\Models\BlockProduction::find($movement->item_id);
            if ($blockProduction) {
                $movement->block_type_id = $blockProduction->block_type_id;
                $movement->length_mm = $blockProduction->length_mm;
                $movement->width_mm = $blockProduction->width_mm;
                $movement->height_mm = $blockProduction->height_mm;
            }
        }

        $movementData = $movement->toArray();
        $movementData['occurred_at'] = $movement->occurred_at ? $movement->occurred_at->format('Y-m-d H:i') : null;

        return Inertia::render('Admin/Inventory/Edit', [
            'movement' => $movementData,
            'rawMaterials' => $raws,
            'silos' => $silos,
            'blockTypes' => $blockTypes,
            'almoxarifados' => $almoxarifados,
            'moldTypes' => $moldTypes,
            'relatedConsumption' => $relatedConsumption,
        ]);
    }

    // Criar movimento manual
    public function storeMovement(Request $request): JsonResponse
    {
        $this->authorize('create', InventoryMovement::class);
        $data = $request->validate([
            'occurred_at' => ['nullable', 'date'],
            'item_type' => ['required', 'in:raw_material,block,molded'],
            'raw_material_id' => ['nullable', 'exists:raw_materials,id'],
            'mold_type_id' => ['nullable', 'exists:mold_types,id'],
            'block_type_id' => ['nullable', 'exists:block_types,id'],
            'length_mm' => ['nullable', 'integer', 'min:0'],
            'width_mm' => ['nullable', 'integer', 'min:0'],
            'height_mm' => ['nullable', 'integer', 'min:0'],
            'consumed_raw_material_id' => ['nullable', 'exists:raw_materials,id'],
            'consumed_quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'direction' => ['required', 'in:in,adjust,out'],
            'quantity' => ['required', 'numeric', $request->direction === 'adjust' ? null : 'min:0'],
            'location_type' => ['required', 'in:silo,almoxarifado,none'],
            'location_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Basic guard: when location != none, location_id must be provided
        if ($data['location_type'] !== 'none' && empty($data['location_id'])) {
            return response()->json(['message' => 'location_id é obrigatório para o tipo informado.'], 422);
        }

        $fields = [
            'occurred_at' => $data['occurred_at'] ?? Carbon::now(),
            'item_type' => $data['item_type'],
            'direction' => $data['direction'],
            'quantity' => (float) $data['quantity'],
            'location_type' => $data['location_type'],
            'location_id' => $data['location_id'] ?? null,
            'unit' => $data['item_type'] === 'raw_material' ? 'kg' : 'unidade',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ];

        if ($data['item_type'] === 'raw_material') {
            $fields['item_id'] = (int) $data['raw_material_id'];
            $fields['block_type_id'] = null;
            $fields['length_mm'] = null;
            $fields['width_mm'] = null;
            $fields['height_mm'] = null;
        } elseif ($data['item_type'] === 'molded') {
            $fields['item_id'] = (int) $data['mold_type_id'];
            $fields['block_type_id'] = null;
            $fields['length_mm'] = null;
            $fields['width_mm'] = null;
            $fields['height_mm'] = null;
        } elseif ($data['item_type'] === 'block') {
            $fields['item_id'] = null;
            $fields['block_type_id'] = $data['block_type_id'] ?? null;
            $fields['length_mm'] = $data['length_mm'] ?? null;
            $fields['width_mm'] = $data['width_mm'] ?? null;
            $fields['height_mm'] = $data['height_mm'] ?? null;
        }

        $move = InventoryMovement::query()->create($fields);

        // Se for bloco ou moldado e foi especificada matéria-prima consumida, criar movimento de saída
        if (($data['item_type'] === 'block' || $data['item_type'] === 'molded') && !empty($data['consumed_raw_material_id']) && !empty($data['consumed_quantity_kg'])) {
            InventoryMovement::query()->create([
                'occurred_at' => $fields['occurred_at'],
                'item_type' => 'raw_material',
                'item_id' => (int) $data['consumed_raw_material_id'],
                'direction' => 'out',
                'quantity' => (float) $data['consumed_quantity_kg'],
                'location_type' => 'none', // Consumo não especifica localização
                'location_id' => null,
                'unit' => 'kg',
                'reference_type' => 'inventory_movement',
                'reference_id' => $move->id,
                'notes' => 'Consumo para produção de ' . ($data['item_type'] === 'block' ? 'bloco' : 'moldado') . ' - Movimento #' . $move->id,
                'created_by' => Auth::id(),
            ]);
        }

        return response()->json(['movement' => $move], 201);
    }

    // Atualizar movimento manual
    public function updateMovement(Request $request, InventoryMovement $movement): JsonResponse
    {
        $this->authorize('update', $movement);
        $data = $request->validate([
            'occurred_at' => ['nullable', 'date'],
            'item_type' => ['required', 'in:raw_material,block,molded'],
            'raw_material_id' => ['nullable', 'exists:raw_materials,id'],
            'mold_type_id' => ['nullable', 'exists:mold_types,id'],
            'block_type_id' => ['nullable', 'exists:block_types,id'],
            'length_mm' => ['nullable', 'integer', 'min:0'],
            'width_mm' => ['nullable', 'integer', 'min:0'],
            'height_mm' => ['nullable', 'integer', 'min:0'],
            'consumed_raw_material_id' => ['nullable', 'exists:raw_materials,id'],
            'consumed_quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'direction' => ['required', 'in:in,adjust,out'],
            'quantity' => ['required', 'numeric', $request->direction === 'adjust' ? null : 'min:0'],
            'location_type' => ['required', 'in:silo,almoxarifado,none'],
            'location_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['location_type'] !== 'none' && empty($data['location_id'])) {
            return response()->json(['message' => 'location_id é obrigatório para o tipo informado.'], 422);
        }

        $fields = [
            'occurred_at' => $data['occurred_at'] ?? Carbon::now(),
            'item_type' => $data['item_type'],
            'direction' => $data['direction'],
            'quantity' => (float) $data['quantity'],
            'location_type' => $data['location_type'],
            'location_id' => $data['location_id'] ?? null,
            'unit' => $data['item_type'] === 'raw_material' ? 'kg' : 'unidade',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => Auth::id(),
        ];

        if ($data['item_type'] === 'raw_material') {
            $fields['item_id'] = (int) $data['raw_material_id'];
            $fields['block_type_id'] = null;
            $fields['length_mm'] = null;
            $fields['width_mm'] = null;
            $fields['height_mm'] = null;
        } elseif ($data['item_type'] === 'molded') {
            $fields['item_id'] = (int) $data['mold_type_id'];
            $fields['block_type_id'] = null;
            $fields['length_mm'] = null;
            $fields['width_mm'] = null;
            $fields['height_mm'] = null;
        } elseif ($data['item_type'] === 'block') {
            $fields['item_id'] = null;
            $fields['block_type_id'] = $data['block_type_id'] ?? null;
            $fields['length_mm'] = $data['length_mm'] ?? null;
            $fields['width_mm'] = $data['width_mm'] ?? null;
            $fields['height_mm'] = $data['height_mm'] ?? null;
        }

        $movement->update($fields);

        // Gerenciar movimento de consumo relacionado para blocos e moldados
        if ($data['item_type'] === 'block' || $data['item_type'] === 'molded') {
            $relatedConsumption = InventoryMovement::query()
                ->where('reference_type', 'inventory_movement')
                ->where('reference_id', $movement->id)
                ->where('direction', 'out')
                ->first();

            if (!empty($data['consumed_raw_material_id']) && !empty($data['consumed_quantity_kg'])) {
                // Criar ou atualizar movimento de consumo
                if ($relatedConsumption) {
                    $relatedConsumption->update([
                        'occurred_at' => $fields['occurred_at'],
                        'item_id' => (int) $data['consumed_raw_material_id'],
                        'quantity' => (float) $data['consumed_quantity_kg'],
                        'notes' => 'Consumo para produção de ' . ($data['item_type'] === 'block' ? 'bloco' : 'moldado') . ' - Movimento #' . $movement->id,
                    ]);
                } else {
                    InventoryMovement::query()->create([
                        'occurred_at' => $fields['occurred_at'],
                        'item_type' => 'raw_material',
                        'item_id' => (int) $data['consumed_raw_material_id'],
                        'direction' => 'out',
                        'quantity' => (float) $data['consumed_quantity_kg'],
                        'location_type' => 'none',
                        'location_id' => null,
                        'unit' => 'kg',
                        'reference_type' => 'inventory_movement',
                        'reference_id' => $movement->id,
                        'notes' => 'Consumo para produção de ' . ($data['item_type'] === 'block' ? 'bloco' : 'moldado') . ' - Movimento #' . $movement->id,
                        'created_by' => Auth::id(),
                    ]);
                }
            } elseif ($relatedConsumption) {
                // Remover movimento de consumo se não há mais consumo especificado
                $relatedConsumption->delete();
            }
        }

        return response()->json(['movement' => $movement]);
    }

    // Exclusão não permitida por razões de auditoria
    public function destroyMovement(InventoryMovement $movement): JsonResponse
    {
        // O observer InventoryMovementObserver impede a exclusão e lança uma exception
        // Este método está mantido apenas por compatibilidade, mas nunca será executado com sucesso
        return response()->json(['error' => 'Movimentos de estoque não podem ser excluídos'], 403);
    }

    // Resumo: entradas MP, produção, consumo e perdas básicas
    public function summary(Request $request): JsonResponse
    {
        // $this->authorize('viewAny', InventoryMovement::class); // Desabilitado temporariamente para testes via Tinker

        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        $dateFilter = function ($q) use ($from, $to): void {
            if ($from) {
                $q->where('occurred_at', '>=', $from);
            }
            if ($to) {
                $q->where('occurred_at', '<=', $to);
            }
        };

        $rawIn = (clone InventoryMovement::query())
            ->where('item_type', 'raw_material')->where('direction', 'in')
            ->when(true, $dateFilter)
            ->sum('quantity');

        $rawOut = (clone InventoryMovement::query())
            ->where('item_type', 'raw_material')->where('direction', 'out')
            ->when(true, $dateFilter)
            ->sum('quantity');

        $blocksInUnits = (clone InventoryMovement::query())
            ->where('item_type', 'block')->where('direction', 'in')
            ->when(true, $dateFilter)
            ->sum('quantity');

        $blocksProducedM3 = (clone InventoryMovement::query())
            ->where('item_type', 'block')->where('direction', 'in')
            ->when(true, $dateFilter)
            ->selectRaw('SUM(CAST(quantity AS NUMERIC) * CAST(length_mm AS NUMERIC) * CAST(width_mm AS NUMERIC) * CAST(height_mm AS NUMERIC) / 1000000000) as total_m3')
            ->value('total_m3') ?? 0;

        $virginMpKgForBlocks = \App\Models\BlockProduction::query()
            ->join('block_types', 'block_productions.block_type_id', '=', 'block_types.id')
            ->when($from, fn($q) => $q->where('block_productions.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('block_productions.started_at', '<=', $to))
            ->selectRaw('SUM(block_productions.weight * (block_types.raw_material_percentage / 100)) as virgin_mp')
            ->value('virgin_mp') ?? 0;

        $recycledMpKgForBlocks = \App\Models\BlockProduction::query()
            ->join('block_types', 'block_productions.block_type_id', '=', 'block_types.id')
            ->when($from, fn($q) => $q->where('block_productions.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('block_productions.started_at', '<=', $to))
            ->selectRaw('SUM(block_productions.weight * ((100 - block_types.raw_material_percentage) / 100)) as recycled_mp')
            ->value('recycled_mp') ?? 0;

        $moldedInUnits = (clone InventoryMovement::query())
            ->where('item_type', 'molded')->where('direction', 'in')
            ->when(true, $dateFilter)
            ->sum('quantity');

        $moldedLossUnits = DB::table('molded_production_scraps')
            ->join('molded_productions', 'molded_production_scraps.molded_production_id', '=', 'molded_productions.id')
            ->when($from, fn($q) => $q->where('molded_productions.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('molded_productions.started_at', '<=', $to))
            ->sum('molded_production_scraps.quantity') ?? 0;

        // Also include negative adjustments for molded items as losses
        $moldedAdjustLosses = (clone InventoryMovement::query())
            ->where('item_type', 'molded')->where('direction', 'adjust')
            ->when(true, $dateFilter)
            ->sum(DB::raw('CASE WHEN quantity < 0 THEN -quantity ELSE 0 END'));

        $moldedLossUnits += $moldedAdjustLosses;

        $virginMpKgForMolded = \App\Models\MoldedProduction::query()
            ->when($from, fn($q) => $q->where('molded_productions.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('molded_productions.started_at', '<=', $to))
            ->sum('total_weight_considered') ?? 0;

        $blockLossUnits = (clone InventoryMovement::query())
            ->where('item_type', 'block')->where('direction', 'adjust')
            ->when(true, $dateFilter)
            ->sum(DB::raw('CASE WHEN quantity < 0 THEN -quantity ELSE 0 END'));

        $blockLossKg = \App\Models\BlockProduction::query()
            ->where('is_scrap', true)
            ->when($from, fn($q) => $q->where('block_productions.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('block_productions.started_at', '<=', $to))
            ->sum('weight') ?? 0;

        $moldedLossRanking = DB::table('molded_production_scraps')
            ->join('molded_productions', 'molded_production_scraps.molded_production_id', '=', 'molded_productions.id')
            ->join('reasons', 'molded_production_scraps.reason_id', '=', 'reasons.id')
            ->select('reasons.id as code', 'reasons.name as reason', DB::raw('SUM(molded_production_scraps.quantity) as quantity'))
            ->when($from, fn($q) => $q->where('molded_productions.started_at', '>=', $from))
            ->when($to, fn($q) => $q->where('molded_productions.started_at', '<=', $to))
            ->groupBy('reasons.id', 'reasons.name')
            ->orderByDesc('quantity')
            ->limit(3)
            ->get();

        return response()->json([
            'from' => $from?->toDateTimeString(),
            'to' => $to?->toDateTimeString(),
            'raw_material_input_kg' => (float) $rawIn,
            'raw_material_consumed_kg' => (float) $rawOut,
            'blocks_produced_units' => (int) $blocksInUnits,
            'blocks_produced_m3' => (float) $blocksProducedM3,
            'virgin_mp_kg_for_blocks' => (float) $virginMpKgForBlocks,
            'recycled_mp_kg_for_blocks' => (float) $recycledMpKgForBlocks,
            'molded_produced_units' => (int) $moldedInUnits,
            'molded_loss_units' => (int) $moldedLossUnits,
            'virgin_mp_kg_for_molded' => (float) $virginMpKgForMolded,
            'block_loss_units' => (int) $blockLossUnits,
            'block_loss_kg' => (float) $blockLossKg,
            'molded_loss_ranking' => $moldedLossRanking,
        ]);
    }

    // Listagem de movimentos (JSON paginado)
    public function movements(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $query = InventoryMovement::query();
        if ($item = $request->query('item_type')) {
            $query->where('item_type', $item);
        }
        if ($direction = $request->query('direction')) {
            $query->where('direction', $direction);
        }
        if ($from = $request->query('from')) {
            $query->where('occurred_at', '>=', Carbon::parse($from));
        }
        if ($to = $request->query('to')) {
            $query->where('occurred_at', '<=', Carbon::parse($to));
        }
        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 25;
        $rows = $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate($perPage)->withQueryString();

        return response()->json($rows);
    }

    // Carga atual por silo (por matéria-prima)
    public function siloLoads(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);

        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        $silos = Silo::query()->orderBy('name')->get(['id', 'name']);
        $raws = RawMaterial::query()->orderBy('name')->get(['id', 'name']);

        $loads = [];
        foreach ($silos as $silo) {
            $entry = [
                'silo_id' => $silo->id,
                'silo_name' => $silo->name,
                'materials' => [],
            ];
            foreach ($raws as $rm) {
                // Inicial: saldo antes do período
                $initial = $from ? InventoryMovement::query()
                    ->where(['item_type' => 'raw_material', 'item_id' => $rm->id, 'location_type' => 'silo', 'location_id' => $silo->id])
                    ->where('occurred_at', '<', $from)
                    ->selectRaw('SUM(CASE WHEN direction = \'in\' THEN quantity WHEN direction = \'out\' THEN -quantity WHEN direction = \'adjust\' THEN quantity ELSE 0 END) as saldo')
                    ->value('saldo') ?? 0 : 0;

                // Entradas no período
                $in = InventoryMovement::query()
                    ->where(['item_type' => 'raw_material', 'item_id' => $rm->id, 'location_type' => 'silo', 'location_id' => $silo->id, 'direction' => 'in'])
                    ->when($from, fn($q) => $q->where('occurred_at', '>=', $from))
                    ->when($to, fn($q) => $q->where('occurred_at', '<=', $to))
                    ->sum('quantity') ?? 0;

                // Saídas no período
                $out = InventoryMovement::query()
                    ->where(['item_type' => 'raw_material', 'item_id' => $rm->id, 'location_type' => 'silo', 'location_id' => $silo->id, 'direction' => 'out'])
                    ->when($from, fn($q) => $q->where('occurred_at', '>=', $from))
                    ->when($to, fn($q) => $q->where('occurred_at', '<=', $to))
                    ->sum('quantity') ?? 0;

                // Ajustes no período
                $adjust = InventoryMovement::query()
                    ->where(['item_type' => 'raw_material', 'item_id' => $rm->id, 'location_type' => 'silo', 'location_id' => $silo->id, 'direction' => 'adjust'])
                    ->when($from, fn($q) => $q->where('occurred_at', '>=', $from))
                    ->when($to, fn($q) => $q->where('occurred_at', '<=', $to))
                    ->sum('quantity') ?? 0;

                $balance = (float) $initial + (float) $in - (float) $out + (float) $adjust;
                if (abs($balance) > 0.0001) {
                    $entry['materials'][] = [
                        'raw_material_id' => $rm->id,
                        'raw_material_name' => $rm->name,
                        'balance_kg' => $balance,
                    ];
                }
            }
            $loads[] = $entry;
        }

        return response()->json(['data' => $loads]);
    }

    // Entrada/ajuste manual de matéria-prima (ex.: compras ou abastecimento de silo)
    public function storeRawMaterialMovement(Request $request): JsonResponse
    {
        $data = $request->validate([
            'occurred_at' => ['nullable', 'date'],
            'item_type' => ['required', 'in:raw_material,block,molded'],
            'raw_material_id' => ['nullable', 'exists:raw_materials,id'],
            'mold_type_id' => ['nullable', 'exists:mold_types,id'],
            'block_type_id' => ['nullable', 'exists:block_types,id'],
            'length_mm' => ['nullable', 'integer', 'min:0'],
            'width_mm' => ['nullable', 'integer', 'min:0'],
            'height_mm' => ['nullable', 'integer', 'min:0'],
            'consumed_raw_material_id' => ['nullable', 'exists:raw_materials,id'],
            'consumed_quantity_kg' => ['nullable', 'numeric', 'min:0'],
            'direction' => ['required', 'in:in,adjust,out'],
            'quantity' => ['required', 'numeric', $request->direction === 'adjust' ? null : 'min:0'],
            'location_type' => ['required', 'in:silo,almoxarifado,none'],
            'location_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Basic guard: when location != none, location_id must be provided
        if ($data['location_type'] !== 'none' && empty($data['location_id'])) {
            return response()->json(['message' => 'location_id é obrigatório para o tipo informado.'], 422);
        }

        $fields = [
            'occurred_at' => $data['occurred_at'] ?? Carbon::now(),
            'item_type' => $data['item_type'],
            'direction' => $data['direction'],
            'quantity' => (float) $data['quantity'],
            'location_type' => $data['location_type'],
            'location_id' => $data['location_id'] ?? null,
            'unit' => $data['item_type'] === 'raw_material' ? 'kg' : 'unidade',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ];

        if ($data['item_type'] === 'raw_material') {
            $fields['item_id'] = (int) $data['raw_material_id'];
        } elseif ($data['item_type'] === 'molded') {
            $fields['item_id'] = (int) $data['mold_type_id'];
        } elseif ($data['item_type'] === 'block') {
            $fields['block_type_id'] = $data['block_type_id'] ?? null;
            $fields['length_mm'] = $data['length_mm'] ?? null;
            $fields['width_mm'] = $data['width_mm'] ?? null;
            $fields['height_mm'] = $data['height_mm'] ?? null;
        }

        $move = InventoryMovement::query()->create($fields);

        // Se for bloco ou moldado e foi especificada matéria-prima consumida, criar movimento de saída
        if (($data['item_type'] === 'block' || $data['item_type'] === 'molded') && !empty($data['consumed_raw_material_id']) && !empty($data['consumed_quantity_kg'])) {
            InventoryMovement::query()->create([
                'occurred_at' => $fields['occurred_at'],
                'item_type' => 'raw_material',
                'item_id' => (int) $data['consumed_raw_material_id'],
                'direction' => 'out',
                'quantity' => (float) $data['consumed_quantity_kg'],
                'location_type' => 'none', // Consumo não especifica localização
                'location_id' => null,
                'unit' => 'kg',
                'reference_type' => 'inventory_movement',
                'reference_id' => $move->id,
                'notes' => 'Consumo para produção de ' . ($data['item_type'] === 'block' ? 'bloco' : 'moldado') . ' - Movimento #' . $move->id,
                'created_by' => Auth::id(),
            ]);
        }

        return response()->json(['movement' => $move], 201);
    }

    // Estoque atual de blocos por tipo e altura
    public function blockStock(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        // Busca combinações únicas de block_type e height_mm
        $blockStocks = InventoryMovement::query()
            ->leftJoin('block_types', 'inventory_movements.block_type_id', '=', 'block_types.id')
            ->where('inventory_movements.item_type', 'block')
            ->whereNotNull('inventory_movements.block_type_id')
            ->whereNotNull('inventory_movements.height_mm')
            ->select('block_types.name as type', 'inventory_movements.height_mm')
            ->distinct()
            ->get();

        $result = $blockStocks->map(function ($stock) use ($from, $to) {
            $type = $stock->type;
            $height = $stock->height_mm;

            // Inicial: saldo antes do período
            $initial = $from ? InventoryMovement::query()
                ->leftJoin('block_types', 'inventory_movements.block_type_id', '=', 'block_types.id')
                ->where('inventory_movements.item_type', 'block')
                ->where('block_types.name', $type)
                ->where('inventory_movements.height_mm', $height)
                ->where('inventory_movements.occurred_at', '<', $from)
                ->selectRaw('SUM(CASE WHEN direction = \'in\' THEN quantity WHEN direction = \'out\' THEN -quantity WHEN direction = \'adjust\' THEN quantity ELSE 0 END) as saldo')
                ->value('saldo') ?? 0 : 0;

            // Entradas no período
            $input = InventoryMovement::query()
                ->leftJoin('block_types', 'inventory_movements.block_type_id', '=', 'block_types.id')
                ->where('inventory_movements.item_type', 'block')
                ->where('block_types.name', $type)
                ->where('inventory_movements.height_mm', $height)
                ->when($from, fn($q) => $q->where('inventory_movements.occurred_at', '>=', $from))
                ->when($to, fn($q) => $q->where('inventory_movements.occurred_at', '<=', $to))
                ->where('inventory_movements.direction', 'in')
                ->sum('inventory_movements.quantity') ?? 0;

            // Saídas no período
            $output = InventoryMovement::query()
                ->leftJoin('block_types', 'inventory_movements.block_type_id', '=', 'block_types.id')
                ->where('inventory_movements.item_type', 'block')
                ->where('block_types.name', $type)
                ->where('inventory_movements.height_mm', $height)
                ->when($from, fn($q) => $q->where('inventory_movements.occurred_at', '>=', $from))
                ->when($to, fn($q) => $q->where('inventory_movements.occurred_at', '<=', $to))
                ->where('inventory_movements.direction', 'out')
                ->sum('inventory_movements.quantity') ?? 0;

            // Saldo final
            $balance = $initial + $input - $output;

            // Metros cúbicos: saldo * (length * width * height / 1000000000)
            // Assumindo que length e width são constantes por tipo, pegar da primeira entrada
            $dimensions = InventoryMovement::query()
                ->leftJoin('block_types', 'inventory_movements.block_type_id', '=', 'block_types.id')
                ->where('inventory_movements.item_type', 'block')
                ->where('block_types.name', $type)
                ->where('inventory_movements.height_mm', $height)
                ->whereNotNull('inventory_movements.length_mm')
                ->whereNotNull('inventory_movements.width_mm')
                ->select('inventory_movements.length_mm', 'inventory_movements.width_mm')
                ->first();

            $cubic_meters = 0;
            if ($dimensions && $balance > 0) {
                $volume_per_unit = ($dimensions->length_mm * $dimensions->width_mm * $height) / 1000000000;
                $cubic_meters = $balance * $volume_per_unit;
            }

            return [
                'type' => $type,
                'height_mm' => (int) $height,
                'initial_units' => (float) $initial,
                'input_units' => (float) $input,
                'output_units' => (float) $output,
                'balance_units' => (float) $balance,
                'cubic_meters' => (float) $cubic_meters,
            ];
        })->filter(function ($item) {
            // Filtrar apenas itens com saldo > 0 ou movimentos
            return $item['balance_units'] != 0 || $item['initial_units'] != 0 || $item['input_units'] != 0 || $item['output_units'] != 0;
        });

        return response()->json(['data' => $result]);
    }

    // Estoque atual de moldados por tipo
    public function moldedStock(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', InventoryMovement::class);
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        // Baseado em inventory_movements para considerar entradas, saídas e saldo inicial antes do período
        $moldedTypes = InventoryMovement::query()
            ->leftJoin('mold_types', 'inventory_movements.mold_type_id', '=', 'mold_types.id')
            ->where('inventory_movements.item_type', 'molded')
            ->whereNotNull('inventory_movements.mold_type_id')
            ->select('inventory_movements.mold_type_id', 'mold_types.name as type')
            ->distinct()
            ->get();

        $result = $moldedTypes->map(function ($stock) use ($from, $to) {
            $moldTypeId = (int) $stock->mold_type_id;
            $type = $stock->type;

            // Inicial: saldo antes do período
            $initial = $from ? InventoryMovement::query()
                ->where('inventory_movements.item_type', 'molded')
                ->where('inventory_movements.mold_type_id', $moldTypeId)
                ->where('inventory_movements.occurred_at', '<', $from)
                ->selectRaw('SUM(CASE WHEN direction = \'in\' THEN quantity WHEN direction = \'out\' THEN -quantity WHEN direction = \'adjust\' THEN quantity ELSE 0 END) as saldo')
                ->value('saldo') ?? 0 : 0;

            // Entradas no período
            $input = InventoryMovement::query()
                ->where('inventory_movements.item_type', 'molded')
                ->where('inventory_movements.mold_type_id', $moldTypeId)
                ->when($from, fn($q) => $q->where('inventory_movements.occurred_at', '>=', $from))
                ->when($to, fn($q) => $q->where('inventory_movements.occurred_at', '<=', $to))
                ->where('inventory_movements.direction', 'in')
                ->sum('inventory_movements.quantity') ?? 0;

            // Saídas no período
            $output = InventoryMovement::query()
                ->where('inventory_movements.item_type', 'molded')
                ->where('inventory_movements.mold_type_id', $moldTypeId)
                ->when($from, fn($q) => $q->where('inventory_movements.occurred_at', '>=', $from))
                ->when($to, fn($q) => $q->where('inventory_movements.occurred_at', '<=', $to))
                ->where('inventory_movements.direction', 'out')
                ->sum('inventory_movements.quantity') ?? 0;

            // Saldo final (seguindo a lógica do estoque de blocos)
            $balance = (float) $initial + (float) $input - (float) $output;

            return [
                'type' => $type,
                'initial_units' => (float) $initial,
                'input_units' => (float) $input,
                'output_units' => (float) $output,
                'balance_units' => (float) $balance,
            ];
        })->filter(function ($item) {
            return $item['balance_units'] != 0 || $item['initial_units'] != 0 || $item['input_units'] != 0 || $item['output_units'] != 0;
        })->values();

        return response()->json(['data' => $result]);
    }
}
