<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoldedDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispatched_at',
        'manufacturing_order_number',
        'mold_type_id',
        'quantity',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function moldType(): BelongsTo
    {
        return $this->belongsTo(MoldType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}

