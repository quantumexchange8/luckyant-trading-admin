<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'min_join_equity' => ['required', 'numeric'],
            'sharing_profit' => ['required', 'numeric'],
            'subscription_fee' => ['required', 'numeric'],
            'signal_status' => ['required'],
            'eta_montly_return' => ['required'],
            'eta_lot_size' => ['required'],
            'extra_fund' => ['required'],
            'total_fund' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'min_join_equity' => 'Minimum Equity',
            'sharing_profit' => 'Sharing Profit (%)',
            'subscription_fee' => 'Subscription Fee (Month)',
            'signal_status' => 'Trade Signal Status',
            'eta_montly_return' => 'Estimated Monthly Return',
            'eta_lot_size' => 'Estimated Lot Size',
            'extra_fund' => 'Extra Size',
            'total_fund' => 'Total Fund',
        ];
    }
}
