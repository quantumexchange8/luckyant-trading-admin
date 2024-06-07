<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Master extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'type',
        'min_join_equity',
        'sharing_profit',
        'market_profit',
        'company_profit',
        'estimated_monthly_returns',
        'estimated_lot_size',
        'subscription_fee',
        'total_fund',
        'extra_fund',
        'roi_period',
        'signal_status',
        'is_public',
        'status',
        'total_subscribers',
        'max_drawdown',
        'management_fee',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function trading_account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function subscribers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscriber::class, 'master_id', 'id');
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class, 'master_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'meta_login', 'meta_login');
    }

    public function masterManagementFee(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(MasterManagementFee::class, 'master_id', 'id');
    }

    public function masterLeaders(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(MasterLeader::class, 'master_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $master = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('master')
            ->logOnly([
                'id',
                'user_id',
                'trading_account_id',
                'meta_login',
                'min_join_equity',
                'sharing_profit',
                'market_profit',
                'company_profit',
                'estimated_monthly_returns',
                'estimated_lot_size',
                'subscription_fee',
                'management_fee',
                'extra_fund',
                'total_fund',
                'total_subscribers',
                'max_drawdown',
                'roi_period',
                'master_id',
                'is_public',
                'signal_status',
                'status'
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($master) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} master request with ID: {$master->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
