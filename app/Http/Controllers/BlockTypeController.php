<?php

namespace App\Http\Controllers;

use App\Models\BlockType;
use App\Http\Requests\StoreBlockTypeRequest;
use App\Http\Requests\UpdateBlockTypeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BlockTypeController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('viewAny', BlockType::class);

        $query = BlockType::query();

        if ($search = request('search')) {
            $query->search($search);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $blockTypes = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $blockTypes->lastPage() && $blockTypes->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $blockTypes->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/BlockTypes/Index', [
            'blockTypes' => $blockTypes,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', BlockType::class);

        return Inertia::render('Admin/BlockTypes/Create');
    }

    public function store(StoreBlockTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', BlockType::class);

        $data = $request->validated();
        BlockType::create([
            'name' => $data['name'],
            'raw_material_percentage' => $data['raw_material_percentage'],
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('block-types.index')->with('status', 'Tipo de bloco criado com sucesso!');
    }

    public function edit(BlockType $blockType): InertiaResponse
    {
        $this->authorize('update', $blockType);

        return Inertia::render('Admin/BlockTypes/Edit', [
            'blockType' => $blockType,
        ]);
    }

    public function update(UpdateBlockTypeRequest $request, BlockType $blockType): RedirectResponse
    {
        $this->authorize('update', $blockType);

        $data = $request->validated();
        $blockType->update([
            'name' => $data['name'],
            'raw_material_percentage' => $data['raw_material_percentage'],
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('block-types.index')->with('status', 'Tipo de bloco atualizado com sucesso!');
    }

    public function destroy(BlockType $blockType): RedirectResponse
    {
        try {
            $this->authorize('delete', $blockType);
            $blockType->delete();

            return redirect()->route('block-types.index')->with('status', 'Tipo de bloco removido com sucesso!');
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir tipo de bloco', [
                'block_type_id' => $blockType->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir tipo de bloco.');
        }
    }

    public function modal(BlockType $blockType): JsonResponse
    {
        $this->authorize('view', $blockType);
        $blockType->load(['createdBy', 'updatedBy']);

        return response()->json([
            'blockType' => [
                'id' => $blockType->id,
                'name' => $blockType->name,
                'raw_material_percentage' => $blockType->raw_material_percentage,
                'status' => $blockType->status,
                'created_at' => $blockType->created_at?->format('d/m/Y H:i'),
                'updated_at' => $blockType->updated_at?->format('d/m/Y H:i'),
                'created_by' => $blockType->createdBy?->name,
                'updated_by' => $blockType->updatedBy?->name,
            ],
        ]);
    }
}