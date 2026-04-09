<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMoldedDispatchRequest;
use App\Http\Requests\UpdateMoldedDispatchRequest;
use App\Models\InventoryMovement;
use App\Models\MoldedDispatch;
use App\Models\MoldType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MoldedDispatchController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('viewAny', MoldedDispatch::class);

        $query = MoldedDispatch::query()->with(['moldType:id,name']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('manufacturing_order_number', 'like', '%' . $search . '%');
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($moldTypeId = request()->integer('mold_type_id')) {
            $query->where('mold_type_id', $moldTypeId);
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
            ->through(function (MoldedDispatch $dispatch): array {
                return [
                    'id' => $dispatch->id,
                    'dispatched_at' => $dispatch->dispatched_at?->format('d/m/Y H:i'),
                    'manufacturing_order_number' => $dispatch->manufacturing_order_number,
                    'mold_type_id' => $dispatch->mold_type_id,
                    'mold_type_name' => $dispatch->moldType?->name,
                    'quantity' => (int) $dispatch->quantity,
                ];
            });

        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $dispatches->lastPage() && $dispatches->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $dispatches->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/MoldedDispatches/Index', [
            'dispatches' => $dispatches,
            'filters' => request()->only(['search', 'mold_type_id', 'period']),
            'moldTypes' => MoldType::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', MoldedDispatch::class);

        return Inertia::render('Admin/MoldedDispatches/Create', [
            'moldTypes' => MoldType::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMoldedDispatchRequest $request): RedirectResponse
    {
        $this->authorize('create', MoldedDispatch::class);

        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $dispatch = MoldedDispatch::query()->create([
                'dispatched_at' => $data['dispatched_at'],
                'manufacturing_order_number' => $data['manufacturing_order_number'],
                'mold_type_id' => (int) $data['mold_type_id'],
                'quantity' => (int) $data['quantity'],
                'created_by_id' => Auth::id(),
                'updated_by_id' => Auth::id(),
            ]);

            app(\App\Services\Inventory\InventoryService::class)->syncMoldedDispatch($dispatch);
        });

        return redirect()
            ->route('molded-dispatches.index')
            ->with('status', 'Saída de moldados registrada com sucesso!');
    }

    public function edit(MoldedDispatch $moldedDispatch): InertiaResponse
    {
        $this->authorize('update', $moldedDispatch);

        return Inertia::render('Admin/MoldedDispatches/Edit', [
            'moldedDispatch' => [
                'id' => $moldedDispatch->id,
                'dispatched_at' => $moldedDispatch->dispatched_at?->format('Y-m-d H:i:s'),
                'manufacturing_order_number' => $moldedDispatch->manufacturing_order_number,
                'mold_type_id' => (int) $moldedDispatch->mold_type_id,
                'quantity' => (int) $moldedDispatch->quantity,
            ],
            'moldTypes' => MoldType::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateMoldedDispatchRequest $request, MoldedDispatch $moldedDispatch): RedirectResponse
    {
        $this->authorize('update', $moldedDispatch);

        $data = $request->validated();

        DB::transaction(function () use ($moldedDispatch, $data) {
            $moldedDispatch->update([
                'dispatched_at' => $data['dispatched_at'],
                'manufacturing_order_number' => $data['manufacturing_order_number'],
                'mold_type_id' => (int) $data['mold_type_id'],
                'quantity' => (int) $data['quantity'],
                'updated_by_id' => Auth::id(),
            ]);

            app(\App\Services\Inventory\InventoryService::class)->syncMoldedDispatch($moldedDispatch);
        });

        return redirect()
            ->route('molded-dispatches.index')
            ->with('status', 'Saída de moldados atualizada com sucesso!');
    }

    public function destroy(MoldedDispatch $moldedDispatch): RedirectResponse
    {
        try {
            $this->authorize('delete', $moldedDispatch);

            DB::transaction(function () use ($moldedDispatch) {
                InventoryMovement::query()
                    ->where('reference_type', MoldedDispatch::class)
                    ->where('reference_id', $moldedDispatch->id)
                    ->delete();

                $moldedDispatch->delete();
            });

            return redirect()
                ->route('molded-dispatches.index')
                ->with('status', 'Saída de moldados removida com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir saída de moldados', [
                'molded_dispatch_id' => $moldedDispatch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir a saída de moldados.');
        }
    }

    public function modal(MoldedDispatch $moldedDispatch): JsonResponse
    {
        $this->authorize('view', $moldedDispatch);

        $moldedDispatch->load(['moldType:id,name', 'createdBy:id,name', 'updatedBy:id,name']);

        return response()->json([
            'moldedDispatch' => [
                'id' => $moldedDispatch->id,
                'dispatched_at' => $moldedDispatch->dispatched_at?->toISOString(),
                'manufacturing_order_number' => $moldedDispatch->manufacturing_order_number,
                'mold_type_id' => (int) $moldedDispatch->mold_type_id,
                'mold_type_name' => $moldedDispatch->moldType?->name,
                'quantity' => (int) $moldedDispatch->quantity,
                'created_at' => $moldedDispatch->created_at?->toISOString(),
                'updated_at' => $moldedDispatch->updated_at?->toISOString(),
                'created_by' => $moldedDispatch->createdBy?->name,
                'updated_by' => $moldedDispatch->updatedBy?->name,
            ],
        ]);
    }
}

