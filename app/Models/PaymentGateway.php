<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGateway extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'platform',
        'environment',
        'payment_url',
        'payment_app_name',
        'secret_key',
        'secondary_key',
        'edited_by',
        'status',
    ];

    // Relations
    public function successTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payment_gateway_id', 'id')->where('status', 'Success');
    }

    public function visibleLeaders(): HasMany
    {
        return $this->hasMany(PaymentGatewayToLeader::class, 'payment_gateway_id', 'id');
    }
}
