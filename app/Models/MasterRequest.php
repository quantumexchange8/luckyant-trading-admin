<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterRequest extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'status',
        'approval_date',
        'remarks',
        'handle_by',
    ];

    protected $casts = [
        'approval_date' => 'date',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function trading_account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $masterRequest = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('master_request')
            ->logOnly([
                'id',
                'user_id',
                'trading_account_id',
                'min_join_equity',
                'sharing_profit',
                'subscription_fee',
                'roi_period',
                'status',
                'approval_date',
                'remarks',
                'handle_by',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($masterRequest) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} master request with ID: {$masterRequest->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
