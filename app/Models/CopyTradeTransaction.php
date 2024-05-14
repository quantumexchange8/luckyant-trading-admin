<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class CopyTradeTransaction extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'subscription_id',
        'master_id',
        'master_meta_login',
        'amount',
        'real_fund',
        'demo_fund',
        'type',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $copyTradeTransaction = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('copy_trade_transaction')
            ->logOnly([
                'id',
                'user_id',
                'trading_account_id',
                'meta_login',
                'subscription_id',
                'master_id',
                'master_meta_login',
                'amount',
                'real_fund',
                'demo_fund',
                'type',
                'status',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($copyTradeTransaction) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} copy trade transaction with ID: {$copyTradeTransaction->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
