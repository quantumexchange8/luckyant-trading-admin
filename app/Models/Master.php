<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Master extends Model implements HasMedia
{
    use SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'category',
        'type',
        'strategy_type',
        'max_fund_percentage',
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
        'join_period',
        'signal_status',
        'delivery_requirement',
        'is_public',
        'status',
        'total_subscribers',
        'max_drawdown',
        'management_fee',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function trading_account(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class, 'master_id', 'id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'master_id', 'id');
    }

    public function tradingUser(): BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'meta_login', 'meta_login');
    }

    public function masterManagementFee(): HasMany
    {
        return $this->hasMany(MasterManagementFee::class, 'master_id', 'id');
    }

    public function masterSubscriptionPackage(): HasMany
    {
        return $this->hasMany(MasterSubscriptionPackage::class, 'master_id', 'id');
    }

    public function active_copy_trades(): HasMany
    {
        return $this->hasMany(Subscriber::class, 'master_id', 'id')
            ->where('status', 'Subscribing');
    }

    public function active_pamm(): HasMany
    {
        return $this->hasMany(PammSubscription::class, 'master_id', 'id')
            ->where('status', 'Active');
    }

    public function close_trades(): HasMany
    {
        return $this->hasMany(TradeHistory::class, 'meta_login', 'meta_login')
            ->where('trade_status', 'Closed');
    }

    public function leaders(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, MasterToLeader::class, 'master_id', 'id', 'id', 'user_id');
    }

    public function master_term(): HasOne
    {
        return $this->hasOne(MasterTerms::class, 'master_id', 'id');
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
                'category',
                'type',
                'strategy_type',
                'max_fund_percentage',
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
                'join_period',
                'signal_status',
                'delivery_requirement',
                'is_public',
                'status',
                'total_subscribers',
                'max_drawdown',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($master) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} master request with ID: {$master->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
