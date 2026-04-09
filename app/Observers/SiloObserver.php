<?php

namespace App\Observers;

use App\Models\Silo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SiloObserver
{
  public function created(Silo $silo): void
  {
    Log::info('Silo criado', [
      'silo_id' => $silo->id,
      'name' => $silo->name,
      'status' => $silo->status,
      'user_id' => Auth::id(),
    ]);
  }

  public function updated(Silo $silo): void
  {
    $changes = $silo->getChanges();
    Log::info('Silo atualizado', [
      'silo_id' => $silo->id,
      'changes' => $changes,
      'user_id' => Auth::id(),
    ]);
  }

  public function deleted(Silo $silo): void
  {
    Log::info('Silo excluído', [
      'silo_id' => $silo->id,
      'name' => $silo->name,
      'status' => $silo->status,
      'user_id' => Auth::id(),
    ]);
  }
}
