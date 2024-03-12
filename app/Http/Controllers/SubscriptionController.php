<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Subscription;
use App\Models\Transaction;
use Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    //
    public function subscription()
    {
        return Inertia::render('Subscription/Subscription');
    }

    public function getPendingSubscriber(Request $request)
    {

        $pendingSubscriber = Subscription::query()
            ->with(['user', 'master', 'master.user'])
            ->where('status', 'Pending');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $pendingSubscriber->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
                })
                ->orWhereHas('master', function ($master) use ($search) {
                    $master->where('meta_login', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $pendingSubscriber->whereBetween('created_at', [$start_date, $end_date]);
        }

        $results = $pendingSubscriber->latest()->paginate(10);

        return response()->json($results);
    }

    public function approveSubscribe(Request $request)
    {
        dd($request->all());

        return redirect()->back()
            ->with('title', 'Success approve')
            ->with('success', 'Approve this subscription');
    }

    public function rejectSubscribe(Request $request)
    {

        $request->validate([
            'remarks' => ['required'],
        ]);

        

        $reject = Subscription::find($request->id);
        $transactionId = Transaction::find($request->transactionId);
        
        $reject->update([
            'status' => 'Rejected',
            'approval_date' => now(),
            'remarks' => $request->remarks,
            'handle_by' => Auth::user()->id,
        ]);

        $transactionId->update([
            'status' => 'Rejected',
            'remarks' => $request->remarks,
            'handle_by' => Auth::user()->id,
        ]);

        return redirect()->back()
            ->with('title', 'Success rejrected')
            ->with('rejected', 'Rejected this subscription');
    }

    public function getHistorySubscriber(Request $request)
    {
        $historySubscriber = Subscription::query()
            ->with(['user', 'master', 'master.user', 'transaction'])
            ->whereNot('status', 'Pending');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $historySubscriber->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
                })
                ->orWhereHas('master', function ($master) use ($search) {
                    $master->where('meta_login', 'like', $search);
                })
                ->orWhere('meta_login', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $historySubscriber->whereBetween('created_at', [$start_date, $end_date]);
        }

        $results = $historySubscriber->latest()->paginate(10);

        return response()->json($results);
    }
}
