<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionPointingRequest;
use App\Http\Requests\UpdateProductionPointingRequest;
use App\Models\Operator;
use App\Models\BlockType;
use App\Models\MoldType;
use App\Models\ProductionPointing;
use App\Models\RawMaterial;
use App\Models\Silo;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProductionPointingController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', ProductionPointing::class);

        $query = ProductionPointing::query()->with(['rawMaterial'])->withCount(['operators', 'silos']);

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($period = request('period')) {
            $from = $period['from'] ?? null;
            $to = $period['to'] ?? null;
            $query->between($from, $to);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $productionPointings = $query
            ->orderBy('started_at', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (ProductionPointing $pointing): array {
                return [
                    'id' => $pointing->id,
                    'status' => $pointing->status,
                    'sheet_number' => $pointing->sheet_number,
                    'sheet_label' => $pointing->sheet_number ? sprintf('Ficha #%s', $pointing->sheet_number) : 'Sem ficha',
                    'started_at' => $pointing->started_at?->format('d/m/Y H:i'),
                    'ended_at' => $pointing->ended_at?->format('d/m/Y H:i'),
                    'raw_material' => $pointing->rawMaterial?->name,
                    'quantity' => $pointing->quantity !== null ? (float) $pointing->quantity : null,
                    'operators_count' => $pointing->operators_count,
                    'silos_count' => $pointing->silos_count,
                ];
            });

        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $productionPointings->lastPage() && $productionPointings->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $productionPointings->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/ProductionPointings/Index', [
            'productionPointings' => $productionPointings,
            'filters' => request()->only(['search', 'status', 'period']),
            'blockTypes' => BlockType::query()->active()->orderBy('name')->get(['id', 'name', 'raw_material_percentage']),
            'moldTypes' => MoldType::query()->orderBy('name')->get(['id', 'name']),
            'operators' => Operator::query()->orderBy('name')->get(['id', 'name']),
            'silos' => Silo::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', ProductionPointing::class);

        return Inertia::render('Admin/ProductionPointings/Create', [
            'rawMaterials' => RawMaterial::query()->orderBy('name')->get(['id', 'name']),
            'operators' => Operator::query()->orderBy('name')->get(['id', 'name']),
            'silos' => Silo::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductionPointingRequest $request): RedirectResponse
    {
        $this->authorize('create', ProductionPointing::class);

        $data = $request->validated();
        $productionPointing = ProductionPointing::create([
            'status' => $data['status'],
            'sheet_number' => (int) $data['sheet_number'],
            'started_at' => $data['started_at'],
            'ended_at' => $data['ended_at'],
            'raw_material_id' => $data['raw_material_id'],
            'quantity' => $data['quantity'],
            'created_by' => Auth::id(),
        ]);

        $productionPointing->operators()->sync($data['operator_ids']);
        $productionPointing->silos()->sync($data['silo_ids']);

        // Criar/atualizar reserva de matéria-prima
        app(\App\Services\Inventory\InventoryService::class)->reserveForProductionPointing($productionPointing);

        return redirect()->route('production-pointings.index')->with('status', 'Apontamento de produção criado com sucesso!');
    }

    public function edit(ProductionPointing $productionPointing): InertiaResponse
    {
        $this->authorize('update', $productionPointing);

        $productionPointing->load(['operators:id,name', 'silos:id,name']);

        return Inertia::render('Admin/ProductionPointings/Edit', [
            'productionPointing' => [
                'id' => $productionPointing->id,
                'status' => $productionPointing->status,
                'sheet_number' => $productionPointing->sheet_number,
                'raw_material_id' => $productionPointing->raw_material_id,
                'quantity' => $productionPointing->quantity,
                'started_at' => $productionPointing->started_at?->toIso8601String(),
                'ended_at' => $productionPointing->ended_at?->toIso8601String(),
                'operators' => $productionPointing->operators->map(fn ($operator) => [
                    'id' => $operator->id,
                    'name' => $operator->name,
                ])->values(),
                'silos' => $productionPointing->silos->map(fn ($silo) => [
                    'id' => $silo->id,
                    'name' => $silo->name,
                ])->values(),
            ],
            'rawMaterials' => RawMaterial::query()->orderBy('name')->get(['id', 'name']),
            'operators' => Operator::query()->orderBy('name')->get(['id', 'name']),
            'silos' => Silo::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductionPointingRequest $request, ProductionPointing $productionPointing): RedirectResponse
    {
        $this->authorize('update', $productionPointing);

        $data = $request->validated();
        $productionPointing->update([
            'status' => $data['status'],
            'sheet_number' => (int) $data['sheet_number'],
            'started_at' => $data['started_at'],
            'ended_at' => $data['ended_at'],
            'raw_material_id' => $data['raw_material_id'],
            'quantity' => $data['quantity'],
            'updated_by' => Auth::id(),
        ]);

        $productionPointing->operators()->sync($data['operator_ids']);
        $productionPointing->silos()->sync($data['silo_ids']);

        // Atualizar reserva de matéria-prima conforme mudanças
        app(\App\Services\Inventory\InventoryService::class)->reserveForProductionPointing($productionPointing);

        return redirect()->route('production-pointings.index')->with('status', 'Apontamento de produção atualizado com sucesso!');
    }

    public function destroy(ProductionPointing $productionPointing): RedirectResponse
    {
        try {
            $this->authorize('delete', $productionPointing);
            $productionPointing->delete();
            return redirect()->route('production-pointings.index')->with('status', 'Apontamento de produção removido com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir apontamento de produção', [
                'production_pointing_id' => $productionPointing->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir o apontamento de produção.');
        }
    }

    public function modal(ProductionPointing $productionPointing): JsonResponse
    {
        $this->authorize('view', $productionPointing);
        $productionPointing->load([
            'createdBy',
            'updatedBy',
            'rawMaterial:id,name',
            'operators:id,name',
            'silos:id,name',
        ]);

        return response()->json([
            'productionPointing' => [
                'id' => $productionPointing->id,
                'status' => $productionPointing->status,
                'sheet_number' => $productionPointing->sheet_number !== null ? (int) $productionPointing->sheet_number : null,
                'sheet_label' => $productionPointing->sheet_number ? sprintf('Ficha #%s', $productionPointing->sheet_number) : 'Sem ficha',
                'started_at' => $productionPointing->started_at?->format('Y-m-d H:i:s'),
                'ended_at' => $productionPointing->ended_at?->format('Y-m-d H:i:s'),
                'quantity' => $productionPointing->quantity !== null ? (float) $productionPointing->quantity : null,
                'raw_material' => $productionPointing->rawMaterial?->name,
                'operators' => $productionPointing->operators->map(fn ($operator) => [
                    'id' => $operator->id,
                    'name' => $operator->name,
                ])->values(),
                'silos' => $productionPointing->silos->map(fn ($silo) => [
                    'id' => $silo->id,
                    'name' => $silo->name,
                ])->values(),
                'created_at' => $productionPointing->created_at?->format('d/m/Y H:i'),
                'updated_at' => $productionPointing->updated_at?->format('d/m/Y H:i'),
                'created_by' => $productionPointing->createdBy?->name,
                'updated_by' => $productionPointing->updatedBy?->name,
            ],
        ]);
    }
}
