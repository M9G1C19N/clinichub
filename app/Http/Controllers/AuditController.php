<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('causer', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', 'App\\Models\\' . $request->subject_type);
        }

        if ($request->filled('user_id')) {
            $query->where('causer_type', User::class)
                  ->where('causer_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $paginator = $query->paginate(50)->withQueryString();

        $data = [];
        foreach ($paginator->items() as $log) {
            $props = is_array($log->properties)
                ? $log->properties
                : $log->properties->toArray();

            $data[] = [
                'id'              => $log->id,
                'event'           => $log->event ?? 'updated',
                'description'     => $log->description,
                'subject_type'    => $log->subject_type ? class_basename($log->subject_type) : null,
                'subject_id'      => $log->subject_id,
                'causer'          => $log->causer
                                        ? ['id' => $log->causer->id, 'name' => $log->causer->name]
                                        : null,
                'old'             => $props['old'] ?? null,
                'new'             => $props['attributes'] ?? null,
                'created_at'      => $log->created_at->format('M d, Y h:i A'),
                'created_at_diff' => $log->created_at->diffForHumans(),
            ];
        }

        $subjectTypes = Activity::selectRaw('DISTINCT subject_type')
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->map(fn($t) => class_basename($t))
            ->sort()
            ->values()
            ->all();

        return inertia('Admin/Audit', [
            'logs' => [
                'data'         => $data,
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
                'links'        => $paginator->linkCollection()->toArray(),
            ],
            'filters'      => $request->only(['search', 'event', 'subject_type', 'user_id', 'date_from', 'date_to']),
            'users'        => User::orderBy('name')->get(['id', 'name'])->toArray(),
            'subjectTypes' => $subjectTypes,
            'totals'       => [
                'today'   => Activity::whereDate('created_at', today())->count(),
                'created' => Activity::where('event', 'created')->count(),
                'updated' => Activity::where('event', 'updated')->count(),
                'deleted' => Activity::where('event', 'deleted')->count(),
            ],
        ]);
    }
}
