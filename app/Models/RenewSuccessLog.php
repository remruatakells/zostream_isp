<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewSuccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'admin_user_id',
        'jaze_user_id',
        'jaze_username',
        'account_id',
        'status',
        'payload',
        'renewed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'renewed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}
