<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeLeverage;
use App\Models\AccountTypeToLeader;
use App\Models\Country;
use App\Models\Master;
use App\Models\SettingLeverage;
use App\Models\SettingRank;
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

    public function getLeverages()
    {
        $settingLeverages = SettingLeverage::where('status', 'Active')
            ->get();

        return response()->json($settingLeverages);
    }

    public function getAccountTypes()
    {
        $authUser = Auth::user();

        $query = AccountTypeToLeader::query();

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

        $account_type_ids = $query->pluck('account_type_id')
            ->toArray();

        $accountTypes = AccountType::select([
            'id',
            'name',
            'slug'
        ])
            ->whereIn('id', $account_type_ids)
            ->get();

        return response()->json($accountTypes);
    }

    public function getLeveragesByAccountType(Request $request)
    {
        $accountTypes = AccountTypeLeverage::with('leverage')
            ->where('account_type_id', $request->account_type_id)
            ->get();

        return response()->json($accountTypes);
    }

    public function getCountries()
    {
        $countries = Country::select([
            'id',
            'name',
            'iso2',
            'phone_code',
            'nationality',
            'translations',
        ])
            ->get();

        return response()->json($countries);
    }

    public function getRanks()
    {
        $ranks = SettingRank::select([
            'id',
            'name'
        ])
        ->get();

        return response()->json($ranks);
    }

    public function getUsers(Request $request)
    {
        $query = User::whereNotIn('role', [
            'super-admin',
            'admin'
        ])
            ->select([
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

        if ($request->user_type == 'leader') {
            $query->where('leader_status', 1);
        } elseif ($request->user_type == 'user') {
            $query->where('leader_status', 0);
        }

        $users = $query->get();

        return response()->json($users);
    }
}
