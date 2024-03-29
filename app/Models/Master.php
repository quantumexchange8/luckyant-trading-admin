<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Master extends Model
{
    use SoftDeletes;

    protected $fillable = [
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
        'total_fund',
        'extra_fund',
        'roi_period',
        'signal_status',
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

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'meta_login', 'meta_login');
    }
}
