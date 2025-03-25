<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Carbon\Carbon;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Models\Transaction;
// use App\Exports\BalanceAdjustmentExport;
use Illuminate\Http\Request;
use App\Exports\DepositExport;
// use App\Models\BalanceAdjustment;
use App\Exports\WithdrawalExport;
use App\Exports\TransactionsExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PendingDepositExport;
use App\Services\SelectOptionService;
use App\Exports\InternalTransferExport;
use App\Exports\PendingWithdrawalExport;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use App\Notifications\DepositConfirmationNotification;
use App\Notifications\WithdrawalConfirmationNotification;

class TransactionController extends Controller
{
    public function pendingTransaction()
    {
        $query = Transaction::where([
            'category' => 'wallet',
            'status' => 'Processing',
        ]);

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

        return Inertia::render('Transaction/PendingTransaction/PendingTransaction', [
            'pendingDepositCounts' => (clone $query)->where('transaction_type', 'Deposit')->count(),
            'pendingWithdrawalCounts' => (clone $query)->where('transaction_type', 'Withdrawal')->count(),
        ]);
    }

    public function transactionHistory()
    {
        return Inertia::render('Transaction/TransactionHistory/TransactionHistory', [
            'transactionTypes' => (new SelectOptionService())->getTransactionType(),
        ]);
    }

    public function getPendingTransaction(Request $request, UserService $userService)
    {
        $query = Transaction::with([
            'user:id,name,email,hierarchyList',
            'from_wallet:id,type,name,wallet_address',
            'to_wallet:id,type,name,wallet_address',
            'from_meta_login:id,meta_login',
            'to_meta_login:id,meta_login,account_type',
            'to_meta_login.accountType:id,name,slug',
            'payment_account',
            'media',
            'setting_payment'
        ])

            ->where([
                'category' => 'wallet',
                'transaction_type' => $request->type,
                'status' => 'Processing',
            ]);

        if ($request->type == 'Withdrawal') {
            $query->addSelect([
                'profitAmount' => DB::table('wallet_logs')
                    ->selectRaw('SUM(amount)')
                    ->whereColumn('wallet_logs.user_id', 'transactions.user_id')
                    ->where('wallet_logs.purpose', 'ProfitSharing'),

                'bonusAmount' => DB::table('wallet_logs')
                    ->selectRaw('SUM(amount)')
                    ->whereColumn('wallet_logs.user_id', 'transactions.user_id')
                    ->whereIn('wallet_logs.purpose', [
                        'PerformanceIncentive',
                        'SameLevelRewards',
                        'LotSizeRebate',
                        'ExtraBonus'
                    ]),
            ]);
        }

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

        $join_start_date = $request->query('joinStartDate');
        $join_end_date = $request->query('joinEndDate');

        if ($join_start_date && $join_end_date) {
            $start_date = Carbon::createFromFormat('Y-m-d', $join_start_date)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $join_end_date)->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->first_leader_id) {
            $first_leader = User::find($request->first_leader_id);
            $childrenIds = $first_leader->getChildrenIds();
            $query->whereIn('user_id', $childrenIds);
        }

        if ($request->export == 'yes') {
            if ($request->type == 'Withdrawal') {
                return Excel::download(new PendingWithdrawalExport($query), Carbon::now() . '-pending-withdrawal-report.xlsx');
            } elseif ($request->type == 'Deposit') {
                return Excel::download(new PendingDepositExport($query), Carbon::now() . '-pending-deposit-report.xlsx');
            }
        }

        $pendings = $query
            ->orderByDesc('created_at')
            ->get();

        // Extract all hierarchy IDs from users' hierarchyLists
        $userHierarchyLists = $pendings->pluck('user.hierarchyList')
            ->filter()
            ->flatMap(fn($list) => explode('-', trim($list, '-')))
            ->unique()
            ->toArray();

