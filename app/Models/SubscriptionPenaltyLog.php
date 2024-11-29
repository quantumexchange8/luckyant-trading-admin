<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPenaltyLog extends Model
{
    protected $table = 'subscription_penalty_log';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function trading_account(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id', 'id');
    }

    public function subscription_batch(): BelongsTo
    {
        return $this->belongsTo(SubscriptionBatch::class, 'subscription_batch_id', 'id');
    }
}
