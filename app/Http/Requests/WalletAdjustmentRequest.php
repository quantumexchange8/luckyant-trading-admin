<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletAdjustmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required'],
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
            'amount' => 'Adjustment',
            'description' => 'Description',
        ];
    }
}