        // Load all potential leaders in bulk
        $leaders = User::whereIn('id', $userHierarchyLists)
            ->where('leader_status', 1) // Only load users with leader_status == 1
            ->get()
            ->keyBy('id');

        // Attach the first leader details
        $pendings->each(function ($subscriptionQuery) use ($userService, $leaders) {
            $firstLeader = $userService->getFirstLeader($subscriptionQuery->user?->hierarchyList, $leaders);

            $subscriptionQuery->first_leader_id = $firstLeader?->id;
            $subscriptionQuery->first_leader_name = $firstLeader?->name;
            $subscriptionQuery->first_leader_email = $firstLeader?->email;
        });

        return response()->json([
            'pendings' => $pendings
        ]);
    }

    public function approveTransaction(Request $request)
    {
        $type = $request->type;

        if ($type == 'approve_selected') {
            $transactions = Transaction::whereIn('id', $request->id)->get();

            foreach ($transactions as $transaction) {

                if ($transaction->status == 'Success') {
                    continue;
                }

                $transaction->update([
                    'status' => 'Success',
                    'approval_at' => now(),
                    'handle_by' => Auth::user()->id,
                ]);

                if ($transaction->transaction_type == 'Deposit') {
                    $wallet = Wallet::find($transaction->to_wallet_id);

                    if ($transaction->payment_method == 'Payment Merchant') {
                        if (!$transaction->transaction_amount) {
                            $transaction->update([
                                'transaction_amount' => $transaction->amount,
                            ]);
                        }
                        $wallet->balance += $transaction->transaction_amount;
                    } else {
                        $wallet->balance += $transaction->amount;
                    }

                    $wallet->save();

                    $transaction->update([
                        'new_wallet_amount' => $wallet->balance,
                    ]);

                    if (App::environment('production')) {
                        Notification::route('mail', $transaction->user->email)->notify(new DepositConfirmationNotification($transaction));
                    }
                } elseif ($transaction->transaction_type == 'Withdrawal') {
                    $wallet = Wallet::find($transaction->from_wallet_id);

                    if (App::environment('production')) {
                        Notification::route('mail', $transaction->user->email)->notify(new WithdrawalConfirmationNotification($transaction));
                    }
                }
            }
        } else {
            $transaction = Transaction::find($request->id);
            $wallet = Wallet::find($transaction->to_wallet_id);

            if ($transaction->status == 'Success') {
                return back()->with('toast', [
                    'title' => trans("public.invalid_action"),
                    'message' => trans("public.try_again_later"),
                    'type' => 'warning',
                ]);
            }

            if ($transaction->transaction_type == 'Deposit') {
                $transaction->update([
                    'status' => 'Success',
                    'approval_at' => now(),
                    'handle_by' => Auth::user()->id,
                ]);

                if ($transaction->payment_method == 'Payment Merchant') {
                    if (!$transaction->transaction_amount) {
                        $transaction->update([
                            'transaction_amount' => $transaction->amount,
                        ]);
                    }
                    $wallet->balance += $transaction->transaction_amount;
                } else {
                    $wallet->balance += $transaction->amount;
                }
                $wallet->save();

                $transaction->update([
                    'new_wallet_amount' => $wallet->balance,
                ]);

                if (App::environment('production')) {
                    Notification::route('mail', $transaction->user->email)->notify(new DepositConfirmationNotification($transaction));
                }

            } elseif ($transaction->transaction_type == 'Withdrawal') {
                $anotherTransactions = Transaction::with('user')
                    ->where([
                        'transaction_number' => $transaction->transaction_number,
                        'status' => 'Processing'
                    ])
                    ->get();

                foreach ($anotherTransactions as $anotherTransaction) {
                    $anotherTransaction->update([
                        'status' => 'Success',
                        'approval_at' => now(),
                        'handle_by' => Auth::id(),
                    ]);

                    if (App::environment('production')) {
                        Notification::route('mail', $anotherTransaction->user->email)->notify(new WithdrawalConfirmationNotification($anotherTransaction));
                    }
                }
            }
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_approve_transaction_message"),
            'type' => 'success',
        ]);
    }

    public function rejectTransaction(Request $request)
    {
        $type = $request->type;

        if ($type == 'reject_selected') {
            $transactions = Transaction::whereIn('id', $request->id)->get();

            foreach ($transactions as $transaction) {

                if ($transaction->status == 'Processing') {
                    $transaction->update([
                        'status' => 'Rejected',
                        'approval_at' => now(),
                        'remarks' => 'MULTIPLE Reject by admin - ID ' . $transaction->transaction_number,
                        'handle_by' => Auth::user()->id,
                        'new_wallet_amount' => $transaction->new_wallet_amount += $transaction->amount,
                    ]);

                    if ($transaction->transaction_type == 'Withdrawal') {
                        $wallet = Wallet::find($transaction->from_wallet_id);

                        $wallet->balance += $transaction->amount;
                        $wallet->save();
                    }
                }
            }
        } else {
            $request->validate([
                'remarks' => ['required'],
            ]);

            $transaction = Transaction::find($request->id);

            if ($transaction->status == 'Rejected') {
                return back()->with('toast', [
                    'title' => trans("public.invalid_action"),
                    'message' => trans("public.try_again_later"),
                    'type' => 'warning',
                ]);
            }

            if ($transaction->transaction_type == 'Deposit') {
                $wallet = Wallet::find($transaction->to_wallet_id);
                $transaction->update([
                    'status' => 'Rejected',
                    'approval_at' => now(),
                    'remarks'=> $request->remarks,
                    'handle_by' => Auth::user()->id,
                    'new_wallet_amount' => $wallet->balance,
                ]);
            }

            if ($transaction->transaction_type == 'Withdrawal') {
                $anotherTransactions = Transaction::where([
                        'transaction_number' => $transaction->transaction_number,
                        'status' => 'Processing'
                    ])
                    ->get();

                foreach ($anotherTransactions as $anotherTransaction) {
                    $anotherTransaction->update([
                        'status' => 'Rejected',
                        'approval_at' => now(),
                        'remarks'=> $request->remarks,
                        'new_wallet_amount' => $anotherTransaction->new_wallet_amount += $anotherTransaction->amount,
                        'handle_by' => Auth::user()->id,
                    ]);

                    $wallet = Wallet::find($anotherTransaction->from_wallet_id);
                    $wallet->balance += $anotherTransaction->amount;
                    $wallet->save();
                }
            }
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_reject_transaction_message"),
            'type' => 'success',
        ]);
    }

    public function getTransactionHistory(Request $request, UserService $userService)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Transaction::with([
                'user',
                'from_wallet:id,type,name,wallet_address',
                'to_wallet:id,type,name,wallet_address',
                'from_meta_login:id,meta_login',
                'to_meta_login:id,meta_login',
            ])
                ->where('transaction_type', $data['filters']['type']['value'])
                ->whereNot('status', 'Processing');

            if ($data['filters']['type']['value'] == 'Withdrawal') {
                $query->addSelect([
                    'profitAmount' => DB::table('wallet_logs')
                        ->selectRaw('SUM(amount)')
                        ->whereColumn('wallet_logs.user_id', 'transactions.user_id')
                        ->where('wallet_logs.purpose', 'ProfitSharing'),

                    'bonusAmount' => DB::table('wallet_logs')
                        ->selectRaw('SUM(amount)')
                        ->whereColumn('wallet_logs.user_id', 'transactions.user_id')
                        ->whereIn('wallet_logs.purpose', ['PerformanceIncentive', 'SameLevelRewards', 'LotSizeRebate']),
                ]);

                if (!empty($data['filters']['start_approval_date']['value']) && !empty($data['filters']['end_approval_date']['value'])) {
                    $start_date = Carbon::parse($data['filters']['start_approval_date']['value'])->addDay()->startOfDay();
                    $end_date = Carbon::parse($data['filters']['end_approval_date']['value'])->addDay()->endOfDay();

                    $query->whereBetween('approval_at', [$start_date, $end_date]);
                }
            }

            if ($data['filters']['type']['value'] == 'Transfer') {
                $query->with([
                    'from_wallet.user:id,name',
                    'to_wallet.user:id,name',
                ]);
            }

            if ($data['filters']['global']['value']) {
                $query->whereHas('user', function($q) use ($data) {
                    $q->where(function ($query) use ($data) {
                        $keyword = $data['filters']['global']['value'];

                        $query->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                });
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            // Filter by leaderId if provided
            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('leader_status', 1)
                    ->where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('user_id', $usersUnderLeader);
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if ($data['filters']['fund_type']['value']) {
                $query->where('fund_type', $data['filters']['fund_type']['value']);
            }

            if ($data['filters']['status']['value']) {
                $query->where('status', $data['filters']['status']['value']);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

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

            // Export logic
            if ($request->has('exportStatus') && $request->exportStatus) {
                return Excel::download(new TransactionsExport($query), now() . '-'. $data['filters']['type']['value'] . 'report.xlsx');
            }

            // Calculate totals before pagination
            $totalAmount = (clone $query)->sum('amount');
            $successAmount = (clone $query)->where('status', 'Success')->sum('amount');
            $rejectedAmount = (clone $query)->where('status', 'Rejected')->sum('amount');

            $transactions = $query->paginate($data['rows']);

            $userHierarchyLists = $transactions->pluck('user.hierarchyList')
                ->filter()
                ->flatMap(fn($list) => explode('-', trim($list, '-')))
                ->unique()
                ->toArray();

            // Load all potential leaders in bulk
            if ($leaderId > 0) {
                $leaderQuery = User::where('id', $leaderId)
                    ->where('leader_status', 1);
            } else {
                $leaderQuery = User::whereIn('id', $userHierarchyLists)
                    ->where('leader_status', 1);
            }

            $leaders = $leaderQuery->get()->keyBy('id');

            $transactions->each(function ($transaction) use ($userService, $leaders) {
                $firstLeader = $userService->getFirstLeader($transaction->user?->hierarchyList, $leaders);

                $transaction->first_leader_id = $firstLeader?->id;
                $transaction->first_leader_name = $firstLeader?->name;
                $transaction->first_leader_email = $firstLeader?->email;
            });

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'totalAmount' => $totalAmount,
                'successAmount' => $successAmount,
                'rejectedAmount' => $rejectedAmount,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function getBalanceHistory(Request $request, $type)
    {
        $query = BalanceAdjustment::query()->with('user', 'to_user')
            ->where('type', $type);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user_query) use ($search) {
                    $user_query->where('name', 'like', $search);
                })
                ->orWhereHas('to_user', function ($to_user_query) use ($search) {
                    $to_user_query->where('name', 'like', $search);
                })
                ->orWhere('new_balance', 'like', $search)
                ->orWhere('amount', 'like', $search)
                ->orWhere('description', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        // if ($request->has('exportStatus')) {
        //     if ($type == 'WalletAdjustment') {
        //         return Excel::download(new BalanceAdjustmentExport($query, $type), Carbon::now() . '-' . $type . '_History-report.xlsx');
        //     } elseif ($type == 'InternalTransfer') {
        //         return Excel::download(new BalanceAdjustmentExport($query, $type), Carbon::now() . '-' . $type . '_History-report.xlsx');
        //     }
        // }

        $results = $query->latest()->paginate(10);

        $results->each(function ($user_deposit) use ($type) {
            $user_deposit->user->profile_photo_url = $user_deposit->user->getFirstMediaUrl('profile_photo');

            if ($type != 'WalletAdjustment') {
                $user_deposit->to_user->profile_photo_url = $user_deposit->to_user->getFirstMediaUrl('profile_photo');
            }
        });

        return response()->json([$type => $results]);
    }

}
