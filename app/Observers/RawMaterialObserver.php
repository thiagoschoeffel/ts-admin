<?php

namespace App\Observers;

use App\Models\RawMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RawMaterialObserver
{
    public function created(RawMaterial $rawMaterial): void
    {
        Log::info('Matéria-prima criada', [
            'raw_material_id' => $rawMaterial->id,
            'name' => $rawMaterial->name,
            'status' => $rawMaterial->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(RawMaterial $rawMaterial): void
    {
        $changes = $rawMaterial->getChanges();
        Log::info('Matéria-prima atualizada', [
            'raw_material_id' => $rawMaterial->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(RawMaterial $rawMaterial): void
    {
        Log::info('Matéria-prima excluída', [
            'raw_material_id' => $rawMaterial->id,
            'name' => $rawMaterial->name,
            'status' => $rawMaterial->status,
            'user_id' => Auth::id(),
        ]);
    }
}