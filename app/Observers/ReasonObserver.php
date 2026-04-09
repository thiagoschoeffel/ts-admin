<?php

namespace App\Observers;

use App\Models\Reason;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReasonObserver
{
    public function created(Reason $reason): void
    {
        Log::info('Motivo criado', [
            'reason_id' => $reason->id,
            'reason_type_id' => $reason->reason_type_id,
            'name' => $reason->name,
            'status' => $reason->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(Reason $reason): void
    {
        $changes = $reason->getChanges();
        Log::info('Motivo atualizado', [
            'reason_id' => $reason->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(Reason $reason): void
    {
        Log::info('Motivo excluído', [
            'reason_id' => $reason->id,
            'reason_type_id' => $reason->reason_type_id,
            'name' => $reason->name,
            'status' => $reason->status,
            'user_id' => Auth::id(),
        ]);
    }
}
