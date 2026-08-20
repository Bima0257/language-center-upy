<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::with('causer');

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%{$search}%");
        }

        $logs = $query->orderByDesc('created_at')->paginate(30)->withQueryString();

        $events = Activity::select('event')->distinct()->pluck('event');

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'events' => $events,
            'filters' => $request->only(['causer_id', 'event', 'subject_type', 'search']),
        ]);
    }
}
