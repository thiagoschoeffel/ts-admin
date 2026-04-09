<?php

namespace App\Models;

use App\Observers\ReasonObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reason extends Model
{
    use HasFactory;

    protected $fillable = [
        'reason_type_id',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected static function booted(): void
    {
        static::observe(ReasonObserver::class);
    }

    public function reasonType(): BelongsTo
    {
        return $this->belongsTo(ReasonType::class);
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

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhereHas('reasonType', function ($rt) use ($term) {
                    $rt->where('name', 'like', "%{$term}%");
                });
        });
    }
}
