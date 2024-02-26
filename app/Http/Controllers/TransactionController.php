<?php

namespace App\Http\Controllers;

// use App\Exports\DepositExport;
// use App\Exports\WithdrawalExport;
// use App\Exports\BalanceAdjustmentExport;
use App\Models\Wallet;
// use App\Models\BalanceAdjustment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Auth;
// use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    public function pendingTransaction()
    {
        return Inertia::render('Transaction/PendingTransaction/PendingTransaction');
    }

    public function transactionHistory()
    {
        return Inertia::render('Transaction/TransactionHistory/TransactionHistory');
    }

    public function getPendingTransaction(Request $request, $type)
    {
        $query = Transaction::query()->with(['user', 'from_wallet', 'to_wallet'])
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

        // if ($request->has('exportStatus')) {
        //     if ($type == 'Deposit') {
        //         return Excel::download(new DepositExport($query), Carbon::now() . '-Pending_' . $type . '-report.xlsx');
        //     } elseif ($type == 'Withdrawal') {
        //         return Excel::download(new WithdrawalExport($query), Carbon::now() . '-Pending_' . $type . '-report.xlsx');
        //     }
        // }

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
            $transactions = Transction::whereIn('id', $request->id)->get();

            foreach ($transactions as $transaction) {

                $transaction->update([
                    'status' => 'Success',
                    'handle_by' => Auth::user()->id,
                ]);

                if ($transaction->transaction_type == 'Deposit') {
                    $wallet = Wallet::find($transaction->to_wallet_id);
                    $wallet->balance += $transaction->amount;
                    $wallet->save();
                }
            }
        } else {
            $transaction = Transaction::find($request->id);
            

            if ($transaction->transaction_type == 'Deposit') {
                $wallet = Wallet::find($transaction->to_wallet_id);
                $wallet->balance += $transaction->amount;
                $wallet->save();

            }
            if ($transaction->transaction_type == 'Withdrawal') {
                $wallet = Wallet::find($transaction->from_wallet_id);
                $wallet->balance -= $transaction->amount;
                $wallet->save();

            }

            $transaction->update([
                'status' => 'Success',
                'handle_by' => Auth::user()->id,
                'new_wallet_amount' => $wallet->balance,
            ]);
            
        }

        return redirect()->back()->with('title', 'Approved successfully')->with('success', 'The transaction request has been approved successfully.');
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
                        'remarks' => 'MULTIPLE Reject by admin - ID ' . $transaction->transaction_number,
                        'handle_by' => Auth::user()->id,
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

    public function getTransactionHistory(Request $request, $type)
    {
        $query = Transaction::query()->with(['user', 'from_wallet', 'to_wallet'])
            ->whereNotIn('status', ['Processing', 'Pending'])
            ->where('transaction_type', $type);
        
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                // $q->whereHas('wallet', function ($wallet_query) use ($search) {
                //     $wallet_query->where('name', 'like', $search);
                // })
                $q->WhereHas('user', function ($user) use ($search) {
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

        // if ($request->has('exportStatus')) {
        //     if ($type == 'Deposit') {
        //         return Excel::download(new DepositExport($query), Carbon::now() . '-' . $type . '_History-report.xlsx');
        //     } elseif ($type == 'Withdrawal') {
        //         return Excel::download(new WithdrawalExport($query), Carbon::now() . '-' . $type . '_History-report.xlsx');
        //     }
        // }

        $results = $query->latest()->paginate(10);

        $totalAmount = $query->sum('amount');

        $results->each(function ($user_deposit) {
            $user_deposit->user->profile_photo_url = $user_deposit->user->getFirstMediaUrl('profile_photo');
        });


        return response()->json([
            $type => $results,
            'totalAmount' => $totalAmount
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
