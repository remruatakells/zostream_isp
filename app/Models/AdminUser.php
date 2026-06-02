<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'api_token',
        'role',
        'branch_id',
        'jaze_user_id',
        'jaze_username',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isAdminRole(): bool
    {
        return in_array($this->role, ['super_admin', 'branch_admin', 'staff', 'support'], true);
    }

    public function isCustomerRole(): bool
    {
        return $this->role === 'user';
    }
}
