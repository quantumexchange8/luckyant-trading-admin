<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountType extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'delete' => 'boolean',
        'minimum_deposit' => 'decimal:2',
    ];

    public function metaGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    public function visibleLeaders(): HasMany
    {
        return $this->hasMany(AccountTypeToLeader::class, 'account_type_id', 'id');
    }

    public function leverages(): HasMany
    {
        return $this->hasMany(AccountTypeLeverage::class, 'account_type_id', 'id');
    }
}
