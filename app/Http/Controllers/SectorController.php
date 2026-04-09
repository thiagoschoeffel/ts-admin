<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Http\Requests\StoreSectorRequest;
use App\Http\Requests\UpdateSectorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SectorController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Sector::class);

        $query = Sector::query();

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $sectors = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $sectors->lastPage() && $sectors->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $sectors->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Sectors/Index', [
            'sectors' => $sectors,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Sector::class);

        return Inertia::render('Admin/Sectors/Create');
    }

    public function store(StoreSectorRequest $request): RedirectResponse
    {
        $this->authorize('create', Sector::class);

        $data = $request->validated();
        Sector::create([
            'name' => $data['name'],
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('sectors.index')->with('status', 'Setor criado com sucesso!');
    }

    public function edit(Sector $sector): InertiaResponse
    {
        $this->authorize('update', $sector);

        return Inertia::render('Admin/Sectors/Edit', [
            'sector' => $sector,
        ]);
    }

    public function update(UpdateSectorRequest $request, Sector $sector): RedirectResponse
    {
        $this->authorize('update', $sector);

        $data = $request->validated();
        $sector->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('sectors.index')->with('status', 'Setor atualizado com sucesso!');
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        try {
            $this->authorize('delete', $sector);
            $sector->delete();
            return redirect()->route('sectors.index')->with('status', 'Setor removido com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir setor', [
                'sector_id' => $sector->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir setor.');
        }
    }

    public function modal(Sector $sector): JsonResponse
    {
        $this->authorize('view', $sector);
        $sector->load(['createdBy', 'updatedBy']);

        return response()->json([
            'sector' => [
                'id' => $sector->id,
                'name' => $sector->name,
                'status' => $sector->status,
                'created_at' => $sector->created_at?->format('d/m/Y H:i'),
                'updated_at' => $sector->updated_at?->format('d/m/Y H:i'),
                'created_by' => $sector->createdBy?->name,
                'updated_by' => $sector->updatedBy?->name,
            ],
        ]);
    }
}
