<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SearchController extends Controller
{
    /**
     * Tìm kiếm nâng cao
     */
    public function advancedSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string',
            'type' => 'required|in:all,tasks,teams,users',
            'status' => 'nullable|array',
            'status.*' => 'in:todo,in_progress,done,overdue',
            'priority' => 'nullable|array',
            'priority.*' => 'in:low,medium,high',
            'team_id' => 'nullable|integer|exists:teams,id',
            'label_id' => 'nullable|integer|exists:labels,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'created_by' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'due_date_start' => 'nullable|date',
            'due_date_end' => 'nullable|date|after_or_equal:due_date_start',
            'completed' => 'nullable|boolean',
            'has_attachments' => 'nullable|boolean',
            'has_subtasks' => 'nullable|boolean',
            'sort_by' => 'nullable|in:created_at,updated_at,due_date,priority',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100'
        ]);
        
        $query = $validated['query'] ?? '';
        $type = $validated['type'];
        $perPage = $validated['per_page'] ?? 15;
        
        // Tìm kiếm theo loại
        switch ($type) {
            case 'tasks':
                return $this->searchTasks($request, $query, $perPage);
            case 'teams':
                return $this->searchTeams($query, $perPage);
            case 'users':
                return $this->searchUsers($query, $perPage);
            case 'all':
            default:
                return $this->searchAll($query, $perPage);
        }
    }
    
    /**
     * Tìm kiếm công việc
     */
    private function searchTasks(Request $request, $query, $perPage)
    {
        $userId = Auth::id();
        
        // Bắt đầu query
        $tasksQuery = Task::query();
        
        // Tìm kiếm theo từ khóa
        if (!empty($query)) {
            $tasksQuery->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });
        }
        
        // Chỉ lấy task của user hiện tại hoặc task của team mà user là thành viên
        $tasksQuery->where(function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereHas('team', function($q) use ($userId) {
                  $q->whereHas('members', function($q) use ($userId) {
                      $q->where('user_id', $userId);
                  });
              });
        });
        
        // Lọc theo team
        if ($request->has('team_id')) {
            $teamId = $request->input('team_id');
            $tasksQuery->where('team_id', $teamId);
        }
        
        // Lọc theo trạng thái
        if ($request->has('status')) {
            $statuses = $request->input('status');
            $tasksQuery->whereIn('status', $statuses);
        }
        
        // Lọc theo mức độ ưu tiên
        if ($request->has('priority')) {
            $priorities = $request->input('priority');
            $tasksQuery->whereIn('priority', $priorities);
        }
        
        // Lọc theo người được gán
        if ($request->has('assigned_to')) {
            $assignedTo = $request->input('assigned_to');
            $tasksQuery->whereHas('assignments', function($q) use ($assignedTo) {
                $q->where('user_id', $assignedTo);
            });
        }
        
        // Lọc theo người tạo
        if ($request->has('created_by')) {
            $createdBy = $request->input('created_by');
            $tasksQuery->where('created_by', $createdBy);
        }
        
        // Lọc theo ngày tạo
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $tasksQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        // Lọc theo ngày hết hạn
        if ($request->has('due_date_start') && $request->has('due_date_end')) {
            $dueDateStart = Carbon::parse($request->input('due_date_start'));
            $dueDateEnd = Carbon::parse($request->input('due_date_end'));
            $tasksQuery->whereBetween('due_date', [$dueDateStart, $dueDateEnd]);
        }
        
        // Lọc theo trạng thái hoàn thành
        if ($request->has('completed')) {
            $completed = $request->input('completed');
            if ($completed) {
                $tasksQuery->whereNotNull('completed_at');
            } else {
                $tasksQuery->whereNull('completed_at');
            }
        }
        
        // Lọc theo nhãn
        if ($request->has('label_id')) {
            $labelId = $request->input('label_id');
            $tasksQuery->whereHas('labels', function($q) use ($labelId) {
                $q->where('label_id', $labelId);
            });
        }
        
        // Lọc theo tệp đính kèm
        if ($request->has('has_attachments')) {
            $hasAttachments = $request->input('has_attachments');
            if ($hasAttachments) {
                $tasksQuery->has('attachments');
            }
        }
        
        // Lọc theo công việc con
        if ($request->has('has_subtasks')) {
            $hasSubtasks = $request->input('has_subtasks');
            if ($hasSubtasks) {
                $tasksQuery->has('subtasks');
            }
        }
        
        // Sắp xếp
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $tasksQuery->orderBy($sortBy, $sortDirection);
        
        // Phân trang
        $tasks = $tasksQuery->with(['team', 'labels', 'assignments.user'])->paginate($perPage);
        
        return response()->json([
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total()
            ]
        ]);
    }
    
    /**
     * Tìm kiếm nhóm
     */
    private function searchTeams($query, $perPage)
    {
        $userId = Auth::id();
        
        $teamsQuery = Team::query();
        
        // Tìm kiếm theo từ khóa
        if (!empty($query)) {
            $teamsQuery->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });
        }
        
        // Chỉ lấy team mà user là thành viên
        $teamsQuery->whereHas('members', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
        
        // Sắp xếp và phân trang
        $teams = $teamsQuery->orderBy('name')->paginate($perPage);
        
        return response()->json([
            'data' => $teams->items(),
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total()
            ]
        ]);
    }
    
    /**
     * Tìm kiếm người dùng
     */
    private function searchUsers($query, $perPage)
    {
        $userId = Auth::id();
        
        $usersQuery = User::query();
        
        // Tìm kiếm theo từ khóa
        if (!empty($query)) {
            $usersQuery->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
        }
        
        // Chỉ lấy người dùng trong các team mà user hiện tại là thành viên
        $usersQuery->whereHas('teams', function($q) use ($userId) {
            $q->whereHas('members', function($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        });
        
        // Sắp xếp và phân trang
        $users = $usersQuery->orderBy('name')->paginate($perPage);
        
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ]
        ]);
    }
    
    /**
     * Tìm kiếm tất cả
     */
    private function searchAll($query, $perPage)
    {
        if (empty($query)) {
            return response()->json([
                'message' => 'Search query is required for all type search'
            ], 422);
       