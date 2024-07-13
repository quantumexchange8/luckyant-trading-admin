<?php

namespace App\Services;

use App\Models\Country;
use App\Models\PaymentAccount;
use App\Models\SettingSettlementPeriod;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\SettingLeverage;
use Spatie\Permission\Models\Role;

class SelectOptionService
{
    public function getCountries()
    {
        return Country::all()->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
            ];
        });
    }

    public function getNationalities()
    {
        return Country::all()->map(function ($country) {
            return [
                'id' => $country->id,
                'value' => $country->nationality,
                'label' => $country->nationality,
            ];
        });
    }

    public function getTransactionType()
    {
        return Transaction::whereNotIn('transaction_type', ['Management Fee', 'Settlement'])
            ->distinct()
            ->pluck('transaction_type')
            ->map(function ($transactionType) {
                return [
                    'value' => $transactionType,
                    'label' => trans('public.' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $transactionType))),
                ];
            });
    }

    public function getActiveLeverageSelection(): \Illuminate\Support\Collection
    {
        $settingLeverages = SettingLeverage::where('status', 'Active');

        return $settingLeverages->get()->map(function ($settingLeverage) {
            return [
                'label' => $settingLeverage->display,
                'value' => $settingLeverage->value,
            ];
        });
    }

    public function getRoles(): \Illuminate\Support\Collection
    {
        return Role::whereNotIn('name', ['super-admin', 'leader'])->orderBy('id')->get()->map(function ($role) {
            return [
                'label' => trans('public.' . $role->name),
                'value' => $role->id,
            ];
        });
    }

    public function getSettlementPeriods(): \Illuminate\Support\Collection
    {
        return SettingSettlementPeriod::where('status', 'Active')->get()->map(function ($period) {
            return [
                'label' => $period->value . ' Days',
                'value' => $period->value,
            ];
        });
    }
}
