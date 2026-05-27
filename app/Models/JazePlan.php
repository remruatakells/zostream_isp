<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JazePlan extends Model
{
    use HasFactory;

    protected $primaryKey = 'group_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'group_name',
        'profile_id',
        'profile_name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
