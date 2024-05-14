<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionRenewalRequest extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'status',
        'approval_date',
        'remarks',
        'handle_by',
    ];

    protected $casts = [
        'approval_date' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $subscriptionRenewalRequest = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('subscription_renewal_requests')
            ->logOnly([
                'id',
                'user_id',
                'subscription_id',
                'status',
                'approval_date',
                'remarks',
                'handle_by',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($subscriptionRenewalRequest) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} subscription renewal request with ID: {$subscriptionRenewalRequest->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
