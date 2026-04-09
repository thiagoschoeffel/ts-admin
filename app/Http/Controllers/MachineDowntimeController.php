<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineDowntimeRequest;
use App\Http\Requests\UpdateMachineDowntimeRequest;
use App\Models\Machine;
use App\Models\MachineDowntime;
use App\Models\Reason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MachineDowntimeController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', MachineDowntime::class);

        $query = MachineDowntime::with(['machine', 'reason']);

        if ($search = request('search')) {
            $query->search($search);
        }
        if ($machineId = request('machine_id')) {
            $query->where('machine_id', $machineId);
        }
        if ($reasonId = request('reason_id')) {
            $query->where('reason_id', $reasonId);
        }
        if ($period = request('period')) {
            $from = $period['from'] ?? null;
            $to = $period['to'] ?? null;
            $query->between($from, $to);
        }
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $downtimes = $query
            ->orderByDesc('started_at')
            ->paginate($perPage)
            ->withQueryString();

        // Decorate with derived fields
        $downtimes->getCollection()->transform(function (MachineDowntime $m) {
            $duration = null;
            if ($m->started_at) {
                $endTime = $m->ended_at ?: now();
                $minutes = abs($endTime->diffInMinutes($m->started_at));
                $hh = str_pad((string) intdiv($minutes, 60), 2, '0', STR_PAD_LEFT);
                $mm = str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
                $duration = $hh . ':' . $mm;
            }
            $m->machine_name = $m->machine?->name;
            $m->reason_name = $m->reason?->name;
            $m->duration = $duration;
            $m->started_at_formatted = $m->started_at?->format('d/m/Y H:i');
            $m->ended_at_formatted = $m->ended_at?->format('d/m/Y H:i');
            return $m;
        });

        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $downtimes->lastPage() && $downtimes->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $downtimes->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/MachineDowntimes/Index', [
            'downtimes' => $downtimes,
            'filters' => request()->only(['search', 'machine_id', 'reason_id', 'status', 'period']),
            'machines' => Machine::active()->orderBy('name')->get(['id', 'name']),
            'reasons' => Reason::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', MachineDowntime::class);
        return Inertia::render('Admin/MachineDowntimes/Create', [
            'machines' => Machine::active()->orderBy('name')->get(['id', 'name']),
            'reasons' => Reason::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMachineDowntimeRequest $request): RedirectResponse
    {
        $this->authorize('create', MachineDowntime::class);
        $data = $request->validated();
        MachineDowntime::create([
            'machine_id' => $data['machine_id'],
            'reason_id' => $data['reason_id'],
            'started_at' => $data['started_at'],
            'ended_at' => $data['ended_at'],
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => Auth::id(),
        ]);
        return redirect()->route('machine_downtimes.create')->with('status', 'Registro criado com sucesso.');
    }

    public function edit(MachineDowntime $machineDowntime): InertiaResponse
    {
        $this->authorize('update', $machineDowntime);
        return Inertia::render('Admin/MachineDowntimes/Edit', [
            'downtime' => $machineDowntime,
            'machines' => Machine::active()->orderBy('name')->get(['id', 'name']),
            'reasons' => Reason::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateMachineDowntimeRequest $request, MachineDowntime $machineDowntime): RedirectResponse
    {
        $this->authorize('update', $machineDowntime);
        $data = $request->validated();
        $machineDowntime->update([
            'machine_id' => $data['machine_id'],
            'reason_id' => $data['reason_id'],
            'started_at' => $data['started_at'],
            'ended_at' => $data['ended_at'],
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'updated_by' => Auth::id(),
        ]);
        return redirect()->route('machine_downtimes.index')->with('status', 'Registro atualizado com sucesso.');
    }

    public function destroy(MachineDowntime $machineDowntime): RedirectResponse
    {
        $this->authorize('delete', $machineDowntime);
        $machineDowntime->delete();
        return redirect()->route('machine_downtimes.index')->with('status', 'Registro excluído com sucesso.');
    }

    public function modal(MachineDowntime $machineDowntime): JsonResponse
    {
        $this->authorize('view', $machineDowntime);
        $machineDowntime->load(['machine', 'reason', 'creator', 'updater']);

        $duration = null;
        if ($machineDowntime->started_at) {
            $endTime = $machineDowntime->ended_at ?: now();
            $minutes = abs($endTime->diffInMinutes($machineDowntime->started_at));
            $hh = str_pad((string) intdiv($minutes, 60), 2, '0', STR_PAD_LEFT);
            $mm = str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
            $duration = $hh . ':' . $mm;
        }

        return response()->json([
            'downtime' => [
                'id' => $machineDowntime->id,
                'machine_id' => $machineDowntime->machine_id,
                'machine_name' => $machineDowntime->machine?->name,
                'reason_id' => $machineDowntime->reason_id,
                'reason_name' => $machineDowntime->reason?->name,
                'started_at' => $machineDowntime->started_at?->format('d/m/Y H:i'),
                'ended_at' => $machineDowntime->ended_at?->format('d/m/Y H:i'),
                'duration' => $duration,
                'notes' => $machineDowntime->notes,
                'status' => $machineDowntime->status,
                'created_at' => $machineDowntime->created_at?->format('d/m/Y H:i'),
                'updated_at' => $machineDowntime->updated_at?->format('d/m/Y H:i'),
                'created_by' => $machineDowntime->creator?->name,
                'updated_by' => $machineDowntime->updater?->name,
            ],
        ]);
    }
}

