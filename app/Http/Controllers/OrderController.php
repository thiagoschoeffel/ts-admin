<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Client;
use App\Models\Address;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        $this->authorize('viewAny', Order::class);

        $query = Order::with(['client', 'user', 'address']);

        if ($search = $request->string('search')->toString()) {
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filtro por período do pedido (ordered_at) com suporte a hora
        $orderedFrom = $request->get('ordered_from');
        $orderedTo = $request->get('ordered_to');
        $from = null;
        $to = null;
        try {
            if ($orderedFrom) {
                $from = Carbon::createFromFormat('Y-m-d H:i', $orderedFrom);
            }
        } catch (\Throwable $e) {
            try {
                $from = Carbon::createFromFormat('Y-m-d', $orderedFrom)->startOfDay();
            } catch (\Throwable $e2) {
            }
        }
        try {
            if ($orderedTo) {
                $to = Carbon::createFromFormat('Y-m-d H:i', $orderedTo);
            }
        } catch (\Throwable $e) {
            try {
                $to = Carbon::createFromFormat('Y-m-d', $orderedTo)->endOfDay();
            } catch (\Throwable $e2) {
            }
        }

        if ($from && $to) {
            $query->whereBetween('ordered_at', [$from, $to]);
        } elseif ($from) {
            $query->where('ordered_at', '>=', $from);
        } elseif ($to) {
            $query->where('ordered_at', '<=', $to);
        }

        // Resolve per_page
        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) $request->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $orders = $query
            ->orderBy('ordered_at', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Order $order) {
                return [
                    'id' => $order->id,
                    'client' => [
                        'id' => $order->client->id,
                        'name' => $order->client->name,
                    ],
                    'user' => [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                    ],
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'delivery_type' => $order->delivery_type,
                    'address' => $order->address ? [
                        'id' => $order->address->id,
                        'description' => $order->address->description,
                        'address' => $order->address->address,
                        'address_number' => $order->address->address_number,
                        'city' => $order->address->city,
                        'state' => $order->address->state,
                    ] : null,
                    'total' => $order->total,
                    'ordered_at' => $order->ordered_at?->format('d/m/Y H:i'),
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                ];
            });

        // If requested page exceeds last page, redirect to last valid
        $requestedPage = max(1, (int) $request->query('page', 1));
        if ($requestedPage > $orders->lastPage() && $orders->lastPage() > 0) {
            $queryParams = $request->query();
            $queryParams['page'] = $orders->lastPage();
            return redirect()->to($request->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Orders/Index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->get('status'),
                'ordered_from' => $orderedFrom,
                'ordered_to' => $orderedTo,
            ],
            'orders' => $orders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Order::class);

        $recentOrders = Order::with(['client', 'user', 'address'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'client' => [
                        'id' => $order->client->id ?? null,
                        'name' => $order->client->name ?? 'Cliente não informado',
                    ],
                    'user' => [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                    ],
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'delivery_type' => $order->delivery_type,
                    'address' => $order->address ? [
                        'id' => $order->address->id,
                        'description' => $order->address->description,
                        'address' => $order->address->address,
                        'address_number' => $order->address->address_number,
                        'city' => $order->address->city,
                        'state' => $order->address->state,
                    ] : null,
                    'total' => $order->total,
                    'ordered_at' => $order->ordered_at?->format('d/m/Y H:i'),
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('Admin/Orders/Create', [
            'products' => Product::where('status', 'active')->select('id', 'name', 'code', 'price')->get(),
            'clients' => Client::where('status', 'active')->select('id', 'name')->get(),
            'addresses' => Address::select('id', 'client_id', 'description', 'address', 'address_number', 'city', 'state')->get(),
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $this->authorize('create', Order::class);

        $order = Order::create([
            'client_id' => $request->client_id,
            'user_id' => Auth::id(),
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'delivery_type' => $request->delivery_type,
            'address_id' => $request->delivery_type === 'pickup' ? null : $request->address_id,
            'total' => collect($request->items)->sum(fn($item) => $item['quantity'] * Product::find($item['product_id'])->price),
            'ordered_at' => now(),
            'created_by_id' => Auth::id(),
            'updated_by_id' => Auth::id(),
        ]);

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'total' => $item['quantity'] * $product->price,
            ]);
        }

        return redirect()->route('orders.create')->with('status', 'Pedido criado com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        $order = Order::with(['client', 'user', 'items.product', 'address'])->findOrFail($id);
        $this->authorize('update', $order);

        $recentOrders = Order::with(['client', 'user', 'address'])
            ->where('id', '!=', $id)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'client' => [
                        'id' => $order->client->id ?? null,
                        'name' => $order->client->name ?? 'Cliente não informado',
                    ],
                    'user' => [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                    ],
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'delivery_type' => $order->delivery_type,
                    'address' => $order->address ? [
                        'id' => $order->address->id,
                        'description' => $order->address->description,
                        'address' => $order->address->address,
                        'address_number' => $order->address->address_number,
                        'city' => $order->address->city,
                        'state' => $order->address->state,
                    ] : null,
                    'total' => $order->total,
                    'ordered_at' => $order->ordered_at?->format('d/m/Y H:i'),
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                ];
            });

        $clients = Client::where('status', 'active')->select('id', 'name')->get();

        return Inertia::render('Admin/Orders/Edit', [
            'order' => [
                'id' => $order->id,
                'client_id' => $order->client_id,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'delivery_type' => $order->delivery_type,
                'address_id' => $order->address_id,
                'address' => $order->address ? [
                    'id' => $order->address->id,
                    'description' => $order->address->description,
                    'address' => $order->address->address,
                    'address_number' => $order->address->address_number,
                    'city' => $order->address->city,
                    'state' => $order->address->state,
                ] : null,
                'total' => $order->total,
                'ordered_at' => $order->ordered_at?->format('d/m/Y H:i'),
                'created_at' => $order->created_at->format('d/m/Y H:i'),
                'items' => collect($order->items)->sortByDesc('id')->values()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->product->name,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (float) $item->quantity,
                        'total' => (float) $item->total,
                    ];
                })->all(),
            ],
            'currentClient' => $order->client ? ['id' => $order->client->id, 'name' => $order->client->name] : null,
            'products' => Product::where('status', 'active')->select('id', 'name', 'code', 'price')->get(),
            'clients' => $clients,
            'addresses' => Address::select('id', 'client_id', 'description', 'address', 'address_number', 'city', 'state')->get(),
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, string $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('update', $order);

        // Check if status is being changed and authorize if so
        if ($request->status !== $order->status) {
            try {
                $this->authorize('updateStatus', $order);
            } catch (AuthorizationException $e) {
                Log::warning('Tentativa de alteração de status de pedido não autorizada', [
                    'order_id' => $id,
                    'user_id' => Auth::id(),
                    'current_status' => $order->status,
                    'attempted_status' => $request->status,
                    'error' => $e->getMessage()
                ]);

                return redirect()->back()->with('error', __('auth.forbidden_orders_status_update'));
            }
        }

        // Atualizar dados básicos do pedido
        $order->update([
            'client_id' => $request->client_id,
            'status' => $request->status,
            'payment_method' => $request->payment_method,
            'delivery_type' => $request->delivery_type,
            'address_id' => $request->delivery_type === 'pickup' ? null : $request->address_id,
            'updated_by_id' => Auth::id(),
            // Total is already updated when items are modified individually
        ]);

        return redirect()->route('orders.index')->with('status', 'Pedido atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);

        try {
            $this->authorize('delete', $order);

            // Delete all order items first
            $order->items()->delete();

            // Delete the order
            $order->delete();

            return redirect()->route('orders.index')->with('status', 'Pedido excluído com sucesso.');
        } catch (AuthorizationException $e) {
            Log::warning('Tentativa de exclusão de pedido não autorizada', [
                'order_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        } catch (DomainException $e) {
            Log::error('Erro ao excluir pedido', [
                'order_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the quantity of a specific order item.
     */
    public function updateItem(UpdateOrderItemRequest $request, string $orderId, string $itemId)
    {
        $order = Order::findOrFail($orderId);
        $this->authorize('updateItem', $order);

        $item = $order->items()->findOrFail($itemId);

        $quantity = (float) $request->quantity;

        // Ensure quantity is properly rounded to 2 decimal places
        $quantity = round($quantity, 2);

        $updateData = [
            'quantity' => $quantity,
        ];

        if ($request->has('product_id') && $request->product_id != $item->product_id) {
            $newProduct = Product::findOrFail($request->product_id);
            $updateData['product_id'] = $request->product_id;
            $updateData['unit_price'] = $newProduct->price;
        }

        Log::info('Updating order item', [
            'order_id' => $orderId,
            'item_id' => $itemId,
            'old_quantity' => $item->quantity,
            'new_quantity' => $quantity,
            'old_product_id' => $item->product_id,
            'new_product_id' => $updateData['product_id'] ?? $item->product_id,
            'unit_price' => $updateData['unit_price'] ?? $item->unit_price,
        ]);

        $item->update($updateData);
        $item->update(['total' => $item->quantity * $item->unit_price]);

        // Recalculate order total
        $order->update([
            'total' => $order->items()->sum('total'),
            'updated_by_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'total' => (float) $item->total,
            ],
            'order_total' => (float) $order->total,
        ]);
    }

    /**
     * Remove a specific item from the order.
     */
    public function removeItem(string $orderId, string $itemId)
    {
        $order = Order::findOrFail($orderId);
        $this->authorize('removeItem', $order);

        $item = $order->items()->findOrFail($itemId);

        $item->delete();

        // Recalculate order total
        $order->update([
            'total' => $order->items()->sum('total'),
            'updated_by_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'order_total' => $order->total,
        ]);
    }

    /**
     * Add a new item to the order.
     */
    public function addItem(StoreOrderItemRequest $request, string $orderId)
    {
        $order = Order::findOrFail($orderId);
        $this->authorize('addItem', $order);

        $product = Product::findOrFail($request->product_id);

        $quantity = floatval($request->quantity);

        // Ensure quantity is properly rounded to 2 decimal places
        $quantity = round($quantity, 2);

        // Check if item already exists
        $existingItem = $order->items()->where('product_id', $request->product_id)->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;
            $newTotal = $newQuantity * $existingItem->unit_price;

            $existingItem->update([
                'quantity' => $newQuantity,
                'total' => $newTotal,
            ]);

            $item = $existingItem;
        } else {
            $newTotal = $quantity * $product->price;

            $item = $order->items()->create([
                'product_id' => $request->product_id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total' => $newTotal,
            ]);
        }

        // Recalculate order total
        $itemsTotal = $order->items()->sum('total');
        $order->update([
            'total' => $itemsTotal,
            'updated_by_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $product->name,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'total' => (float) $item->total,
            ],
            'order_total' => (float) $order->total,
        ]);
    }

    /**
     * Get order details for modal display.
     */
    public function modal(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['client', 'user', 'items.product', 'address', 'createdBy', 'updatedBy']);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'client' => $order->client ? [
                    'id' => $order->client->id,
                    'name' => $order->client->name,
                ] : null,
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                ],
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'delivery_type' => $order->delivery_type,
                'address' => $order->address ? [
                    'id' => $order->address->id,
                    'description' => $order->address->description,
                    'address' => $order->address->address,
                    'address_number' => $order->address->address_number,
                    'address_complement' => $order->address->address_complement,
                    'neighborhood' => $order->address->neighborhood,
                    'city' => $order->address->city,
                    'state' => $order->address->state,
                ] : null,
                'total' => (float) $order->total,
                'ordered_at' => $order->ordered_at?->format('d/m/Y H:i'),
                'created_at' => $order->created_at->format('d/m/Y H:i'),
                'updated_at' => $order->updated_at?->format('d/m/Y H:i'),
                'created_by' => $order->createdBy?->name,
                'updated_by' => $order->updatedBy?->name,
                'items' => collect($order->items)->sortByDesc('id')->values()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (float) $item->quantity,
                        'total' => (float) $item->total,
                    ];
                })->all(),
            ],
        ]);
    }
}
