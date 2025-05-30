<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionBatch extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'meta_balance',
        'real_fund',
        'demo_fund',
        'master_id',
        'master_meta_login',
        'type',
        'strategy_type',
        'subscriber_id',
        'subscription_id',
        'subscription_number',
        'subscription_period',
        'transaction_id',
        'subscription_fee',
        'max_out_amount',
        'settlement_start_date',
        'settlement_date',
        'termination_date',
        'status',
        'auto_renewal',
        'approval_date',
        'remarks',
        'claimed_profit',
        'handle_by',
    ];

    protected $casts = [
        'termination_date' => 'datetime',
        'settlement_date' => 'datetime',
        'approval_date' => 'datetime',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function tradingUser(): BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'master_meta_login', 'meta_login');
    }

    public function subscription_penalty(): HasOne
    {
        return $this->hasOne(SubscriptionPenaltyLog::class, 'subscription_batch_id', 'id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $subscriptionBatch = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('subscription_batch')
            ->logOnly([
                'id',
                'user_id',
                'trading_account_id',
                'meta_login',
                'meta_balance',
                'real_fund',
                'demo_fund',
                'master_id',
                'master_meta_login',
                'type',
                'subscriber_id',
                'subscription_id',
                'subscription_number',
                'subscription_period',
                'transaction_id',
                'subscription_fee',
                'settlement_start_date',
                'settlement_date',
                'termination_date',
                'status',
                'auto_renewal',
                'approval_date',
                'remarks',
                'claimed_profit',
                'handle_by',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($subscriptionBatch) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} subscription batch with ID: {$subscriptionBatch->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
