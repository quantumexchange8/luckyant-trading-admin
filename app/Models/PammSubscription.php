<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PammSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'meta_login',
        'master_id',
        'master_meta_login',
        'subscription_amount',
        'type',
        'strategy_type',
        'transaction_id',
        'subscription_number',
        'subscription_period',
        'settlement_period',
        'subscription_fee',
        'settlement_date',
        'expired_date',
        'termination_date',
        'max_out_amount',
        'approval_date',
        'status',
        'remarks',
        'handle_by',
    ];

    protected $casts = [
        'settlement_date' => 'datetime',
        'expired_date' => 'datetime',
        'approval_date' => 'datetime',
        'termination_date' => 'datetime',
    ];

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function tradingUser(): BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'master_meta_login', 'meta_login');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(MasterSubscriptionPackage::class, 'subscription_package_id', 'id');
    }
}
