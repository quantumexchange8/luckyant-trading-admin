<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionProfitHistory extends Model
{
    use SoftDeletes;

    protected $table = 'subscriptions_profit_histories';

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function copy_trade_subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function pamm_subscription(): BelongsTo
    {
        return $this->belongsTo(PammSubscription::class, 'subscription_id', 'id');
    }
}
