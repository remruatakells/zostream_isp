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
    ];

    protected $hidden = [
        'jaze_api_token',
        'jaze_api_key',
    ];

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class);
    }
}
