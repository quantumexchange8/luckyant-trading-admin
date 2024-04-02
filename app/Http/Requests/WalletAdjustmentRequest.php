<?php

namespace App\Http\Requests;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class WalletAdjustmentRequest extends FormRequest
{
    public function rules(): array
    {
        $wallet = Wallet::find($this->wallet_id);

        return [
            'transaction_type' => $wallet->type == 'cash_wallet' ? ['required'] : ['nullable'],
            'amount' => ['required', 'min:1'],
            'description' => ['required']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'transaction_type' => 'Transaction Type',
            'amount' => 'Amount',
            'description' => 'Description',
        ];
    }
}
