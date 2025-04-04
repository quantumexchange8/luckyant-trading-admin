<?php

namespace App\Http\Controllers;

use App\Models\SettingRank;
use App\Models\WorldPoolAllocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $allocation_amount = WorldPoolAllocation::whereDate('allocation_date', '<=', Carbon::now())
            ->sum('allocation_amount');

        $world_pool = [];

        foreach ($ranks as $index => $rank) {
            if ($index === 0) {
                $world_pool[$rank] = $allocation_amount;
            } else {
                $world_pool[$rank] = $allocation_amount * 2;
            }
        }

        return Inertia::render('WorldPool/Allocation/WorldPoolAllocation', [
            'last_allocate_date' => WorldPoolAllocation::orderByDesc('allocation_date')->first()->allocation_date,
            'world_pool' => $world_pool
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
            'pool_allocations' => ['required'],
            'pool_allocations.*.pool_amount' => ['required', 'numeric'],
        ])->setAttributeNames([
            'pool_allocations' => trans('public.date'),
            'pool_allocations.*.pool_amount' => trans('public.pool_amount'),
        ])->validate();

        $pool_allocations = $request->pool_allocations;

        foreach ($pool_allocations as $pool_allocation) {
            WorldPoolAllocation::create([
                'allocation_date' => $pool_allocation['full_date'],
                'allocation_amount' => $pool_allocation['pool_amount'],
            ]);
        }

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
