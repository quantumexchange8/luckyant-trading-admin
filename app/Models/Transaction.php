<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'user_id',
        'category',
        'transaction_type',
        'fund_type',
        'from_wallet_id',
        'to_wallet_id',
        'from_meta_login',
        'to_meta_login',
        'ticket',
        'transaction_number',
        'payment_account_id',
        'setting_payment_method_id',
        'payment_method',
        'to_wallet_address',
        'txn_hash',
        'amount',
        'conversion_rate',
        'transaction_charges',
        'transaction_amount',
        'new_wallet_amount',
        'status',
        'approval_at',
        'comment',
        'remarks',
        'handle_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approval_at' => 'datetime',
        ];
    }

    public function from_wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id', 'id');
    }

    public function to_wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id', 'id');
    }

    public function from_account(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'from_meta_login', 'meta_login');
    }

    public function to_account(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'to_meta_login', 'meta_login');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function payment_account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id', 'id');
    }
    public function setting_payment(): BelongsTo
    {
        return $this->belongsTo(SettingPaymentMethod::class, 'setting_payment_method_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $transaction = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('transaction')
            ->logOnly([
                'id',
                'user_id',
                'category',
                'transaction_type',
                'fund_type',
                'from_wallet_id',
                'to_wallet_id',
                'from_meta_login',
                'to_meta_login',
                'ticket',
                'transaction_number',
                'payment_account_id',
                'setting_payment_method_id',
                'payment_method',
                'to_wallet_address',
                'txn_hash',
                'amount',
                'conversion_rate',
                'transaction_charges',
                'transaction_amount',
                'new_wallet_amount',
                'status',
                'comment',
                'remarks',
                'handle_by',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($transaction) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} transaction with ID: {$transaction->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
