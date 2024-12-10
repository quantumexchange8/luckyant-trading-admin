<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'balance',
        'wallet_address',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $wallet = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('wallet')
            ->logOnly([
                'id',
                'user_id',
                'name',
                'type',
                'balance',
                'wallet_address',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($wallet) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} wallet with ID: {$wallet->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
