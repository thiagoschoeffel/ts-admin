<?php

namespace App\Observers;

use App\Models\ProductionPointing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductionPointingObserver
{
    public function created(ProductionPointing $productionPointing): void
    {
        Log::info('Apontamento de produção criado', [
            'production_pointing_id' => $productionPointing->id,
            'sheet_number' => $productionPointing->sheet_number,
            'status' => $productionPointing->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(ProductionPointing $productionPointing): void
    {
        $changes = $productionPointing->getChanges();
        Log::info('Apontamento de produção atualizado', [
            'production_pointing_id' => $productionPointing->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(ProductionPointing $productionPointing): void
    {
        Log::info('Apontamento de produção excluído', [
            'production_pointing_id' => $productionPointing->id,
            'sheet_number' => $productionPointing->sheet_number,
            'status' => $productionPointing->status,
            'user_id' => Auth::id(),
        ]);
    }
}
