<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorldPoolAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'allocation_date',
        'allocation_amount',
        'world_pool_amount'
    ];

    protected $casts = [
        'allocation_date' => 'datetime',
    ];
}
