<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'postal_code',
        'address',
        'address_number',
        'address_complement',
        'neighborhood',
        'city',
        'state',
        'description',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected function postalCode(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value ? preg_replace('/\D+/', '', $value) : null,
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function formattedPostalCode(): ?string
    {
        if (!$this->postal_code) {
            return null;
        }

        return Str::of($this->postal_code)
            ->padLeft(8, '0')
            ->replaceMatches('/(\d{5})(\d{3})/', '$1-$2')
            ->value();
    }
}
