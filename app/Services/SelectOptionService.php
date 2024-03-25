<?php

namespace App\Services;

use App\Models\Country;
use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Models\Wallet;

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
        return Transaction::distinct()->pluck('transaction_type')->map(function ($transactionType) {
            return [
                'value' => $transactionType,
                'label' => $transactionType,
            ];
        });
    }
}
