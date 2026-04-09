<?php

namespace App\Models;

use App\Observers\SiloObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Silo extends Model
{
  use HasFactory;

  protected $table = 'silos';

  protected $fillable = [
    'name',
    'status',
  ];

  protected $casts = [
    'status' => 'string',
  ];

  protected static function booted(): void
  {
    static::observe(SiloObserver::class);
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  public function scopeSearch($query, $term)
  {
    return $query->where('name', 'like', "%{$term}%");
  }
}
