<?php

namespace App\Models;

use App\Observers\ClientObserver;
use DomainException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'person_type',
        'document',
        'observations',
        'contact_name',
        'contact_phone_primary',
        'contact_phone_secondary',
        'contact_email',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'person_type' => 'string',
        'status' => 'string',
    ];

    protected function document(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value ? preg_replace('/\D+/', '', $value) : null,
        );
    }

    protected function contactPhonePrimary(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value ? preg_replace('/\D+/', '', $value) : null,
        );
    }

    protected function contactPhoneSecondary(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value ? preg_replace('/\D+/', '', $value) : null,
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function formattedDocument(): string
    {
        $digits = $this->document;

        if ($this->person_type === 'company') {
            return Str::of($digits)
                ->padLeft(14, '0')
                ->replaceMatches('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5')
                ->value();
        }

        return Str::of($digits)
            ->padLeft(11, '0')
            ->replaceMatches('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4')
            ->value();
    }

    public function formattedPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $length = strlen($phone);

        return match (true) {
            $length === 11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone),
            $length === 10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone),
            default => $phone,
        };
    }

    protected static function booted(): void
    {
        static::observe(ClientObserver::class);

        static::deleting(function (Client $client): void {
            if ($client->orders()->exists()) {
                Log::warning('Tentativa de exclusão de cliente com pedidos bloqueada', [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'user_id' => Auth::id(),
                ]);
                throw new DomainException(__('client.delete_blocked_has_orders'));
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
