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
        $search = $request->get('search', null);
        // return dd($search);
        $perPage = $request->get('per_page', 5);
        // Query the SystemLog with relationships
        $logsQuery = SystemLog::with('user')->orderByDesc('created_at');

        // Apply search filter
        if ($search) {
            $logsQuery->where(function ($query) use ($search) {
                $query->where('action', 'like', '%' . $search . '%')
                    ->orWhere('model_type', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Paginate logs with the requested per-page value or default to 10
        $logs = $logsQuery->paginate($perPage);

        // map the logs, and format the created_at date using readable format
        $data = $logs->map(function ($log) {
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
            'logs' => $data, // Send paginated items
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
