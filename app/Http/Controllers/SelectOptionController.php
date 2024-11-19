<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\SettingSettlementPeriod;
use App\Models\User;

class SelectOptionController extends Controller
{
    public function getMasters()
    {
        $masters = Master::with('tradingUser:id,name,meta_login')
            ->where('type', 'CopyTrade')
            ->select('id', 'meta_login')
            ->get();

        return response()->json($masters);
    }

    public function getLeaders()
    {
        $leaders = User::select([
            'id',
            'name',
            'email'
        ])
            ->where('leader_status', 1)
            ->get();

        return response()->json($leaders);
    }

    public function getSettlementPeriods()
    {
        $periods = SettingSettlementPeriod::where('status', 'Active')
            ->select('label', 'value')
            ->get();

        return response()->json($periods);
    }
}
