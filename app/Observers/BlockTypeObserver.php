<?php

namespace App\Observers;

use App\Models\BlockType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BlockTypeObserver
{
    public function created(BlockType $blockType): void
    {
        Log::info('Tipo de bloco criado', [
            'block_type_id' => $blockType->id,
            'name' => $blockType->name,
            'status' => $blockType->status,
            'user_id' => Auth::id(),
        ]);
    }

    public function updated(BlockType $blockType): void
    {
        $changes = $blockType->getChanges();
        Log::info('Tipo de bloco atualizado', [
            'block_type_id' => $blockType->id,
            'changes' => $changes,
            'user_id' => Auth::id(),
        ]);
    }

    public function deleted(BlockType $blockType): void
    {
        Log::info('Tipo de bloco excluído', [
            'block_type_id' => $blockType->id,
            'name' => $blockType->name,
            'status' => $blockType->status,
            'user_id' => Auth::id(),
        ]);
    }
}