<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementToLeader;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Inertia::render('Announcement/Announcement', [
            'announcementsCount' => Announcement::count()
        ]);
    }

    public function create()
    {
        return Inertia::render('Announcement/NewAnnouncement');
    }

    public function edit($id)
    {
        $announcement = Announcement::with([
            'leaders',
            'media'
        ])->find($id);

        return Inertia::render('Announcement/EditAnnouncement', [
            'announcement' => $announcement,
            'thumbnail' => $announcement->getMedia('announcement'),
        ]);
    }

    public function getAnnouncement(Request $request)
    {
        // fetch limit with default
        $limit = $request->input('limit', 6);

        // Fetch parameter from request
        $search = $request->input('search', '');
        $start_date = $request->input('start_date', '');
        $end_date = $request->input('end_date', '');

        $query = Announcement::with([
            'media',
            'leaders'
        ]);

        if (!empty($search)) {
            $keyword = '%' . $search . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('subject', 'like', $keyword)
                    ->orWhere('details', 'like', $keyword);
            });
        }

        if (!empty($start_date) && !empty($end_date)) {
            $startDate = Carbon::parse($start_date)->addDay()->startOfDay();
            $endDate = Carbon::parse($end_date)->addDay()->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Get total count of masters
        $totalRecords = $query->count();

        // Fetch paginated results
        $announcements = $query->latest()
            ->paginate($limit);

        $formattedAnnouncements = $announcements->map(function ($master) {
            // if all leaders included, return 'everyone'
            $totalCount = $master->leaders->count();
            $leadersCount = User::where('leader_status', 1)->count();

            if ($totalCount === $leadersCount) {
                $master->leaders_names = 'everyone';
            } else {
                $master->leaders_names = $master->leaders->pluck('name')->join(', ');
            }

            return $master;
        });

        return response()->json([
            'announcements' => $formattedAnnouncements,
            'totalRecords' => $totalRecords,
            'currentPage' => $announcements->currentPage(),
        ]);
    }

    public function addAnnouncement(Request $request)
    {
        Validator::make($request->all(), [
            'receiver' => ['required'],
            'thumbnail' => ['nullable', 'image', 'max:8000'],
        ])->setAttributeNames([
            'receiver' => trans('public.recipient'),
            'thumbnail' => trans('public.thumbnail'),
        ])->validate();

        if (!$request->filled('subject') && !$request->filled('content') && !$request->hasFile('thumbnail')) {
            throw ValidationException::withMessages([
                'subject' => trans('public.at_least_one_field_required'),
            ]);
        }

        $announcement = Announcement::create([
            'subject' => $request->subject,
            'details' => $request->details,
            'type' => 'login',
            'url' => $request->url,
            'status' => 'Active',
            'handle_by' => \Auth::id()
        ]);

        if ($request->hasfile('thumbnail')) {
            $announcement->addMedia($request->thumbnail)->toMediaCollection('announcement');
        }

        $leaders = $request->receiver;

        if ($leaders) {
            foreach ($leaders as $leader) {
                AnnouncementToLeader::create([
                    'announcement_id' => $announcement->id,
                    'user_id' => $leader['id'],
                ]);
            }
        }

        return to_route('announcement.announcement_listing')->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_publish_announcement"),
            'type' => 'success',
        ]);
    }

    public function updateAnnouncement(Request $request)
    {
        Validator::make($request->all(), [
            'receiver' => ['required'],
            'thumbnail' => ['nullable', 'image', 'max:8000'],
        ])->setAttributeNames([
            'receiver' => trans('public.recipient'),
            'thumbnail' => trans('public.thumbnail'),
        ])->validate();

        $announcement = Announcement::find($request->id);

        $announcement->update([
            'subject' => $request->subject,
            'details' => $request->details,
            'url' => $request->url,
        ]);

        $leaders = $request->receiver;

        if ($leaders) {
            $existingLeaders = AnnouncementToLeader::where('announcement_id', $announcement->id)
                ->pluck('user_id')
                ->toArray();

            $incomingLeaderIds = collect($leaders)->pluck('id')->toArray();

            if (!empty(array_diff($existingLeaders, $incomingLeaderIds)) || !empty(array_diff($incomingLeaderIds, $existingLeaders))) {
                AnnouncementToLeader::where('announcement_id', $announcement->id)->delete();

                foreach ($leaders as $leader) {
                    AnnouncementToLeader::create([
                        'announcement_id' => $announcement->id,
                        'user_id' => $leader['id'],
                    ]);
                }
            }
        } else {
            AnnouncementToLeader::where('master_id', $announcement->id)->delete();
        }

        if ($request->hasfile('thumbnail')) {
            $announcement->clearMediaCollection('announcement');
            $announcement->addMedia($request->thumbnail)->toMediaCollection('announcement');
        }

        return to_route('announcement.announcement_listing')->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_announcement"),
            'type' => 'success',
        ]);
    }

    public function updateStatus(Request $request)
    {
        $announcement = Announcement::find($request->id);

        $announcement->status = $announcement->status == 'Active' ? 'Inactive' : 'Active';
        $announcement->save();

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_update_announcement"),
            'type' => 'success',
        ]);
    }

    public function deleteAnnouncement(Request $request)
    {
        $announcement = Announcement::find($request->id);

        $announcement->delete();

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_success_delete_announcement"),
            'type' => 'success',
        ]);
    }
}
