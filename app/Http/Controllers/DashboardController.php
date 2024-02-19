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

        $announcements = Announcement::query()
                ->where('type', 'login')
                ->with('media')
                ->latest()
                ->first();

        $totalDeposits = Transaction::where('category', 'wallet')->where('transaction_type', 'Deposit')->sum('amount');
        $totalWithdrawals = Transaction::where('category', 'wallet')->where('transaction_type', 'Withdrawal')->sum('amount');
        $pendingTransaction = Transaction::where('status', 'Pending')->count();

        $kyc = User::whereNot('role', 'admin')->where('kyc_approval', 'Pending')->count();

        return Inertia::render('Dashboard', [
            'announcements' => $announcements,
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'pendingTransaction' => $pendingTransaction,
            'kyc' => $kyc,
        ]);
    }
}
