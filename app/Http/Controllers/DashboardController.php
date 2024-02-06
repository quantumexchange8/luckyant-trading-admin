<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
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
        
        return Inertia::render('Dashboard', [
            'announcements' => $announcements,
        ]);
    }
}
