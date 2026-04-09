<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::observe(UserObserver::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'status',
        'role',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can perform an action on a model
     */
    public function can($ability, $model = null): bool
    {
        // If user is admin, allow everything
        if ($this->isAdmin()) {
            return true;
        }

        // If no model provided, return false
        if ($model === null) {
            return false;
        }

        // If model is a class string, check general permissions
        if (is_string($model)) {
            $permissions = $this->permissions ?? [];
            $modelName = strtolower(class_basename($model));

            // Map common abilities to permission keys
            $permissionMap = [
                'viewAny' => 'view',
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ];

            $permissionKey = $permissionMap[$ability] ?? $ability;

            // Check if user has wildcard permission for this model
            if (isset($permissions[$modelName]['*']) && $permissions[$modelName]['*']) {
                return true;
            }

            // Check specific permission
            return (bool)($permissions[$modelName][$permissionKey] ?? false);
        }

        // If model is an instance, use the policy directly
        if (is_object($model)) {
            $policyClass = \Illuminate\Support\Facades\Gate::getPolicyFor($model);
            if ($policyClass && method_exists($policyClass, $ability)) {
                $policy = new $policyClass();
                return $policy->{$ability}($this, $model);
            }
        }

        return false;
    }

    /**
     * Send the email verification notification using the app's custom template.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification($this));
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'created_by_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'owner_id');
    }
}
