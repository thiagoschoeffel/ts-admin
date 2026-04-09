<?php

namespace App\Observers;

use App\Models\MoldedProduction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MoldedProductionObserver
{
    public function created(MoldedProduction $moldedProduction): void
    {
        Log::info('Produção de moldado criada', [
            'molded_production_id' => $moldedProduction->id,
            'production_pointing_id' => $moldedProduction->production_pointing_id,
            'mold_type_id' => $moldedProduction->mold_type_id,
            'sheet_number' => $moldedProduction->sheet_number,
            'quantity' => $moldedProduction->quantity,
            'package_weight' => $moldedProduction->package_weight,
            'total_weight_considered' => $moldedProduction->total_weight_considered,
            'scrap_quantity' => $moldedProduction->scrap_quantity,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(MoldedProduction $moldedProduction): void
    {
        $changes = $moldedProduction->getChanges();
        Log::info('Produção de moldado atualizada', [
            'molded_production_id' => $moldedProduction->id,
            'production_pointing_id' => $moldedProduction->production_pointing_id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(MoldedProduction $moldedProduction): void
    {
        Log::info('Produção de moldado excluída', [
            'molded_production_id' => $moldedProduction->id,
            'production_pointing_id' => $moldedProduction->production_pointing_id,
            'mold_type_id' => $moldedProduction->mold_type_id,
            'sheet_number' => $moldedProduction->sheet_number,
            'quantity' => $moldedProduction->quantity,
            'user_id' => Auth::id(),
        ]);
    }
}
