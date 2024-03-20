<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Inertia::render('Announcement/Announcement');
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

        $results->each(function ($image) {
            $image->announcement_image_url = $image->getFirstMediaUrl('announcement');
        });

        return response()->json($results);
    }

    public function addAnnouncement(Request $request)
    {

        $announcement = Announcement::create([
            'subject' => $request->subject,
            'details' => $request->details,
            'type' => $request->positions,
            'status' => 'Active',
        ]);

        if ($request->hasfile('image'))
        {
            $announcement->addMedia($request->image)->toMediaCollection('announcement');
        }

        return redirect()->back()->with('title', 'Announcement uploaded')->with('success', 'This announcement has been uploaded successfully.');
    }

    public function editAnnoucement(Request $request)
    {

        $announcement = Announcement::find($request->id);

        $announcement->update([
            'subject' => $request->subject,
            'details' => $request->details,
        ]);

        if ($request->hasfile('image'))
        {
            $announcement->clearMediaCollection('announcement');
            $announcement->addMedia($request->image)->toMediaCollection('announcement');
        }

        return redirect()->back()->with('title', 'Announcement uploaded')->with('success', 'This announcement has been uploaded successfully.');
    }

    public function updateStatus(Request $request)
    {
        $announcement = Announcement::find($request->id);

        $announcement->update([
            'status' => $request->status,
        ]);

        return redirect()->back()->with('title', 'Announcement uploaded')->with('success', 'This announcement has been uploaded successfully.');
    }
}
