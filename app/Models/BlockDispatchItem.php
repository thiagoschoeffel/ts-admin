<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockDispatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'block_dispatch_id',
        'block_production_id',
    ];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(BlockDispatch::class, 'block_dispatch_id');
    }

    public function blockProduction(): BelongsTo
    {
        return $this->belongsTo(BlockProduction::class);
    }
}

