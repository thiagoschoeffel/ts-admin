<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Http\Requests\StoreMachineRequest;
use App\Http\Requests\UpdateMachineRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MachinesController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Machine::class);

        $query = Machine::with('sector');

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($sectorId = request('sector_id')) {
            $query->where('sector_id', $sectorId);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $machines = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $machines->lastPage() && $machines->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $machines->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Machines/Index', [
            'machines' => $machines,
            'filters' => request()->only(['search', 'status', 'sector_id']),
            'sectors' => \App\Models\Sector::where('status', 'active')->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Machine::class);

        return Inertia::render('Admin/Machines/Create', [
            'sectors' => \App\Models\Sector::where('status', 'active')->select('id', 'name')->get(),
        ]);
    }

    public function store(StoreMachineRequest $request): RedirectResponse
    {
        $this->authorize('create', Machine::class);

        $data = $request->validated();
        Machine::create([
            'sector_id' => $data['sector_id'],
            'name' => $data['name'],
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('machines.index')->with('status', 'Máquina criada com sucesso!');
    }

    public function edit(Machine $machine): InertiaResponse
    {
        $this->authorize('update', $machine);

        return Inertia::render('Admin/Machines/Edit', [
            'machine' => $machine,
            'sectors' => \App\Models\Sector::where('status', 'active')->select('id', 'name')->get(),
        ]);
    }

    public function update(UpdateMachineRequest $request, Machine $machine): RedirectResponse
    {
        $this->authorize('update', $machine);

        $data = $request->validated();
        $machine->update([
            'sector_id' => $data['sector_id'],
            'name' => $data['name'],
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('machines.index')->with('status', 'Máquina atualizada com sucesso!');
    }

    public function destroy(Machine $machine): RedirectResponse
    {
        try {
            $this->authorize('delete', $machine);
            $machine->delete();
            return redirect()->route('machines.index')->with('status', 'Máquina removida com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir máquina', [
                'machine_id' => $machine->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir máquina.');
        }
    }

    public function modal(Machine $machine): JsonResponse
    {
        $this->authorize('view', $machine);
        $machine->load(['sector', 'creator', 'updater']);

        return response()->json([
            'machine' => [
                'id' => $machine->id,
                'sector' => $machine->sector->name,
                'name' => $machine->name,
                'status' => $machine->status,
                'created_at' => $machine->created_at?->format('d/m/Y H:i'),
                'updated_at' => $machine->updated_at?->format('d/m/Y H:i'),
                'created_by' => $machine->creator?->name,
                'updated_by' => $machine->updater?->name,
            ],
        ]);
    }
}
