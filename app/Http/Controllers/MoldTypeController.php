<?php

namespace App\Http\Controllers;

use App\Models\MoldType;
use App\Http\Requests\StoreMoldTypeRequest;
use App\Http\Requests\UpdateMoldTypeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MoldTypeController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('viewAny', MoldType::class);

        $query = MoldType::query();

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $moldTypes = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $moldTypes->lastPage() && $moldTypes->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $moldTypes->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/MoldTypes/Index', [
            'moldTypes' => $moldTypes,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', MoldType::class);

        return Inertia::render('Admin/MoldTypes/Create');
    }

    public function store(StoreMoldTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', MoldType::class);

        $data = $request->validated();
        MoldType::create([
            'name' => $data['name'],
            'pieces_per_package' => $data['pieces_per_package'],
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('mold-types.index')->with('status', 'Tipo de moldado criado com sucesso!');
    }

    public function edit(MoldType $moldType): InertiaResponse
    {
        $this->authorize('update', $moldType);

        return Inertia::render('Admin/MoldTypes/Edit', [
            'moldType' => $moldType,
        ]);
    }

    public function update(UpdateMoldTypeRequest $request, MoldType $moldType): RedirectResponse
    {
        $this->authorize('update', $moldType);

        $data = $request->validated();
        $moldType->update([
            'name' => $data['name'],
            'pieces_per_package' => $data['pieces_per_package'],
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('mold-types.index')->with('status', 'Tipo de moldado atualizado com sucesso!');
    }

    public function destroy(MoldType $moldType): RedirectResponse
    {
        try {
            $this->authorize('delete', $moldType);
            $moldType->delete();

            return redirect()->route('mold-types.index')->with('status', 'Tipo de moldado removido com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir tipo de moldado', [
                'mold_type_id' => $moldType->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir tipo de moldado.');
        }
    }

    public function modal(MoldType $moldType): JsonResponse
    {
        $this->authorize('view', $moldType);
        $moldType->load(['createdBy', 'updatedBy']);

        return response()->json([
            'moldType' => [
                'id' => $moldType->id,
                'name' => $moldType->name,
                'pieces_per_package' => $moldType->pieces_per_package,
                'status' => $moldType->status,
                'created_at' => $moldType->created_at?->format('d/m/Y H:i'),
                'updated_at' => $moldType->updated_at?->format('d/m/Y H:i'),
                'created_by' => $moldType->createdBy?->name,
                'updated_by' => $moldType->updatedBy?->name,
            ],
        ]);
    }
}