<?php

namespace App\Observers;

use App\Models\Machine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MachineObserver
{
    public function created(Machine $machine): void
    {
        Log::info('Máquina criada', [
            'machine_id' => $machine->id,
            'sector_id' => $machine->sector_id,
            'name' => $machine->name,
            'status' => $machine->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(Machine $machine): void
    {
        $changes = $machine->getChanges();
        Log::info('Máquina atualizada', [
            'machine_id' => $machine->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(Machine $machine): void
    {
        Log::info('Máquina excluída', [
            'machine_id' => $machine->id,
            'sector_id' => $machine->sector_id,
            'name' => $machine->name,
            'status' => $machine->status,
            'user_id' => Auth::id(),
        ]);
    }
}
