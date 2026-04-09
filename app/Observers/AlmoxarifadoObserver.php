<?php

namespace App\Observers;

use App\Models\Almoxarifado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AlmoxarifadoObserver
{
    public function created(Almoxarifado $almoxarifado): void
    {
        Log::info('Almoxarifado criado', [
            'almoxarifado_id' => $almoxarifado->id,
            'name' => $almoxarifado->name,
            'status' => $almoxarifado->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(Almoxarifado $almoxarifado): void
    {
        $changes = $almoxarifado->getChanges();
        Log::info('Almoxarifado atualizado', [
            'almoxarifado_id' => $almoxarifado->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(Almoxarifado $almoxarifado): void
    {
        Log::info('Almoxarifado excluído', [
            'almoxarifado_id' => $almoxarifado->id,
            'name' => $almoxarifado->name,
            'status' => $almoxarifado->status,
            'user_id' => Auth::id(),
        ]);
    }
}