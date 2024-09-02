<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentAccount extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'payment_account_name',
        'payment_platform',
        'payment_platform_name',
        'account_no',
        'bank_branch_address',
        'bank_swift_code',
        'bank_code',
        'bank_code_type',
        'country',
        'currency',
        'status',
    ];

    public function of_country()
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $paymentAccount = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('payment_account')
            ->logOnly([
                'id',
                'user_id',
                'payment_account_name',
                'payment_platform',
                'payment_platform_name',
                'account_no',
                'bank_branch_address',
                'bank_swift_code',
                'bank_code',
                'bank_code_type',
                'country',
                'currency',
                'status',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($paymentAccount) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} payment account with ID: {$paymentAccount->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
