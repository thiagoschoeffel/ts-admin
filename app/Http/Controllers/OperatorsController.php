<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Http\Requests\StoreOperatorRequest;
use App\Http\Requests\UpdateOperatorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class OperatorsController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Operator::class);

        $query = Operator::with('sector');

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($sectorId = request('sector_id')) {
            $query->where('sector_id', $sectorId);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $operators = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $operators->lastPage() && $operators->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $operators->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Operators/Index', [
            'operators' => $operators,
            'filters' => request()->only(['search', 'sector_id']),
            'sectors' => \App\Models\Sector::where('status', 'active')->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Operator::class);
        return Inertia::render('Admin/Operators/Create', [
            'sectors' => \App\Models\Sector::where('status', 'active')->select('id', 'name')->get(),
        ]);
    }

    public function store(StoreOperatorRequest $request): RedirectResponse
    {
        $this->authorize('create', Operator::class);
        $data = $request->validated();
        Operator::create([
            'sector_id' => $data['sector_id'],
            'name' => $data['name'],
            'created_by' => Auth::id(),
        ]);
        return redirect()->route('operators.index')->with('status', 'Operador criado com sucesso!');
    }

    public function edit(Operator $operator): InertiaResponse
    {
        $this->authorize('update', $operator);
        return Inertia::render('Admin/Operators/Edit', [
            'operator' => $operator,
            'sectors' => \App\Models\Sector::where('status', 'active')->select('id', 'name')->get(),
        ]);
    }

    public function update(UpdateOperatorRequest $request, Operator $operator): RedirectResponse
    {
        $this->authorize('update', $operator);
        $data = $request->validated();
        $operator->update([
            'sector_id' => $data['sector_id'],
            'name' => $data['name'],
            'updated_by' => Auth::id(),
        ]);
        return redirect()->route('operators.index')->with('status', 'Operador atualizado com sucesso!');
    }

    public function destroy(Operator $operator): RedirectResponse
    {
        try {
            $this->authorize('delete', $operator);
            $operator->delete();
            return redirect()->route('operators.index')->with('status', 'Operador removido com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir operador', [
                'operator_id' => $operator->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir operador.');
        }
    }

    public function modal(Operator $operator): JsonResponse
    {
        $this->authorize('view', $operator);
        $operator->load(['sector', 'creator', 'updater']);
        return response()->json([
            'operator' => [
                'id' => $operator->id,
                'sector' => $operator->sector->name,
                'name' => $operator->name,
                'created_at' => $operator->created_at?->format('d/m/Y H:i'),
                'updated_at' => $operator->updated_at?->format('d/m/Y H:i'),
                'created_by' => $operator->creator?->name,
                'updated_by' => $operator->updater?->name,
            ],
        ]);
    }
}
