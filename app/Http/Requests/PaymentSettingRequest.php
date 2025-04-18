<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'payment_method' => ['required'],
            'payment_account_name' => ['required'],
            'payment_platform_name' => ['required'],
            'account_no' => ['required'],
            'leaders' => ['required'],
        ];

        if ($this->payment_method == 'Bank') {
            $rules['bank_swift_code'] = ['required'];
            $rules['country'] = ['required'];
            $rules['payment_logo'] = ['nullable', 'image'];
        } else {
            $rules['network'] = ['required'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'payment_account_name' => $this->payment_method == 'Bank' ? 'Bank Account name' : 'Wallet address',
            'payment_platform_name' => $this->payment_method == 'Bank' ? 'Bank name' : 'Tether',
            'account_no' => $this->payment_method == 'Bank' ? 'Account Number' : 'Wallet Address',
            'bank_swift_code' => 'Bank Swift Code',
            'country' => 'Country',
            'leaders' => trans('public.visible_to'),
            'payment_logo' => $this->payment_method == 'Bank' ? 'Bank Logo' : 'Crypto Logo',
        ];
    }
}
