<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'payment_account_name' => ['required'],
            'payment_platform_name' => ['required'],
            'account_no' => ['required'],
        ];

        if ($this->payment_method == 'Bank') {
            $rules['bank_swift_code'] = ['required'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [
            'payment_account_name' => $this->payment_method == 'Bank' ? 'Bank Account name' : 'Wallet address',
            'payment_platform_name' => $this->payment_method == 'Bank' ? 'Bank name' : 'Tether',
            'account_no' => $this->payment_method == 'Bank' ? 'Account Number' : 'Wallet Address',
            'bank_swift_code' => 'Bank Swift Code',
        ];
    
        return $attributes;
    }
}
