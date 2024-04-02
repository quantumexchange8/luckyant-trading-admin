<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReportController extends Controller
{
    public function trading_rebate()
    {
        return Inertia::render('Report/TradingRebate/TradingRebate');
    }

    public function getTradeHistory()
    {

    }
}
