<?php

namespace App\Http\Controllers;

use App\Models\ReasonType;
use App\Http\Requests\StoreReasonTypeRequest;
use App\Http\Requests\UpdateReasonTypeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReasonTypesController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', ReasonType::class);

        $query = ReasonType::query();

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $reasonTypes = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $reasonTypes->lastPage() && $reasonTypes->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $reasonTypes->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/ReasonTypes/Index', [
            'reasonTypes' => $reasonTypes,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', ReasonType::class);

        return Inertia::render('Admin/ReasonTypes/Create');
    }

    public function store(StoreReasonTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', ReasonType::class);

        $data = $request->validated();
        ReasonType::create([
            'name' => $data['name'],
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);

    return redirect()->route('reason-types.index')->with('status', 'Tipo de motivo criado com sucesso!');
    }

    public function edit(ReasonType $reasonType): InertiaResponse
    {
        $this->authorize('update', $reasonType);

        return Inertia::render('Admin/ReasonTypes/Edit', [
            'reasonType' => $reasonType,
        ]);
    }

    public function update(UpdateReasonTypeRequest $request, ReasonType $reasonType): RedirectResponse
    {
        $this->authorize('update', $reasonType);

        $data = $request->validated();
        $reasonType->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

    return redirect()->route('reason-types.index')->with('status', 'Tipo de motivo atualizado com sucesso!');
    }

    public function destroy(ReasonType $reasonType): RedirectResponse
    {
        try {
            $this->authorize('delete', $reasonType);
            $reasonType->delete();
            return redirect()->route('reason-types.index')->with('status', 'Tipo de motivo removido com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir tipo de motivo', [
                'reason_type_id' => $reasonType->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir tipo de motivo.');
        }
    }

    public function show(ReasonType $reasonType): JsonResponse
    {
        $this->authorize('view', $reasonType);
        $reasonType->load(['creator', 'updater']);

        return response()->json([
            'reasonType' => [
                'id' => $reasonType->id,
                'name' => $reasonType->name,
                'status' => $reasonType->status,
                'created_at' => $reasonType->created_at?->format('d/m/Y H:i'),
                'updated_at' => $reasonType->updated_at?->format('d/m/Y H:i'),
                'created_by' => $reasonType->creator?->name,
                'updated_by' => $reasonType->updater?->name,
            ],
        ]);
    }

    public function modal(ReasonType $reasonType): JsonResponse
    {
        $this->authorize('view', $reasonType);
        $reasonType->load(['creator', 'updater']);

        return response()->json([
            'reasonType' => [
                'id' => $reasonType->id,
                'name' => $reasonType->name,
                'status' => $reasonType->status,
                'created_at' => $reasonType->created_at?->format('d/m/Y H:i'),
                'updated_at' => $reasonType->updated_at?->format('d/m/Y H:i'),
                'created_by' => $reasonType->creator?->name,
                'updated_by' => $reasonType->updater?->name,
            ],
        ]);
    }
}
