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
            'market_profit' => ['required', 'numeric'],
            'company_profit' => ['required', 'numeric'],
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sharingProfit = $this->input('sharing_profit');
            $marketProfit = $this->input('market_profit');
            $companyProfit = $this->input('company_profit');

            $totalPercentage = $sharingProfit + $marketProfit + $companyProfit;

            if ($totalPercentage > 100) {
                $validator->errors()->add('sharing_profit', 'The sum of Sharing Profit, Market Profit, and Company Profit cannot exceed 100%.');
                $validator->errors()->add('market_profit', '');
                $validator->errors()->add('company_profit', '');
            } else if ($totalPercentage < 100) {
                $validator->errors()->add('sharing_profit', 'The sum of Sharing Profit, Market Profit, and Company Profit must be 100%.');
                $validator->errors()->add('market_profit', '');
                $validator->errors()->add('company_profit', '');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'min_join_equity' => 'Minimum Equity',
            'sharing_profit' => 'Sharing Profit (%)',
            'market_profit' => 'Market Profit (%)',
            'company_profit' => 'Company Profit (%)',
            'subscription_fee' => 'Subscription Fee (Month)',
            'signal_status' => 'Trade Signal Status',
            'eta_montly_return' => 'Estimated Monthly Return',
            'eta_lot_size' => 'Estimated Lot Size',
            'extra_fund' => 'Extra Size',
            'total_fund' => 'Total Fund',
        ];
    }
}
