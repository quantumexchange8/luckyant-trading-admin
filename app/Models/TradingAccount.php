<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TradingAccount extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit' => 'decimal:2',
        'margin' => 'decimal:2',
        'margin_free' => 'decimal:2',
        'margin_level' => 'decimal:2',
        'profit' => 'decimal:2',
        'storage' => 'decimal:2',
        'commission' => 'decimal:2',
        'floating' => 'decimal:2',
        'equity' => 'decimal:2',
        'so_level' => 'decimal:2',
        'so_equity' => 'decimal:2',
        'so_margin' => 'decimal:2',
        'assets' => 'decimal:2',
        'liabilities' => 'decimal:2',
        'blocked_commission' => 'decimal:2',
        'blocked_profit' => 'decimal:2',
        'margin_initial' => 'decimal:2',
        'margin_maintenance' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $tradingAccount = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('trading_account')
            ->logOnly([
                'id',
                'user_id',
                'meta_login',
                'account_type',
                'currency_digits',
                'balance',
                'demo_fund',
                'credit',
                'margin',
                'margin_free',
                'margin_level',
                'margin_leverage',
                'profit',
                'storage',
                'commission',
                'floating',
                'equity',
                'so_activation',
                'so_time',
                'so_level',
                'so_equity',
                'so_margin',
                'assets',
                'liabilities',
                'blocked_commission',
                'blocked_profit',
                'margin_initial',
                'margin_maintenance',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($tradingAccount) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} trading account with ID: {$tradingAccount->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'account_type', 'id');
    }
   public function tradingUser(): HasOne
   {
       return $this->hasOne(TradingUser::class, 'meta_login', 'meta_login');
   }

    public function subscriber(): HasOne
    {
        return $this->hasOne(Subscriber::class, 'trading_account_id', 'id');
    }

    public function active_copy_trade(): HasMany
    {
        return $this->hasMany(SubscriptionBatch::class, 'trading_account_id', 'id')->where('status', 'Active');
    }

    public function active_pamm(): HasMany
    {
        return $this->hasMany(PammSubscription::class, 'meta_login', 'meta_login')->where('status', 'Active');
    }
}
