<?php

namespace App\Observers;

use App\Models\MoldType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MoldTypeObserver
{
    public function created(MoldType $moldType): void
    {
        Log::info('Tipo de moldado criado', [
            'mold_type_id' => $moldType->id,
            'name' => $moldType->name,
            'status' => $moldType->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(MoldType $moldType): void
    {
        $changes = $moldType->getChanges();
        Log::info('Tipo de moldado atualizado', [
            'mold_type_id' => $moldType->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(MoldType $moldType): void
    {
        Log::info('Tipo de moldado excluído', [
            'mold_type_id' => $moldType->id,
            'name' => $moldType->name,
            'status' => $moldType->status,
            'user_id' => Auth::id(),
        ]);
    }
}