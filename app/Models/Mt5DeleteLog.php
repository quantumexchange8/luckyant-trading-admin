<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mt5DeleteLog extends Model
{
    protected $table = 'mt5_delete_log';

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'type',
        'account_created_at',
        'account_balance',
        'remarks',
        'handle_by',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function trading_account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

}
