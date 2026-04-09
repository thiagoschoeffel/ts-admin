<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlockDispatchRequest;
use App\Http\Requests\UpdateBlockDispatchRequest;
use App\Models\BlockDispatch;
use App\Models\BlockDispatchItem;
use App\Models\BlockProduction;
use App\Models\InventoryMovement;
use App\Models\ProductionPointing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BlockDispatchController extends Controller
{
    public function index(): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', BlockDispatch::class);

        $query = BlockDispatch::query()
            ->with(['productionPointing:id,sheet_number'])
            ->withCount('items');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('manufacturing_order_number', 'like', '%' . $search . '%');
                if (is_numeric($search)) {
                    $n = (int) $search;
                    $q->orWhere('id', $n)->orWhere('production_pointing_id', $n);
                }
            });
        }

        if ($ppId = request()->integer('production_pointing_id')) {
            $query->where('production_pointing_id', $ppId);
        }

        if ($period = request('period')) {
            $from = $period['from'] ?? null;
            $to = $period['to'] ?? null;
            if ($from) {
                $query->where('dispatched_at', '>=', $from);
            }
            if ($to) {
                $query->where('dispatched_at', '<=', $to);
            }
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $dispatches = $query
            ->orderBy('dispatched_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (BlockDispatch $dispatch): array {
                return [
                    'id' => $dispatch->id,
                    'dispatched_at' => $dispatch->dispatched_at?->format('d/m/Y H:i'),
                    'manufacturing_order_number' => $dispatch->manufacturing_order_number,
                    'production_pointing_id' => $dispatch->production_pointing_id,
                    'sheet_number' => $dispatch->productionPointing?->sheet_number,
                    'items_count' => (int) ($dispatch->items_count ?? 0),
                ];
            });

        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $dispatches->lastPage() && $dispatches->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $dispatches->lastPage();
            return redirect(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/BlockDispatches/Index', [
            'dispatches' => $dispatches,
            'filters' => request()->only(['search', 'production_pointing_id', 'period']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', BlockDispatch::class);

        return Inertia::render('Admin/BlockDispatches/Create');
    }

    public function availableBlocks(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BlockDispatch::class);

        $validated = $request->validate([
            'production_pointing_id' => ['required', 'integer', 'exists:production_pointings,id'],
            'block_dispatch_id' => ['nullable', 'integer', 'exists:block_dispatches,id'],
        ]);

        $ppId = (int) $validated['production_pointing_id'];
        $dispatchId = isset($validated['block_dispatch_id']) ? (int) $validated['block_dispatch_id'] : null;

        $alreadyQuery = BlockDispatchItem::query()
            ->whereIn('block_production_id', function ($q) use ($ppId) {
                $q->select('id')
                    ->from('block_productions')
                    ->where('production_pointing_id', $ppId);
            });

        if ($dispatchId) {
            $alreadyQuery->where('block_dispatch_id', '!=', $dispatchId);
        }

        $alreadyDispatchedIds = $alreadyQuery->pluck('block_production_id')->all();
        $alreadyMap = array_fill_keys($alreadyDispatchedIds, true);

        $blocks = BlockProduction::query()
            ->where('production_pointing_id', $ppId)
            ->with('blockType:id,name')
            ->orderBy('sheet_number')
            ->orderBy('id')
            ->get()
            ->map(function (BlockProduction $bp) use ($alreadyMap) {
                $already = isset($alreadyMap[$bp->id]);
                $isScrap = (bool) $bp->is_scrap;
                return [
                    'id' => $bp->id,
                    'sheet_number' => (int) $bp->sheet_number,
                    'block_type_name' => $bp->blockType?->name,
                    'started_at' => $bp->started_at?->format('Y-m-d H:i:s'),
                    'ended_at' => $bp->ended_at?->format('Y-m-d H:i:s'),
                    'weight' => $bp->weight !== null ? (float) $bp->weight : null,
                    'length_mm' => (int) $bp->length_mm,
                    'width_mm' => (int) $bp->width_mm,
                    'height_mm' => (int) $bp->height_mm,
                    'is_scrap' => $isScrap,
                    'already_dispatched' => $already,
                    'can_dispatch' => !$already && !$isScrap,
                ];
            })
            ->values();

        $pp = ProductionPointing::query()->find($ppId);

        return response()->json([
            'productionPointing' => [
                'id' => $pp?->id,
                'sheet_number' => $pp?->sheet_number !== null ? (int) $pp->sheet_number : null,
            ],
            'data' => $blocks,
        ]);
    }

    public function modal(BlockDispatch $blockDispatch): JsonResponse
    {
        $this->authorize('view', $blockDispatch);

        $blockDispatch->load([
            'productionPointing:id,sheet_number',
            'createdBy:id,name',
            'updatedBy:id,name',
            'items.blockProduction:block_productions.id,production_pointing_id,block_type_id,sheet_number,weight,length_mm,width_mm,height_mm,is_scrap',
            'items.blockProduction.blockType:id,name',
        ]);

        return response()->json([
            'blockDispatch' => [
                'id' => $blockDispatch->id,
                'dispatched_at' => $blockDispatch->dispatched_at?->format('Y-m-d H:i:s'),
                'manufacturing_order_number' => $blockDispatch->manufacturing_order_number,
                'production_pointing_id' => $blockDispatch->production_pointing_id,
                'sheet_number' => $blockDispatch->productionPointing?->sheet_number !== null ? (int) $blockDispatch->productionPointing->sheet_number : null,
                'items' => $blockDispatch->items->map(function (BlockDispatchItem $item): array {
                    $bp = $item->blockProduction;
                    return [
                        'block_production_id' => $item->block_production_id,
                        'block_type_name' => $bp?->blockType?->name,
                        'sheet_number' => $bp?->sheet_number !== null ? (int) $bp->sheet_number : null,
                        'weight' => $bp?->weight !== null ? (float) $bp->weight : null,
                        'length_mm' => $bp?->length_mm !== null ? (int) $bp->length_mm : null,
                        'width_mm' => $bp?->width_mm !== null ? (int) $bp->width_mm : null,
                        'height_mm' => $bp?->height_mm !== null ? (int) $bp->height_mm : null,
                        'is_scrap' => (bool) ($bp?->is_scrap ?? false),
                    ];
                })->values(),
                'created_at' => $blockDispatch->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $blockDispatch->updated_at?->format('Y-m-d H:i:s'),
                'created_by' => $blockDispatch->createdBy?->name,
                'updated_by' => $blockDispatch->updatedBy?->name,
            ],
        ]);
    }

    public function store(StoreBlockDispatchRequest $request): RedirectResponse
    {
        $this->authorize('create', BlockDispatch::class);

        $data = $request->validated();
        $ppId = (int) $data['production_pointing_id'];
        $blockIds = array_map('intval', $data['block_production_ids']);

        DB::transaction(function () use ($data, $ppId, $blockIds) {
            $blocks = BlockProduction::query()
                ->whereIn('id', $blockIds)
                ->where('production_pointing_id', $ppId)
                ->where('is_scrap', false)
                ->lockForUpdate()
                ->get(['id']);

            if ($blocks->count() !== count($blockIds)) {
                throw ValidationException::withMessages([
                    'block_production_ids' => 'Existem blocos inválidos, de outra requisição, ou marcados como refugo.',
                ]);
            }

            $already = BlockDispatchItem::query()
                ->whereIn('block_production_id', $blockIds)
                ->lockForUpdate()
                ->pluck('block_production_id')
                ->all();

            if (!empty($already)) {
                throw ValidationException::withMessages([
                    'block_production_ids' => 'Um ou mais blocos desta requisição já foram baixados.',
                ]);
            }

            $dispatch = BlockDispatch::query()->create([
                'dispatched_at' => $data['dispatched_at'],
                'manufacturing_order_number' => $data['manufacturing_order_number'],
                'production_pointing_id' => $ppId,
                'created_by_id' => Auth::id(),
                'updated_by_id' => Auth::id(),
            ]);

            $items = array_map(fn(int $id) => [
                'block_dispatch_id' => $dispatch->id,
                'block_production_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ], $blockIds);

            BlockDispatchItem::query()->insert($items);

            // Movimenta estoque: saída de blocos
            app(\App\Services\Inventory\InventoryService::class)->syncBlockDispatch($dispatch);
        });

        return redirect()
            ->route('block-dispatches.index')
            ->with('status', 'Saída de blocos registrada com sucesso!');
    }

    public function edit(BlockDispatch $blockDispatch): InertiaResponse
    {
        $this->authorize('update', $blockDispatch);

        $blockDispatch->load(['items:block_dispatch_id,block_production_id']);

        return Inertia::render('Admin/BlockDispatches/Edit', [
            'blockDispatch' => [
                'id' => $blockDispatch->id,
                'dispatched_at' => $blockDispatch->dispatched_at?->format('Y-m-d H:i:s'),
                'manufacturing_order_number' => $blockDispatch->manufacturing_order_number,
                'production_pointing_id' => $blockDispatch->production_pointing_id,
                'block_production_ids' => $blockDispatch->items->pluck('block_production_id')->map(fn($v) => (int) $v)->values(),
            ],
        ]);
    }

    public function update(UpdateBlockDispatchRequest $request, BlockDispatch $blockDispatch): RedirectResponse
    {
        $this->authorize('update', $blockDispatch);

        $data = $request->validated();
        $ppId = (int) $data['production_pointing_id'];
        $blockIds = array_map('intval', $data['block_production_ids']);

        DB::transaction(function () use ($blockDispatch, $data, $ppId, $blockIds) {
            $blocks = BlockProduction::query()
                ->whereIn('id', $blockIds)
                ->where('production_pointing_id', $ppId)
                ->where('is_scrap', false)
                ->lockForUpdate()
                ->get(['id']);

            if ($blocks->count() !== count($blockIds)) {
                throw ValidationException::withMessages([
                    'block_production_ids' => 'Existem blocos inválidos, de outra requisição, ou marcados como refugo.',
                ]);
            }

            $already = BlockDispatchItem::query()
                ->whereIn('block_production_id', $blockIds)
                ->where('block_dispatch_id', '!=', $blockDispatch->id)
                ->lockForUpdate()
                ->pluck('block_production_id')
                ->all();

            if (!empty($already)) {
                throw ValidationException::withMessages([
                    'block_production_ids' => 'Um ou mais blocos desta requisição já foram baixados.',
                ]);
            }

            $blockDispatch->update([
                'dispatched_at' => $data['dispatched_at'],
                'manufacturing_order_number' => $data['manufacturing_order_number'],
                'production_pointing_id' => $ppId,
                'updated_by_id' => Auth::id(),
            ]);

            BlockDispatchItem::query()
                ->where('block_dispatch_id', $blockDispatch->id)
                ->delete();

            $items = array_map(fn(int $id) => [
                'block_dispatch_id' => $blockDispatch->id,
                'block_production_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ], $blockIds);

            BlockDispatchItem::query()->insert($items);

            app(\App\Services\Inventory\InventoryService::class)->syncBlockDispatch($blockDispatch);
        });

        return redirect()
            ->route('block-dispatches.index')
            ->with('status', 'Saída de blocos atualizada com sucesso!');
    }

    public function destroy(BlockDispatch $blockDispatch): RedirectResponse
    {
        $this->authorize('delete', $blockDispatch);

        try {
            DB::transaction(function () use ($blockDispatch) {
                InventoryMovement::query()
                    ->where('reference_type', BlockDispatch::class)
                    ->where('reference_id', $blockDispatch->id)
                    ->delete();

                $blockDispatch->delete();
            });

            return redirect()
                ->route('block-dispatches.index')
                ->with('status', 'Saída de blocos removida com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir saída de blocos', [
                'block_dispatch_id' => $blockDispatch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir a saída de blocos.');
        }
    }
}
