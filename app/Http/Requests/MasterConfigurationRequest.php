<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['required'],
            'type' => ['required_if:category,pamm'],
            'min_investment' => ['required', 'numeric'],
            'sharing_profit' => ['required', 'numeric'],
            'market_profit' => ['required', 'numeric'],
            'company_profit' => ['required', 'numeric'],
//            'subscription_fee' => ['required', 'numeric'],
//            'signal_status' => ['required'],
            'estimated_monthly_returns' => ['required'],
            'estimated_lot_size' => ['required'],
            'join_period' => ['required'],
            'extra_fund' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
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
            'category' => trans('public.type'),
            'type' => trans('public.pamm_type'),
            'min_investment' => trans('public.min_investment'),
            'sharing_profit' => trans('public.shared'),
            'market_profit' => trans('public.market'),
            'company_profit' => trans('public.company'),
//            'subscription_fee' => 'Subscription Fee (Month)',
//            'signal_status' => 'Trade Signal Status',
            'estimated_monthly_returns' => trans('public.estimated_monthly_returns'),
            'estimated_lot_size' => trans('public.estimated_lot_size'),
            'join_period' => trans('public.join_period'),
            'extra_fund' => trans('public.extra_fund'),
        ];
    }
}
