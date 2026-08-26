<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(
        Request $request
    ): View {
        Gate::authorize(
            'viewAny',
            ActivityLog::class
        );

        $search = trim(
            (string) $request->query(
                'search',
                ''
            )
        );

        $action = trim(
            (string) $request->query(
                'action',
                ''
            )
        );

        $activityLogs = ActivityLog::query()
            ->with([
                'actor',
                'subject',
            ])
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    function ($query) use ($search) {
                        $query
                            ->where(
                                'description',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'actor',
                                fn ($actorQuery) => $actorQuery->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                            );
                    }
                )
            )
            ->when(
                $action !== '',
                fn ($query) => $query->where(
                    'action',
                    $action
                )
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view(
            'activity-logs.index',
            compact(
                'activityLogs',
                'actions',
                'search',
                'action'
            )
        );
    }
}