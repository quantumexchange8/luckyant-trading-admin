<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BalanceAdjustmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_type' => ['required'],
            'fund_type' => ['required'],
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
            'fund_type' => 'Fund Type',
            'amount' => 'Amount',
            'description' => 'Description',
        ];
    }
}
