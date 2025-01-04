<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    /**
     * Display a listing of the logs.
     */
    public function index(Request $request)
    {
        // Query the SystemLog with relationships
        $logsQuery = SystemLog::with('user')->orderByDesc('created_at');

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $logsQuery->where(function ($query) use ($request) {
                $query->where('action', 'like', '%' . $request->search . '%')
                    ->orWhere('model_type', 'like', '%' . $request->search . '%')
                    ->orWhereHas('user', function ($subQuery) use ($request) {
                        $subQuery->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Paginate logs with the requested per-page value or default to 10
        $logs = $logsQuery->paginate($request->get('per_page', 10));

        // map the logs, and format the created_at date using readable format
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user,
                'model_type' => $log->model_type,
                'changed_fields' => $log->changed_fields,
                'user_agent' => $log->user_agent,
                'human_readable_time' => $log->created_at->diffForHumans(),
                'created_at' => $log->created_at->format('d M Y - H:i'),
            ];
        });

        // Share pagination and search data with the front-end
        return Inertia::render('Logs/Index', [
            'logs' => $logs->items(), // Send paginated items
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
            'search' => $request->search,
        ]);
    }
}
