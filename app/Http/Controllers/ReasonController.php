<?php

namespace App\Http\Controllers;

use App\Models\Reason;
use App\Models\ReasonType;
use App\Http\Requests\StoreReasonRequest;
use App\Http\Requests\UpdateReasonRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReasonController extends Controller
{
    /**
     * Retorna todos os motivos ativos para uso em selects (refugo, etc).
     */
    public function allActive(): JsonResponse
    {
        $reasons = Reason::active()->orderBy('name')->get(['id', 'name']);
        return response()->json(['data' => $reasons]);
    }

    /**
     * Retorna todos os motivos (ativos e inativos) em formato JSON.
     * Usado para carregar motivos inativos que foram usados em registros antigos.
     */
    public function all(): JsonResponse
    {
        $reasons = Reason::orderBy('name')->get(['id', 'name', 'status']);
        return response()->json(['data' => $reasons]);
    }
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Reason::class);

        $query = Reason::with('reasonType');

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($reasonTypeId = request('reason_type_id')) {
            $query->where('reason_type_id', $reasonTypeId);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $reasons = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Add reason_type_name to each reason
        $reasons->getCollection()->transform(function ($reason) {
            $reason->reason_type_name = $reason->reasonType?->name;
            return $reason;
        });

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $reasons->lastPage() && $reasons->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $reasons->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Reasons/Index', [
            'reasons' => $reasons,
            'filters' => request()->only(['search', 'status', 'reason_type_id']),
            'reasonTypes' => ReasonType::active()->get(),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Reason::class);

        $reasonTypes = ReasonType::active()->get();
        return Inertia::render('Admin/Reasons/Create', [
            'reasonTypes' => $reasonTypes,
        ]);
    }

    public function store(StoreReasonRequest $request): RedirectResponse
    {
        $this->authorize('create', Reason::class);

        $data = $request->validated();
        Reason::create([
            'reason_type_id' => $data['reason_type_id'],
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('reasons.index')->with('status', 'Motivo criado com sucesso!');
    }

    public function edit(Reason $reason): InertiaResponse
    {
        $this->authorize('update', $reason);

        $reasonTypes = ReasonType::active()->get();
        return Inertia::render('Admin/Reasons/Edit', [
            'reason' => $reason,
            'reasonTypes' => $reasonTypes,
        ]);
    }

    public function update(UpdateReasonRequest $request, Reason $reason): RedirectResponse
    {
        $this->authorize('update', $reason);

        $data = $request->validated();
        $reason->update([
            'reason_type_id' => $data['reason_type_id'],
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('reasons.index')->with('status', 'Motivo atualizado com sucesso!');
    }

    public function destroy(Reason $reason): RedirectResponse
    {
        $this->authorize('delete', $reason);

        try {
            $reason->delete();
            return redirect()->route('reasons.index')->with('status', 'Motivo removido com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir motivo', [
                'reason_id' => $reason->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir motivo.');
        }
    }

    public function modal(Reason $reason): JsonResponse
    {
        $this->authorize('view', $reason);

        $reason->load(['reasonType', 'creator', 'updater']);

        return response()->json([
            'reason' => [
                'id' => $reason->id,
                'reason_type_id' => $reason->reason_type_id,
                'reason_type_name' => $reason->reasonType?->name,
                'name' => $reason->name,
                'status' => $reason->status,
                'created_at' => $reason->created_at?->format('d/m/Y H:i'),
                'updated_at' => $reason->updated_at?->format('d/m/Y H:i'),
                'created_by' => $reason->creator?->name,
                'updated_by' => $reason->updater?->name,
            ],
        ]);
    }
}
