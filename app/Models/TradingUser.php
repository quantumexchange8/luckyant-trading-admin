<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TradingUser extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'commission_daily' => 'decimal:2',
        'commission_montly' => 'decimal:2',
        'balance_prev_day' => 'decimal:2',
        'balance_prev_month' => 'decimal:2',
        'equity_prev_day' => 'decimal:2',
        'equity_prev_month' => 'decimal:2',
        'created_at' => 'datetime:Y-m-d',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $tradingUser = $this->fresh();
    
        return LogOptions::defaults()
            ->useLogName('trading_user')
            ->logOnly([
                'id',
                'user_id',
                'meta_login',
                'meta_group',
                'name',
                'country',
                'zip_code',
                'address',
                'phone',
                'email',
                'phone_password',
                'leverage',
                'main_password',
                'invest_password',
                'cert_serial_number',
                'rights',
                'mq_id',
                'registration',
                'last_access',
                'last_pass_change',
                'last_ip',
                'city',
                'state',
                'company',
                'account',
                'account_type',
                'language',
                'client_id',
                'meta_id',
                'status',
                'comment',
                'color',
                'agent',
                'balance',
                'demo_fund',
                'credit',
                'interest_rate',
                'commission_daily',
                'commission_monthly',
                'balance_prev_day',
                'balance_prev_month',
                'equity_prev_day',
                'equity_prev_month',
                'trade_accounts',
                'trade_accounts_currency',
                'trade_accounts_platform',
                'trade_accounts_type',
                'lead_campaign',
                'lead_source',
                'remarks',
                'allow_trade',
                'allow_change_pass',
                'module',
                'category',
                'acc_status',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($tradingUser) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} trading user with ID: {$tradingUser->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
