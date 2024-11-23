<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'meta_balance',
        'transaction_id',
        'master_id',
        'type',
        'strategy_type',
        'subscription_number',
        'subscription_period',
        'subscription_fee',
        'next_pay_date',
        'status',
        'remarks',
        'approval_date',
        'expired_date',
        'max_out_amount',
        'handle_by',
    ];

    protected $casts = [
        'next_pay_date' => 'date',
    ];

    public function tradingAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function master(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'meta_login', 'meta_login');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $subscription = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('subscription')
            ->logOnly([
                'id',
                'user_id',
                'trading_account_id',
                'meta_login',
                'meta_balance',
                'transaction_id',
                'master_id',
                'type',
                'subscription_number',
                'subscription_period',
                'subscription_fee',
                'next_pay_date',
                'status',
                'remarks',
                'approval_date',
                'expired_date',
                'handle_by',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($subscription) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} subscription with ID: {$subscription->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
