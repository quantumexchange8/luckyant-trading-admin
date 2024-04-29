<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $authUser = \Auth::user();
        $announcements = Announcement::query()
                ->where('type', 'login')
                ->with('media')
                ->latest()
                ->first();

        $totalDeposits = Transaction::where('category', 'wallet')->where('transaction_type', 'Deposit')->where('status', 'Success');
        $totalWithdrawals = Transaction::where('category', 'wallet')->where('transaction_type', 'Withdrawal');
        $pendingTransaction = Transaction::where('status', 'Processing');
        $kyc = User::whereNot('role', 'admin')->where('kyc_approval', 'Pending');

        if ($authUser->hasRole('admin') && $authUser->leader_status == 1) {
            $childrenIds = $authUser->getChildrenIds();
            $childrenIds[] = $authUser->id;
            $totalDeposits->whereIn('user_id', $childrenIds);
            $totalWithdrawals->whereIn('user_id', $childrenIds);
            $pendingTransaction->whereIn('user_id', $childrenIds);
            $kyc->whereIn('id', $childrenIds);
        } elseif ($authUser->hasRole('super-admin')) {
            // Super-admin logic, no need to apply whereIn
        } elseif (!empty($authUser->getFirstLeader()) && $authUser->getFirstLeader()->hasRole('admin')) {
            $childrenIds = $authUser->getFirstLeader()->getChildrenIds();
            $totalDeposits->whereIn('user_id', $childrenIds);
            $totalWithdrawals->whereIn('user_id', $childrenIds);
            $pendingTransaction->whereIn('user_id', $childrenIds);
            $kyc->whereIn('id', $childrenIds);
        } else {
            // No applicable conditions, set whereIn to empty array
            $totalDeposits->whereIn('user_id', []);
            $totalWithdrawals->whereIn('user_id', []);
            $pendingTransaction->whereIn('user_id', []);
            $kyc->whereIn('id', []);
        }

        return Inertia::render('Dashboard', [
            'announcements' => $announcements,
            'totalDeposits' => number_format($totalDeposits->sum('transaction_amount'), 2, '.', ''),
            'totalWithdrawals' => number_format($totalWithdrawals->sum('transaction_amount'), 2, '.', ''),
            'pendingTransaction' => $pendingTransaction->count(),
            'kyc' => $kyc->count(),
        ]);
    }
}
