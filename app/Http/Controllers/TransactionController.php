<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\WalletLog;
// use App\Exports\BalanceAdjustmentExport;
use App\Models\Transaction;
use Illuminate\Http\Request;
// use App\Models\BalanceAdjustment;
use App\Exports\DepositExport;
use App\Exports\WithdrawalExport;
use App\Exports\TransactionsExport;
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
        return Inertia::render('Transaction/PendingTransaction/PendingTransaction');
    }

    public function transactionHistory()
    {
        return Inertia::render('Transaction/TransactionHistory/TransactionHistory', [
            'transactionTypes' => (new SelectOptionService())->getTransactionType(),
        ]);
    }

    public function getPendingTransaction(Request $request, $type)
    {
        $query = Transaction::query()->with(['user', 'from_wallet', 'to_wallet', 'payment_account'])
            ->where('transaction_type', $type)
            ->where('status', 'Processing');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
                })
                ->orWhereHas('to_wallet', function ($to_wallet) use ($search) {
                    $to_wallet->where('name', 'like', $search);
                })
                ->orWhereHas('from_wallet', function ($from_wallet) use ($search) {
                    $from_wallet->where('name', 'like', $search);
                })
                ->orWhere('transaction_number', 'like', $search)
                    ->orWhere('amount', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('leader')) {
            $leader = $request->input('leader');
            $leaderUser = User::find($leader);
            if ($leaderUser) {
                $query->whereIn('user_id', $leaderUser->getChildrenIds());
            }
        }

        if ($request->has('exportStatus')) {

            if ($type == 'Deposit') {
                return Excel::download(new PendingDepositExport($query), Carbon::now() . '-Pending_' . $type . '-report.xlsx');
            } elseif ($type == 'Withdrawal') {
                return Excel::download(new PendingWithdrawalExport($query), Carbon::now() . '-Pending_' . $type . '-report.xlsx');
            }
        }

        $results = $query->latest()->paginate(10);

        $results->each(function ($transaction) {

            $transaction->user->profile_photo_url = $transaction->user->getFirstMediaUrl('profile_photo');
            $transaction->receipt_url = $transaction->getFirstMediaUrl('receipt');
        });


        return response()->json([$type => $results]);
    }

    public function approveTransaction(Request $request)
    {
        $type = $request->type;

        if ($type == 'approve_selected') {
            $transactions = Transaction::whereIn('id', $request->id)->get();

            foreach ($transactions as $transaction) {

                $transaction->update([
                    'status' => 'Success',
                    'handle_by' => Auth::user()->id,
                ]);

                if ($transaction->transaction_type == 'Deposit') {
                    $wallet = Wallet::find($transaction->to_wallet_id);
                    $wallet->balance += $transaction->amount;
                    $wallet->save();
                    
                    Notification::route('mail', $transaction->user->email)->notify(new DepositConfirmationNotification($transaction));
                } elseif ($transaction->transaction_type == 'Withdrawal') {
                    $wallet = Wallet::find($transaction->from_wallet_id);
        
                    Notification::route('mail', $transaction->user->email)->notify(new WithdrawalConfirmationNotification($transaction));
                }
            }
        } else {
            $transaction = Transaction::find($request->id);

            if ($transaction->transaction_type == 'Deposit') {
                $wallet = Wallet::find($transaction->to_wallet_id);
                $wallet->balance += $transaction->amount;
                $wallet->save();

                $transaction->update([
                    'status' => 'Success',
                    'handle_by' => Auth::user()->id,
                    'new_wallet_amount' => $wallet->balance,
                ]);
                
                Notification::route('mail', $transaction->user->email)->notify(new DepositConfirmationNotification($transaction));
            } elseif ($transaction->transaction_type == 'Withdrawal') {
                $wallet = Wallet::find($transaction->from_wallet_id);
    
                Notification::route('mail', $transaction->user->email)->notify(new WithdrawalConfirmationNotification($transaction));
            }
        }

        return redirect()->back()->with('title', 'Approved successfully')->with('success', 'The transaction request has been approved successfully.');
    }

    public function rejectTransaction(Request $request)
    {
        $type = $request->type;

        $request->validate([
            'remarks' => ['required'],
        ]);

        if ($type == 'reject_selected') {
            $transactions = Transaction::whereIn('id', $request->id)->get();

            foreach ($transactions as $transaction) {

                if ($transaction->status == 'Processing') {
                    $transaction->update([
                        'status' => 'Rejected',
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
            $transaction = Transaction::find($request->id);

            if ($transaction->transaction_type == 'Deposit') {
                $wallet = Wallet::find($transaction->to_wallet_id);
                $transaction->update([
                    'status' => 'Rejected',
                    'remarks'=> $request->remarks,
                    'handle_by' => Auth::user()->id,
                    'new_wallet_amount' => $wallet->balance,
                ]);
            }

            if ($transaction->transaction_type == 'Withdrawal') {

                $transaction->update([
                    'status' => 'Rejected',
                    'remarks'=> $request->remarks,
                    'new_wallet_amount' => $transaction->new_wallet_amount += $transaction->amount,
                    'handle_by' => Auth::user()->id,
                ]);

                $wallet = Wallet::find($transaction->from_wallet_id);
                $wallet->balance += $transaction->amount;
                $wallet->save();
            }
        }

        return redirect()->back()->with('title', 'Rejected successfully')->with('success', 'The transaction request has been rejected successfully.');
    }

    public function getTransactionHistory(Request $request)
    {
        $query = Transaction::query()
            ->with(['user:id,name,email,country,upline_id,hierarchyList,leader_status,top_leader_id', 'to_wallet:id,name,type', 'from_wallet:id,name,type', 'to_meta_login:id,meta_login', 'from_meta_login:id,meta_login', 'payment_account'])
            ->whereNotIn('status', ['Processing', 'Pending']);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                // $q->whereHas('wallet', function ($wallet_query) use ($search) {
                //     $wallet_query->where('name', 'like', $search);
                // })
                $q->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                         ->orWhere('email', 'like', $search);
                })
                    ->orWhere('transaction_number', 'like', $search)
                    ->orWhere('amount', 'like', $search);
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

        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->where(function ($q) use ($type) {
                $q->where('transaction_type',  $type);
            });
        }

        if ($request->filled('category')) {
            $category = $request->input('category');
            $query->where(function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        if ($request->filled('methods')) {
            $methods = $request->input('methods');
            $query->where(function ($q) use ($methods) {
                $q->where('payment_method', $methods);
            });
        }

        if ($request->filled('fund_type')) {
            $fund_type = $request->input('fund_type');
            $query->where(function ($q) use ($fund_type) {
                $q->where('fund_type', $fund_type);
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where(function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        if ($request->has('exportStatus')) {
            $fileName = Carbon::now() . '-' . $request->type . '_History-report.xlsx';
            return Excel::download(new TransactionsExport($query), $fileName);
        }

        $totalAmountQuery = clone $query;
        $rejectedAmountQuery = clone $query;
        $results = $query->latest()->paginate(10);

        $totalAmount = $totalAmountQuery->sum('transaction_amount');
        $successAmount = $totalAmountQuery->where('status', 'Success')->sum('transaction_amount');
        $rejectedAmount = $rejectedAmountQuery->where('status', 'Rejected')->sum('transaction_amount');

        $results->each(function ($transaction) {
            $transaction->user->profile_photo_url = $transaction->user->getFirstMediaUrl('profile_photo');
            $transaction->user->first_leader = $transaction->user->getFirstLeader() ?? null;
        });

        if ($request->input('type') == 'Withdrawal') {
            $results->each(function ($transaction) {
                $profit = WalletLog::where('user_id', $transaction->user_id)->where('category', 'profit')->sum('amount');
                $bonus = WalletLog::where('user_id', $transaction->user_id)->where('category', 'bonus')->sum('amount');
                $transaction->profit_amount = $profit;
                $transaction->bonus_amount = $bonus;
            });
        }

        return response()->json([
            'transactions' => $results,
            'totalAmount' => $totalAmount,
            'successAmount' => $successAmount,
            'rejectedAmount' => $rejectedAmount,
        ]);
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
