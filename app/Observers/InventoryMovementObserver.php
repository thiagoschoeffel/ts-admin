<?php

namespace App\Observers;

use App\Models\InventoryMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryMovementObserver
{
    public function created(InventoryMovement $movement): void
    {
        Log::info('Movimento de estoque criado', [
            'movement_id' => $movement->id,
            'item_type' => $movement->item_type,
            'item_id' => $movement->item_id,
            'direction' => $movement->direction,
            'quantity' => $movement->quantity,
            'unit' => $movement->unit,
            'location_type' => $movement->location_type,
            'location_id' => $movement->location_id,
            'occurred_at' => $movement->occurred_at?->toDateTimeString(),
            'reference_type' => $movement->reference_type,
            'reference_id' => $movement->reference_id,
            'notes' => $movement->notes,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function updated(InventoryMovement $movement): void
    {
        $changes = $movement->getChanges();

        // Log only if there are actual changes (excluding timestamps)
        $significantChanges = array_diff_key($changes, ['updated_at' => 1]);

        if (!empty($significantChanges)) {
            Log::warning('Movimento de estoque alterado', [
                'movement_id' => $movement->id,
                'item_type' => $movement->item_type,
                'item_id' => $movement->item_id,
                'direction' => $movement->direction,
                'old_values' => array_intersect_key($movement->getOriginal(), $significantChanges),
                'new_values' => $significantChanges,
                'quantity_change' => $significantChanges['quantity'] ?? null,
                'unit' => $movement->unit,
                'occurred_at' => $movement->occurred_at?->toDateTimeString(),
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
    }

    public function deleting(InventoryMovement $movement): void
    {
        // Prevent deletion of inventory movements for audit purposes
        Log::critical('Tentativa de exclusão de movimento de estoque bloqueada', [
            'movement_id' => $movement->id,
            'item_type' => $movement->item_type,
            'item_id' => $movement->item_id,
            'direction' => $movement->direction,
            'quantity' => $movement->quantity,
            'unit' => $movement->unit,
            'occurred_at' => $movement->occurred_at?->toDateTimeString(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => 'Movimentos de estoque não podem ser excluídos por razões de auditoria',
        ]);

        throw new \Exception('Movimentos de estoque não podem ser excluídos. Entre em contato com o administrador do sistema.');
    }
}
