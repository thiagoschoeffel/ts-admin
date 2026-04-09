<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\LeadStatus;
use App\Enums\LeadSource;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'source',
        'status',
        'owner_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'source' => LeadSource::class,
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function interactions()
    {
        return $this->hasMany(LeadInteraction::class)->orderBy('interacted_at', 'desc');
    }
}
