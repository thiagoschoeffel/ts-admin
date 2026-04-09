<?php

namespace App\Observers;

use App\Models\ReasonType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReasonTypeObserver
{
    public function created(ReasonType $reasonType): void
    {
        Log::info('Tipo de motivo criado', [
            'reason_type_id' => $reasonType->id,
            'name' => $reasonType->name,
            'status' => $reasonType->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(ReasonType $reasonType): void
    {
        $changes = $reasonType->getChanges();
        Log::info('Tipo de motivo atualizado', [
            'reason_type_id' => $reasonType->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(ReasonType $reasonType): void
    {
        Log::info('Tipo de motivo excluído', [
            'reason_type_id' => $reasonType->id,
            'name' => $reasonType->name,
            'status' => $reasonType->status,
            'user_id' => Auth::id(),
        ]);
    }
}
