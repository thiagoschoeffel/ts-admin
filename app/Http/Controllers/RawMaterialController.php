<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Http\Requests\StoreRawMaterialRequest;
use App\Http\Requests\UpdateRawMaterialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RawMaterialController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', RawMaterial::class);

        $query = RawMaterial::query();

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $rawMaterials = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $rawMaterials->lastPage() && $rawMaterials->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $rawMaterials->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/RawMaterials/Index', [
            'rawMaterials' => $rawMaterials,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', RawMaterial::class);

        return Inertia::render('Admin/RawMaterials/Create');
    }

    public function store(StoreRawMaterialRequest $request): RedirectResponse
    {
        $this->authorize('create', RawMaterial::class);

        $data = $request->validated();
        RawMaterial::create([
            'name' => $data['name'],
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('raw-materials.index')->with('status', 'Matéria-prima criada com sucesso!');
    }

    public function edit(RawMaterial $rawMaterial): InertiaResponse
    {
        $this->authorize('update', $rawMaterial);

        return Inertia::render('Admin/RawMaterials/Edit', [
            'rawMaterial' => $rawMaterial,
        ]);
    }

    public function update(UpdateRawMaterialRequest $request, RawMaterial $rawMaterial): RedirectResponse
    {
        $this->authorize('update', $rawMaterial);

        $data = $request->validated();
        $rawMaterial->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('raw-materials.index')->with('status', 'Matéria-prima atualizada com sucesso!');
    }

    public function destroy(RawMaterial $rawMaterial): RedirectResponse
    {
        try {
            $this->authorize('delete', $rawMaterial);
            $rawMaterial->delete();
            return redirect()->route('raw-materials.index')->with('status', 'Matéria-prima removida com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir matéria-prima', [
                'raw_material_id' => $rawMaterial->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir matéria-prima.');
        }
    }

    public function modal(RawMaterial $rawMaterial): JsonResponse
    {
        $this->authorize('view', $rawMaterial);
        $rawMaterial->load(['createdBy', 'updatedBy']);

        return response()->json([
            'rawMaterial' => [
                'id' => $rawMaterial->id,
                'name' => $rawMaterial->name,
                'status' => $rawMaterial->status,
                'created_at' => $rawMaterial->created_at?->format('d/m/Y H:i'),
                'updated_at' => $rawMaterial->updated_at?->format('d/m/Y H:i'),
                'created_by' => $rawMaterial->createdBy?->name,
                'updated_by' => $rawMaterial->updatedBy?->name,
            ],
        ]);
    }
}