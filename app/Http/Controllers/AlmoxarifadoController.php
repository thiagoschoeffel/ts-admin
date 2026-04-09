<?php

namespace App\Http\Controllers;

use App\Models\Almoxarifado;
use App\Http\Requests\StoreAlmoxarifadoRequest;
use App\Http\Requests\UpdateAlmoxarifadoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AlmoxarifadoController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Almoxarifado::class);

        $query = Almoxarifado::query();

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $almoxarifados = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $almoxarifados->lastPage() && $almoxarifados->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $almoxarifados->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Almoxarifados/Index', [
            'almoxarifados' => $almoxarifados,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Almoxarifado::class);

        return Inertia::render('Admin/Almoxarifados/Create');
    }

    public function store(StoreAlmoxarifadoRequest $request): RedirectResponse
    {
        $this->authorize('create', Almoxarifado::class);

        $data = $request->validated();
        Almoxarifado::create([
            'name' => $data['name'],
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('almoxarifados.index')->with('status', 'Almoxarifado criado com sucesso!');
    }

    public function edit(Almoxarifado $almoxarifado): InertiaResponse
    {
        $this->authorize('update', $almoxarifado);

        return Inertia::render('Admin/Almoxarifados/Edit', [
            'almoxarifado' => $almoxarifado,
        ]);
    }

    public function update(UpdateAlmoxarifadoRequest $request, Almoxarifado $almoxarifado): RedirectResponse
    {
        $this->authorize('update', $almoxarifado);

        $data = $request->validated();
        $almoxarifado->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('almoxarifados.index')->with('status', 'Almoxarifado atualizado com sucesso!');
    }

    public function destroy(Almoxarifado $almoxarifado): RedirectResponse
    {
        try {
            $this->authorize('delete', $almoxarifado);
            $almoxarifado->delete();

            return redirect()->route('almoxarifados.index')->with('status', 'Almoxarifado removido com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir almoxarifado', [
                'almoxarifado_id' => $almoxarifado->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir almoxarifado.');
        }
    }

    public function modal(Almoxarifado $almoxarifado): JsonResponse
    {
        $this->authorize('view', $almoxarifado);
        $almoxarifado->load(['createdBy', 'updatedBy']);

        return response()->json([
            'almoxarifado' => [
                'id' => $almoxarifado->id,
                'name' => $almoxarifado->name,
                'status' => $almoxarifado->status,
                'created_at' => $almoxarifado->created_at?->format('d/m/Y H:i'),
                'updated_at' => $almoxarifado->updated_at?->format('d/m/Y H:i'),
                'created_by' => $almoxarifado->createdBy?->name,
                'updated_by' => $almoxarifado->updatedBy?->name,
            ],
        ]);
    }
}