<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineDowntime extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'reason_id',
        'started_at',
        'ended_at',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'status' => 'string',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(Reason::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBetween($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('started_at', '>=', $from);
        }
        if ($to) {
            $query->where('ended_at', '<=', $to);
        }
        return $query;
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('notes', 'like', "%{$term}%")
              ->orWhereHas('machine', function ($mq) use ($term) {
                  $mq->where('name', 'like', "%{$term}%");
              })
              ->orWhereHas('reason', function ($rq) use ($term) {
                  $rq->where('name', 'like', "%{$term}%");
              });
        });
    }
}

