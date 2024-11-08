<?php

namespace App\Console\Commands;

use App\Models\CurrencyConversionRate;
use AshAllenDesign\LaravelExchangeRates\Classes\ExchangeRate;
use Illuminate\Console\Command;

class UpdateExchangeRateCommand extends Command
{
    protected $signature = 'update:exchange-rate';

    protected $description = 'Update latest exchange rates daily';

    public function handle(): void
    {
        $currencyArrays = CurrencyConversionRate::whereNot('base_currency', 'USDT')->get()->pluck('base_currency')->toArray();

        $exchangeRates = app(ExchangeRate::class);

        $results = $exchangeRates->exchangeRate('USD', $currencyArrays);

        foreach ($results as $currency => $rate) {
            $baseCurrency = CurrencyConversionRate::where('base_currency', $currency)->first();
            $markupRate = $rate * 1.015;

            $baseCurrency->update([
                'deposit_rate' => $markupRate,
                'withdrawal_rate' => $markupRate,
            ]);
        }
    }
}
