<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\SettingSettlementPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;

class SelectOptionController extends Controller
{
    public function getMasters(Request $request)
    {
        $query = Master::with('tradingUser:id,name,meta_login')
            ->where('category', $request->category)
            ->select('id', 'meta_login');

        $authUser = Auth::user();

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $query->whereIn('user_id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $query->whereIn('user_id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $query->whereIn('user_id', []);
        }

        $masters = $query
            ->get();

        return response()->json($masters);
    }

    public function getLeaders()
    {
        $query = User::select([
            'id',
            'name',
            'email'
        ]);

        $authUser = Auth::user();

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $query->whereIn('id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $query->whereIn('id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $query->whereIn('id', []);
        }

        $leaders = $query
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
