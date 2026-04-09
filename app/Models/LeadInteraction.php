<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'type',
        'interacted_at',
        'description',
        'created_by_id',
    ];

    protected $casts = [
        'interacted_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function getTypeLabelAttribute()
    {
        return match ($this->type) {
            'phone_call' => 'Ligação Telefônica',
            'email' => 'E-mail',
            'meeting' => 'Reunião',
            'message' => 'Mensagem',
            'visit' => 'Visita',
            'other' => 'Outro',
            default => $this->type,
        };
    }
}
