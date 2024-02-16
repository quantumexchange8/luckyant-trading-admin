<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterRequest;
use Carbon\Carbon;
use Inertia\Inertia;

class MasterController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('Master/Master');
    }

    public function getMaster(Request $request, $type)
    {
        $query = MasterRequest::query()
            ->with(['user:id,name,email', 'trading_account_id'])
            ->where('status', $type);
        
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->WhereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                         ->orWhere('email', 'like', $search);
                })
                    ->orWhere('trading_account_id', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('filter')) {
            $filter = $request->input('filter') ;
            $query->where(function ($q) use ($filter) {
                $q->where('status', $filter);
            });
        }

        $results = $query->latest()->paginate(10);

        // $results->each(function ($user_deposit) {
        //     $user_deposit->user->profile_photo_url = $user_deposit->user->getFirstMediaUrl('profile_photo');
        // });
        // dd($query);

        return response()->json([$type => $results]);
    }
}
