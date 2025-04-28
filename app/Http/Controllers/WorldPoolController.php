<?php

namespace App\Http\Controllers;

use App\Models\PammSubscription;
use App\Models\SettingRank;
use App\Models\Subscription;
use App\Models\WorldPoolAllocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WorldPoolController extends Controller
{
    public function index()
    {
        $ranks = SettingRank::where('position', '>', 4)
            ->limit(2)
            ->get()
            ->pluck('name')
            ->toArray();

        $query = WorldPoolAllocation::whereDate('allocation_date', '<=', Carbon::now());

        $world_pool = [];

        foreach ($ranks as $index => $rank) {
            if ($index === 0) {
                $world_pool[$rank] = $query->sum('world_pool_amount');
            } else {
                $world_pool[$rank] = $query->sum('world_pool_amount') * 2;
            }
        }

        $active_subscriptions_capital = Subscription::where('status', 'Active')
            ->sum('meta_balance');

        $active_pamm_capital = PammSubscription::with('master:id,involves_world_pool')
            ->where('status', 'Active')
            ->whereHas('master', function ($q) {
                $q->where('involves_world_pool', 1);
            })
            ->sum('subscription_amount');

        return Inertia::render('WorldPool/Allocation/WorldPoolAllocation', [
            'active_pamm_capital' => (float) $active_pamm_capital,
            'active_subscriptions_capital' => (float) $active_subscriptions_capital,
            'extra_fund_sum' => (float) $query->sum('allocation_amount'),
            'world_pool' => $world_pool,
        ]);
    }

    public function getAllocationData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = WorldPoolAllocation::query();

            //date filter
            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay(); //add day to ensure capture entire day
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('allocation_date', [$start_date, $end_date]);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('allocation_date');
            }

            $allocations = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $allocations,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function allocateWorldPool(Request $request)
    {
        Validator::make($request->all(), [
            'allocation_date' => ['required'],
            'allocation_amount' => ['required'],
        ])->setAttributeNames([
            'allocation_date' => trans('public.date'),
            'allocation_amount' => trans('public.amount'),
        ])->validate();

        $last_date = WorldPoolAllocation::orderByDesc('allocation_date')->first();
        $allocation_date = Carbon::parse($request->allocation_date)->addHours(8);

        if ($last_date) {
            // If same as existing allocation_date
            if ($allocation_date->isSameDay($last_date->allocation_date)) {
                throw ValidationException::withMessages([
                    'allocation_date' => trans('public.date_existed')
                ]);
            }

            // If smaller than today
            if (!$allocation_date->greaterThan(Carbon::today())) {
                throw ValidationException::withMessages([
                    'allocation_date' => trans('public.date_cannot_be_past')
                ]);
            }

            // If smaller than today
            if ($allocation_date == Carbon::today()) {
                throw ValidationException::withMessages([
                    'allocation_date' => trans('public.date_cannot_be_today')
                ]);
            }
        }

        WorldPoolAllocation::create([
            'allocation_date' => $allocation_date,
            'allocation_amount' => $request->allocation_amount,
        ]);

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_allocate_world_pool"),
            'type' => 'success',
        ]);
    }

    public function updateWorldPool(Request $request)
    {
        Validator::make($request->all(), [
            'allocation_amount' => ['required', 'numeric'],
        ])->setAttributeNames([
            'allocation_amount' => trans('public.pool_amount'),
        ])->validate();

        $allocation = WorldPoolAllocation::find($request->allocation_id);

        $allocation->update([
           'allocation_amount' => $request->allocation_amount,
        ]);

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_world_pool"),
            'type' => 'success',
        ]);
    }
}
