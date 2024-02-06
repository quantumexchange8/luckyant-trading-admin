<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Announcement;

class AnnoucementController extends Controller
{
    //

    public function Announcement()
    {

        return Inertia::render('Announcement/announcement');
    }

    public function getAnnouncement(Request $request)
    {
        $announcements = Announcement::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('subject', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            });

        $results = $announcements->latest()->paginate(10);
        
        // $results->each(function ($image) {
        //     $image->user->profile_photo_url = $user_deposit->user->getFirstMediaUrl('profile_photo');
        // });

        return response()->json($results);
    }

    public function addAnnouncement(Request $request)
    {

        $announcement = Announcement::create([
            'subject' => $request->subject,
            'details' => $request->details,
            'type' => $request->positions,
        ]);

        if ($request->hasfile('image'))
        {
            $announcement->addMedia($request->image)->toMediaCollection('announcement');
        }

        return redirect()->back()->with('title', 'Announcement uploaded')->with('success', 'This announcement has been uploaded successfully.');
    }
}
