<?php

namespace App\Observers;

use App\Models\Operator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OperatorObserver
{
    public function created(Operator $operator): void
    {
        Log::info('Operador criado', [
            'operator_id' => $operator->id,
            'sector_id' => $operator->sector_id,
            'name' => $operator->name,
            'status' => $operator->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(Operator $operator): void
    {
        $changes = $operator->getChanges();
        Log::info('Operador atualizado', [
            'operator_id' => $operator->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(Operator $operator): void
    {
        Log::info('Operador excluído', [
            'operator_id' => $operator->id,
            'sector_id' => $operator->sector_id,
            'name' => $operator->name,
            'status' => $operator->status,
            'user_id' => Auth::id(),
        ]);
    }
}
