<?php

namespace App\Http\Controllers;

use App\Http\Requests\KycApprovalRequest;
use App\Models\SettingRank;
use App\Models\User;
use App\Notifications\KycApprovalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function index()
    {
        $rankLists = SettingRank::all()->map(function ($rank) {
            return [
                'value' => $rank->id,
                'label' => $rank->name,
            ];
        });
        return Inertia::render('Member/MemberListing', [
            'rankLists' => $rankLists,
            'pendingKycCount' => User::where('kyc_approval', '=', 'pending')->where('role', 'member')->count(),
            'unverifiedKycCount' => User::where('kyc_approval', '=', 'unverified')->where('role', 'member')->count(),
        ]);
    }

    public function getMemberDetails(Request $request)
    {
        $members = User::query()
            ->where('role', '=', 'member')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                $query->where(function ($innerQuery) use ($type) {
                    $innerQuery->where('kyc_approval', $type);
                });
            })
            ->when($request->filled('rank'), function ($query) use ($request) {
                $rank_id = $request->input('rank');
                $query->where(function ($innerQuery) use ($rank_id) {
                    $innerQuery->where('setting_rank_id', $rank_id);
                });
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->select('id', 'name', 'email', 'setting_rank_id', 'kyc_approval', 'country', 'cash_wallet','created_at')
            ->with(['rank:id,name', 'country:id,name'])
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // if ($request->has('exportStatus')) {
        //     if($request->type != null){
        //         return Excel::download(new MemberListingTypeExport($members), Carbon::now() . '-' . $request->type . '-report.xlsx');
        //     } else {
        //         return Excel::download(new MemberListingExport($members), Carbon::now() . '-' . '-report.xlsx');
        //     }
        // }

        $members->each(function ($user) {
            $user->profile_photo_url = $user->getFirstMediaUrl('profile_photo');
            $user->front_identity = $user->getFirstMediaUrl('front_identity');
            $user->back_identity = $user->getFirstMediaUrl('back_identity');
            $user->kyc_upload_date = $user->getMedia('back_identity')->first()->created_at ?? null;
        });

        return response()->json($members);
    }

    public function verifyMember(KycApprovalRequest $request)
    {
        $user = User::find($request->id);
        $approvalType = $request->type;

        $title = '';
        $message = '';

        if ($approvalType == 'approve') {
            $user->update([
                'kyc_approval' => 'Verified',
                'kyc_approval_description' => 'Approved by admin',
            ]);

            $title = 'Member verified!';
            $message = 'The member has been verified successfully.';

        } elseif ($approvalType == 'reject') {
            $user->update([
                'kyc_approval' => 'Unverified',
                'kyc_approval_description' => $request->remark,
            ]);

            $title = 'Member unverified!';
            $message = 'An email has been sent to the member to request updated KYC information.';

        }

        Notification::route('mail', $user->email)
            ->notify(new KycApprovalNotification($user));

        return redirect()->back()->with('title', $title)->with('toast', $message);
    }
}
