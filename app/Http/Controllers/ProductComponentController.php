<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreProductComponentRequest;
use App\Http\Requests\UpdateProductComponentRequest;

class ProductComponentController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $this->authorize('manageComponents', $product);

        return response()->json([
            'components' => $product->components()->orderBy('product_components.id', 'desc')->get()->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'quantity' => $component->pivot->quantity,
                    'price' => $component->formattedPrice(),
                    'total' => 'R$ ' . number_format($component->price * $component->pivot->quantity, 2, ',', '.'),
                    'status' => $component->status,
                    'created_at' => $component->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $component->updated_at?->format('d/m/Y H:i'),
                    'created_by' => $component->createdBy?->name,
                    'updated_by' => $component->updatedBy?->name,
                ];
            }),
        ]);
    }

    public function store(StoreProductComponentRequest $request, Product $product): JsonResponse
    {
        $this->authorize('createComponent', $product);

        $componentId = $request->component_id;
        $quantity = $request->quantity;

        // Verificar se o componente já existe
        if ($product->components()->where('component_id', $componentId)->exists()) {
            return response()->json(['message' => 'Este componente já foi adicionado ao produto.'], 422);
        }

        // Verificar ciclo (produto não pode ser componente de si mesmo)
        if ($componentId == $product->id) {
            return response()->json(['message' => 'Um produto não pode ser componente de si mesmo.'], 422);
        }

        // Verificar dependências circulares
        if ($this->hasCircularDependency($product->id, $componentId)) {
            return response()->json(['message' => 'Esta adição criaria uma dependência circular.'], 422);
        }

        $product->components()->attach($componentId, [
            'quantity' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component = $product->components()->where('component_id', $componentId)->first();

        return response()->json([
            'component' => [
                'id' => $component->id,
                'name' => $component->name,
                'quantity' => $quantity,
                'price' => $component->formattedPrice(),
                'total' => 'R$ ' . number_format($component->price * $quantity, 2, ',', '.'),
                'status' => $component->status,
                'created_at' => now()->format('d/m/Y H:i'),
                'updated_at' => now()->format('d/m/Y H:i'),
                'created_by' => Auth::user()->name,
                'updated_by' => Auth::user()->name,
            ],
        ], 201);
    }

    public function update(UpdateProductComponentRequest $request, Product $product, $componentId): JsonResponse
    {
        $this->authorize('updateComponent', $product);

        $quantity = $request->quantity;

        $product->components()->updateExistingPivot($componentId, [
            'quantity' => $quantity,
            'updated_at' => now(),
        ]);

        $component = $product->components()->where('component_id', $componentId)->first();

        return response()->json([
            'component' => [
                'id' => $component->id,
                'name' => $component->name,
                'quantity' => $quantity,
                'price' => $component->formattedPrice(),
                'total' => 'R$ ' . number_format($component->price * $quantity, 2, ',', '.'),
                'status' => $component->status,
                'created_at' => $component->created_at?->format('d/m/Y H:i'),
                'updated_at' => now()->format('d/m/Y H:i'),
                'created_by' => $component->createdBy?->name,
                'updated_by' => Auth::user()->name,
            ],
        ]);
    }

    public function destroy(Product $product, $componentId): JsonResponse
    {
        $this->authorize('deleteComponent', $product);

        $product->components()->detach($componentId);

        return response()->json(['message' => 'Componente removido com sucesso.']);
    }

    private function hasCircularDependency($productId, $componentId, $visited = []): bool
    {
        if (in_array($componentId, $visited)) {
            return true;
        }

        $component = Product::find($componentId);
        if (!$component) {
            return false;
        }

        $visited[] = $componentId;

        foreach ($component->components as $subComponent) {
            if ($subComponent->id == $productId || $this->hasCircularDependency($productId, $subComponent->id, $visited)) {
                return true;
            }
        }

        return false;
    }
}
