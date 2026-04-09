<?php

namespace App\Observers;

use App\Models\Sector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SectorObserver
{
    public function created(Sector $sector): void
    {
        Log::info('Setor criado', [
            'sector_id' => $sector->id,
            'name' => $sector->name,
            'status' => $sector->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(Sector $sector): void
    {
        $changes = $sector->getChanges();
        Log::info('Setor atualizado', [
            'sector_id' => $sector->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(Sector $sector): void
    {
        Log::info('Setor excluído', [
            'sector_id' => $sector->id,
            'name' => $sector->name,
            'status' => $sector->status,
            'user_id' => Auth::id(),
        ]);
    }
}
