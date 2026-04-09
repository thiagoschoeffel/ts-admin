<?php

namespace App\Http\Controllers;

use App\Models\Silo;
use App\Http\Requests\StoreSiloRequest;
use App\Http\Requests\UpdateSiloRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SiloController extends Controller
{
  public function index(): InertiaResponse|\Illuminate\Http\Response
  {
    $this->authorize('viewAny', Silo::class);

    $query = Silo::query();

    if ($search = request('search')) {
      $query->search($search);
    }

    if ($status = request('status')) {
      $query->where('status', $status);
    }

    $allowedPerPage = [10, 25, 50, 100];
    $perPageCandidate = (int) request()->integer('per_page');
    $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

    $silos = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

    // Adjust out-of-range page
    $requestedPage = max(1, (int) request()->query('page', 1));
    if ($requestedPage > $silos->lastPage() && $silos->lastPage() > 0) {
      $queryParams = request()->query();
      $queryParams['page'] = $silos->lastPage();
      return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
    }

    return Inertia::render('Admin/Silos/Index', [
      'silos' => $silos,
      'filters' => request()->only(['search', 'status']),
    ]);
  }

  public function create(): InertiaResponse
  {
    $this->authorize('create', Silo::class);

    return Inertia::render('Admin/Silos/Create');
  }

  public function store(StoreSiloRequest $request): RedirectResponse
  {
    $this->authorize('create', Silo::class);

    $data = $request->validated();
    Silo::create([
      'name' => $data['name'],
      'status' => $data['status'],
      'created_by' => Auth::id(),
    ]);

    return redirect()->route('silos.index')->with('status', 'Silo criado com sucesso!');
  }

  public function edit(Silo $silo): InertiaResponse
  {
    $this->authorize('update', $silo);

    return Inertia::render('Admin/Silos/Edit', [
      'silo' => $silo,
    ]);
  }

  public function update(UpdateSiloRequest $request, Silo $silo): RedirectResponse
  {
    $this->authorize('update', $silo);

    $data = $request->validated();
    $silo->update([
      'name' => $data['name'],
      'status' => $data['status'],
      'updated_by' => Auth::id(),
    ]);

    return redirect()->route('silos.index')->with('status', 'Silo atualizado com sucesso!');
  }

  public function destroy(Silo $silo): RedirectResponse
  {
    try {
      $this->authorize('delete', $silo);
      $silo->delete();
      return redirect()->route('silos.index')->with('status', 'Silo removido com sucesso!');
    } catch (AuthorizationException $e) {
      abort(403, $e->getMessage());
    } catch (\Exception $e) {
      Log::error('Erro ao excluir silo', [
        'silo_id' => $silo->id,
        'user_id' => Auth::id(),
        'error' => $e->getMessage(),
      ]);
      return back()->with('error', 'Erro interno ao excluir silo.');
    }
  }

  public function modal(Silo $silo): JsonResponse
  {
    $this->authorize('view', $silo);
    $silo->load(['createdBy', 'updatedBy']);

    return response()->json([
      'silo' => [
        'id' => $silo->id,
        'name' => $silo->name,
        'status' => $silo->status,
        'created_at' => $silo->created_at?->format('d/m/Y H:i'),
        'updated_at' => $silo->updated_at?->format('d/m/Y H:i'),
        'created_by' => $silo->createdBy?->name,
        'updated_by' => $silo->updatedBy?->name,
      ],
    ]);
  }
}
