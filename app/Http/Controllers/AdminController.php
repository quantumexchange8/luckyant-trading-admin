<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

class AdminController extends Controller
{
    //

    public function admin()
    {
        
        return Inertia::render('Admin/Admin');
    }

    public function getAdminDetails(Request $request)
    {
        $admins = User::query()
            ->where('role', '=', 'admin')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            // ->when($request->filled('type'), function($query) use ($request) {
            //     $type = $request->input('type');
            //     $sort = $request->input('sort');
               
            //     $query->orderBy($type, $sort);
            // })
            ->select('id', 'name', 'email', 'setting_rank_id', 'kyc_approval', 'country','created_at', 'hierarchyList')
            ->with(['rank:id,name', 'country:id,name', 'tradingAccounts', 'tradingUser'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $admins->each(function ($user) {
            $user->profile_photo_url = $user->getFirstMediaUrl('profile_photo');
            $user->front_identity = $user->getFirstMediaUrl('front_identity');
            $user->back_identity = $user->getFirstMediaUrl('back_identity');
            $user->kyc_upload_date = $user->getMedia('back_identity')->first()->created_at ?? null;
            $user->walletBalance = $user->wallets->sum('balance');
        });

        return response()->json($admins);
    }
}
