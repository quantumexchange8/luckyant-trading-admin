<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Auth;

class SettingPaymentMethod extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'payment_method',
        'payment_account_name',
        'payment_platform_name',
        'account_no',
        'bank_swift_code',
        'bank_code',
        'bank_code_type',
        'country',
        'currency',
        'crypto_network',
        'status',
        'handle_by'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'handle_by', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $paymentDetail = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('setting_payment')
            ->logOnly([
                'id', 
                'payment_method', 
                'payment_account_name', 
                'payment_platform_name', 
                'account_no', 
                'country', 
                'bank_swift_code', 
                'bank_code', 
                'crypto_network',
                'status'
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($paymentDetail) {
                return Auth::user()->name . " has {$eventName} payment detail of ID {$paymentDetail->id}.";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
