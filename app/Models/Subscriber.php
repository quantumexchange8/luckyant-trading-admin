<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriber extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'initial_meta_balance',
        'initial_subscription_fee',
        'transaction_id',
        'master_id',
        'master_meta_login',
        'roi_period',
        'subscribe_amount',
        'subscription_id',
        'status',
        'auto_renewal',
        'approval_date',
        'unsubscribe_date',
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

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'master_meta_login', 'meta_login');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $subscriber = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('subscriber')
            ->logOnly([
                'id',        
                'user_id',
                'trading_account_id',
                'meta_login',
                'initial_meta_balance',
                'initial_subscription_fee',
                'transaction_id',
                'master_id',
                'master_meta_login',
                'roi_period',
                'subscribe_amount',
                'subscription_id',
                'status',
                'auto_renewal',
                'approval_date',
                'unsubscribe_date',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($subscriber) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} subscriber with ID: {$subscriber->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
