<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location',
        'address',
        'status',
        'jaze_api_token',
        'jaze_api_key',
        'jaze_account_id',
    ];

    protected $hidden = [
        'jaze_api_token',
        'jaze_api_key',
    ];

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class);
    }

    public function jazePlans(): HasMany
    {
        return $this->hasMany(JazePlan::class);
    }

    public static function findByJazeCredentials(string $token, string $key): ?self
    {
        return static::query()
            ->where('jaze_api_token', $token)
            ->where('jaze_api_key', $key)
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->where('status', 'active')
            ->first();
    }
}
