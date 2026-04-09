<?php

namespace App\Observers;

use App\Models\BlockProduction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BlockProductionObserver
{
    public function created(BlockProduction $blockProduction): void
    {
        Log::info('Produção de bloco criada', [
            'block_production_id' => $blockProduction->id,
            'production_pointing_id' => $blockProduction->production_pointing_id,
            'block_type_id' => $blockProduction->block_type_id,
            'sheet_number' => $blockProduction->sheet_number,
            'weight' => $blockProduction->weight,
            'length_mm' => $blockProduction->length_mm,
            'width_mm' => $blockProduction->width_mm,
            'height_mm' => $blockProduction->height_mm,
            'is_scrap' => $blockProduction->is_scrap,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(BlockProduction $blockProduction): void
    {
        $changes = $blockProduction->getChanges();
        Log::info('Produção de bloco atualizada', [
            'block_production_id' => $blockProduction->id,
            'production_pointing_id' => $blockProduction->production_pointing_id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(BlockProduction $blockProduction): void
    {
        Log::info('Produção de bloco excluída', [
            'block_production_id' => $blockProduction->id,
            'production_pointing_id' => $blockProduction->production_pointing_id,
            'block_type_id' => $blockProduction->block_type_id,
            'sheet_number' => $blockProduction->sheet_number,
            'weight' => $blockProduction->weight,
            'user_id' => Auth::id(),
        ]);
    }
}
