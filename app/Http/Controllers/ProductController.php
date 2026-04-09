<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use DomainException;

class ProductController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('components');

        if ($search = request('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $products = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Adjust out-of-range page
        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $products->lastPage() && $products->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $products->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Product::class);

        $products = Product::active()->get();
        return Inertia::render('Admin/Products/Create', [
            'products' => $products,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();
        $product = Product::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'unit_of_measure' => $data['unit_of_measure'],
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => Auth::id(),
        ]);
        if (!empty($data['components'])) {
            $syncData = collect($data['components'])->mapWithKeys(function ($item) {
                return [$item['id'] => ['quantity' => $item['quantity']]];
            })->toArray();
            $product->components()->sync($syncData);
        }
        return redirect()->route('products.index')->with('status', 'Produto criado com sucesso!');
    }

    public function edit(Product $product): InertiaResponse
    {
        $this->authorize('update', $product);

        $product->load('components');
        // Sort components by name and reindex; ensure we update the relation
        $sorted = $product->components->sortBy('name')->values();
        $product->setRelation('components', $sorted);

        $products = Product::active()->where('id', '!=', $product->id)->get();

        // Include inactive components for display
        $inactiveComponents = $product->components->where('status', 'inactive');
        if ($inactiveComponents->isNotEmpty()) {
            $products = $products->merge($inactiveComponents);
        }

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'products' => $products,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        if (!empty($data['components'])) {
            $componentIdsFromRequest = collect($data['components'])->pluck('id')->map(fn($id) => (int)$id)->toArray();
            $currentComponentIds = $product->components()->pluck('products.id')->map(fn($id) => (int)$id)->toArray();

            // Atualizar ou adicionar componentes
            foreach ($data['components'] as $item) {
                $pivotId = $item['pivot_id'] ?? null;
                $quantity = (float) $item['quantity'];
                if ($pivotId && in_array((int)$item['id'], $currentComponentIds)) {
                    $product->components()->updateExistingPivot($item['id'], ['quantity' => $quantity]);
                } else if (!$pivotId && !in_array((int)$item['id'], $currentComponentIds)) {
                    $product->components()->attach($item['id'], ['quantity' => $quantity]);
                }
            }

            // Remover componentes que não estão mais presentes
            $toDetach = array_diff($currentComponentIds, $componentIdsFromRequest);
            if (!empty($toDetach)) {
                $product->components()->detach($toDetach);
            }
        } else {
            $product->components()->detach();
        }
        // Atualize o produto por último para garantir que o nome não seja sobrescrito
        $product->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'unit_of_measure' => $data['unit_of_measure'],
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
            'status' => $data['status'] ?? 'active',
            'updated_by' => Auth::id(),
        ]);
        $product->refresh();
        return redirect()->route('products.index')->with('status', 'Produto atualizado com sucesso!');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->authorize('delete', $product);
            $product->delete();
            return redirect()->route('products.index')->with('status', 'Produto removido com sucesso!');
        } catch (AuthorizationException $e) {
            $message = $e->getMessage();
            if ($message === __('product.delete_blocked_has_orders')) {
                Log::warning('Tentativa de exclusão de produto com pedidos bloqueada', [
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'message' => $message,
                ]);
                return back()->with('error', $message);
            }
            abort(403, $message);
        } catch (DomainException $e) {
            Log::warning('Tentativa de exclusão de produto com pedidos bloqueada (Observer)', [
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir produto', [
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir produto.');
        }
    }

    public function modal(Product $product): JsonResponse
    {
        $this->authorize('view', $product);
        $product->load(['createdBy', 'updatedBy', 'components.components']);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->formattedPrice(),
                'unit_of_measure' => $product->unit_of_measure,
                'length' => $product->length,
                'width' => $product->width,
                'height' => $product->height,
                'weight' => $product->weight,
                'status' => $product->status,
                'created_at' => $product->created_at?->format('d/m/Y H:i'),
                'updated_at' => $product->updated_at?->format('d/m/Y H:i'),
                'created_by' => $product->createdBy?->name,
                'updated_by' => $product->updatedBy?->name,
                'components' => $product->components->map(function ($component) {
                    return [
                        'id' => $component->id,
                        'name' => $component->name,
                        'quantity' => $component->pivot->quantity,
                        'unit_of_measure' => $component->unit_of_measure,
                        'price' => $component->formattedPrice(),
                        'total' => 'R$ ' . number_format($component->price * $component->pivot->quantity, 2, ',', '.'),
                        'status' => $component->status,
                        'created_at' => $component->created_at?->format('d/m/Y H:i'),
                        'updated_at' => $component->updated_at?->format('d/m/Y H:i'),
                        'created_by' => $component->createdBy?->name,
                        'updated_by' => $component->updatedBy?->name,
                    ];
                }),
                'component_tree' => $this->buildComponentTree($product),
            ],
        ]);
    }

    private function buildComponentTree(Product $product, $level = 0, $visited = []): array
    {
        if (in_array($product->id, $visited)) {
            return []; // Evita loops infinitos
        }

        $visited[] = $product->id;
        $tree = [];

        foreach ($product->components->sortByDesc('pivot.id') as $component) {
            $tree[] = [
                'id' => $component->id,
                'name' => $component->name,
                'quantity' => $component->pivot->quantity,
                'unit_of_measure' => $component->unit_of_measure,
                'price' => $component->formattedPrice(),
                'total' => 'R$ ' . number_format($component->price * $component->pivot->quantity, 2, ',', '.'),
                'status' => $component->status,
                'level' => $level,
                'has_children' => $component->components->count() > 0,
                'children' => $this->buildComponentTree($component, $level + 1, $visited),
            ];
        }

        return $tree;
    }
}
