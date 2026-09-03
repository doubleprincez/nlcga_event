<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    protected $table = 'users';
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        'is_admin',
        'is_staff',
        'isApproved',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_staff' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isStaff(): bool
    {
        return (bool) $this->is_staff || (bool) $this->is_admin;
    }

    public function isApprovedMember(): bool
    {
        return strtoupper((string) ($this->isApproved ?? '')) === 'YES';
    }

    public function syncRoleFlags(?string $targetRole = null): void
    {
        $role = $targetRole ?: (string) $this->role;
        $roleStr = strtolower(trim($role));

        if (in_array($roleStr, ['admin', 'superadmin', 'super_admin', 'administrator']) || (int)$this->is_admin === 1) {
            $this->role = 'admin';
            $this->is_admin = 1;
            $this->is_staff = 1;
            $this->isApproved = 'YES';
        } elseif (in_array($roleStr, ['staff', 'scanner', 'management']) || (int)$this->is_staff === 1) {
            $this->role = 'staff';
            $this->is_admin = 0;
            $this->is_staff = 1;
            $this->isApproved = 'YES';
        } else {
            $this->role = 'member';
            $this->is_admin = 0;
            $this->is_staff = 0;
        }

        $this->save();
    }

    public function getNameAttribute($value)
    {
        return $value ?: ($this->attributes['username'] ?? 'User');
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }
}

