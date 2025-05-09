<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    /**
     * Lấy thống kê tổng quan
     */
    public function overview()
    {
        $user = Auth::user();
        $userId = $user->id;
        
        // Lấy danh sách team của user
        $teamIds = $user->teams()->pluck('teams.id')->toArray();
        
        // Đếm số task cá nhân
        $personalTasksCount = Task::where('user_id', $userId)
            ->where('team_id', null)
            ->count();
            
        // Đếm số task cá nhân đã hoàn thành
        $completedPersonalTasksCount = Task::where('user_id', $userId)
            ->where('team_id', null)
            ->whereNotNull('completed_at')
            ->count();
            
        // Đếm số task cá nhân quá hạn
        $overduePersonalTasksCount = Task::where('user_id', $userId)
            ->where('team_id', null)
            ->whereNull('completed_at')
            ->where('due_date', '<', now())
            ->count();
            
        // Đếm số task nhóm được gán
        $assignedTeamTasksCount = Task::whereIn('team_id', $teamIds)
            ->whereHas('assignments', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();
            
        // Đếm số task nhóm đã hoàn thành
        $completedTeamTasksCount = Task::whereIn('team_id', $teamIds)
            ->whereHas('assignments', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereNotNull('completed_at')
            ->count();
            
        // Đếm số task nhóm quá hạn
        $overdueTeamTasksCount = Task::whereIn('team_id', $teamIds)
            ->whereHas('assignments', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereNull('completed_at')
            ->where('due_date', '<', now())
            ->count();
            
        // Đếm số nhóm
        $teamsCount = count($teamIds);
        
        // Tính tỷ lệ hoàn thành
        $personalCompletionRate = $personalTasksCount > 0 
            ? round(($completedPersonalTasksCount / $personalTasksCount) * 100, 1) 
            : 0;
            
        $teamCompletionRate = $assignedTeamTasksCount > 0 
            ? round(($completedTeamTasksCount / $assignedTeamTasksCount) * 100, 1) 
            : 0;
            
        $overallCompletionRate = ($personalTasksCount + $assignedTeamTasksCount) > 0 
            ? round((($completedPersonalTasksCount + $completedTeamTasksCount) / ($personalTasksCount + $assignedTeamTasksCount)) * 100, 1) 
            : 0;
            
        return response()->json([
            'data' => [
                'personal_tasks' => [
                    'total' => $personalTasksCount,
                    'completed' => $completedPersonalTasksCount,
                    'overdue' => $overduePersonalTasksCount,
                    'completion_rate' => $personalCompletionRate
                ],
                'team_tasks' => [
                    'total' => $assignedTeamTasksCount,
                    'completed' => $completedTeamTasksCount,
                    'overdue' => $overdueTeamTasksCount,
                    'completion_rate' => $teamCompletionRate
                ],
                'overall' => [
                    'total_tasks' => $personalTasksCount + $assignedTeamTasksCount,
                    'completion_rate' => $overallCompletionRate,
                    'teams_count' => $teamsCount
                ]
            ]
        ]);
    }
    
    /**
     * Lấy thống kê theo thời gian
     */
    public function timeStats(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:day,week,month,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);
        
        $user = Auth::user();
        $userId = $user->id;
        $period = $validated['period'];
        
        // Xác định khoảng thời gian
        $startDate = isset($validated['start_date']) 
            ? Carbon::parse($validated['start_date']) 
            : Carbon::now()->startOfMonth();
            
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : Carbon::now();
            
        // Lấy danh sách team của user
        $teamIds = $user->teams()->pluck('teams.id')->toArray();
        
        // Lấy task cá nhân và nhóm trong khoảng thời gian
        $personalTasks = Task::where('user_id', $userId)
            ->where('team_id', null)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        $teamTasks = Task::whereIn('team_id', $teamIds)
            ->whereHas('assignments', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        // Gộp task
        $allTasks = $personalTasks->merge($teamTasks);
        
        // Phân nhóm theo thời gian
        $groupedTasks = [];
        
        switch ($period) {
            case 'day':
                // Phân nhóm theo giờ
                foreach ($allTasks as $task) {
                    $hour = $task->created_at->format('H');
                    $key = $hour . ':00';
                    
                    if (!isset($groupedTasks[$key])) {
                        $groupedTasks[$key] = [
                            'period' => $key,
                            'count' => 0,
                            'completed' => 0
                        ];
                    }
                    
                    $groupedTasks[$key]['count']++;
                    
                    if ($task->completed_at) {
                        $groupedTasks[$key]['completed']++;
                    }
                }
                break;
                
            case 'week':
                // Phân nhóm theo ngày trong tuần
                foreach ($allTasks as $task) {
                    $day = $task->created_at->format('l');
                    
                    if (!isset($groupedTasks[$day])) {
                        $groupedTasks[$day] = [
                            'period' => $day,
                            'count' => 0,
                            'completed' => 0
                        ];
                    }
                    
                    $groupedTasks[$day]['count']++;
                    
                    if ($task->completed_at) {
                        $groupedTasks[$day]['completed']++;
                    }
                }
                break;
                
            case 'month':
                // Phân nhóm theo ngày trong tháng
                foreach ($allTasks as $task) {
                    $day = $task->created_at->format('d');
                    
                    if (!isset($groupedTasks[$day])) {
                        $groupedTasks[$day] = [
                            'period' => $day,
                            'count' => 0,
                            'completed' => 0
                        ];
                    }
                    
                    $groupedTasks[$day]['count']++;
                    
                    if ($task->completed_at) {
                        $groupedTasks[$day]['completed']++;
                    }
                }
                break;
                
            case 'year':
                // Phân nhóm theo tháng
                foreach ($allTasks as $task) {
                    $month = $task->created_at->format('M');
                    
                    if (!isset($groupedTasks[$month])) {
                        $groupedTasks[$month] = [
                            'period' => $month,
                            'count' => 0,
                            'completed' => 0
                        ];
                    }
                    
                    $groupedTasks[$month]['count']++;
                    
                    if ($task->completed_at) {
                        $groupedTasks[$month]['completed']++;
                    }
                }
                break;
        }
        
        return response()->json([
            'data' => array_values($groupedTasks)
        ]);
    }
    
    /**
     * Lấy thống kê theo mức độ ưu tiên
     */
    public function priorityStats()
    {
        $user = Auth::user();
        $userId = $user->id;
        
        // Lấy danh sách team của user
        $teamIds = $user->teams()->pluck('teams.id')->toArray();
        
        // Đếm task cá nhân theo mức độ ưu tiên
        $personalTasksByPriority = Task::where('user_id', $userId)
            ->where('team_id', null)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->keyBy('priority')
            ->map(function ($item) {
                return $item->count;
            })
            ->toArray();
            
        // Đếm task nhóm theo mức độ ưu tiên
        $teamTasksByPriority = Task::whereIn('team_id', $teamIds)
            ->whereHas('assignments', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->keyBy('priority')
            ->map(function ($item) {
                return $item->count;
            })
            ->toArray();
            
        // Gộp kết quả
        $priorities = ['low', 'medium', 'high'];
        $result = [];
        
        foreach ($priorities as $priority) {
            $personalCount = $personalTasksByPriority[$priority] ?? 0;
            $teamCount = $teamTasksByPriority[$priority] ?? 0;
            
            $result[] = [
                'priority' => $priority,
                'personal_count' => $personalCount,
                'team_count' => $teamCount,
                'total_count' => $personalCount + $teamCount
            ];
        }
        
        return response()->json([
            'data' => $result
        ]);
    }
    
    /**
     * Lấy thống kê theo trạng thái
     */
    public function statusStats()
    {
        $user = Auth::user();
        $userId = $user->id;
        
        // Lấy danh sách team của user
        $teamIds = $user->teams()->pluck('teams.id')->toArray();
        
        // Đếm task cá nhân theo trạng thái
        $personalTasksByStatus = Task::where('user_id', $userId)
            ->where('team_id', null)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(function ($item) {
                return $item->count;
            })
            ->toArray();
            
        // Đếm task nhóm theo trạng thái
        $teamTasksByStatus = Task::whereIn('team_id', $teamIds)
            ->whereHas('assignments', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(function ($item) {
                return $item->count;
            })
            ->toArray();
            
        // Gộp kết quả
        $statuses = ['pending', 'in_progress', 'review', 'completed'];
        $result = [];
        
        foreach ($statuses as $status) {
            $personalCount = $personalTasksByStatus[$status] ?? 0;
            $teamCount = $teamTasksByStatus[$status] ?? 0;
            
            $result[] = [
                'status' => $status,
                'personal_count' => $personalCount,
                'team_count' => $teamCount,
                'total_count' => $personalCount + $teamCount
            ];
        }
        
        return response()->json([
            'data' => $result
        ]);
    }
    
    /**
     * Lấy thống kê theo nhóm
     */
    public function teamStats()
    {
        $user = Auth::user();
        
        // Lấy danh sách team của user
        $teams = $user->teams()->get();
        
        $result = [];
        
        foreach ($teams as $team) {
            // Đếm tổng số task
            $totalTasks = $team->tasks()->count();
            
            // Đếm số task đã hoàn thành
            $completedTasks = $team->tasks()->whereNotNull('completed_at')->count();
            
            // Đếm số task quá hạn
            $overdueTasks = $team->tasks()
                ->whereNull('completed_at')
                ->where('due_date', '<', now())
                ->count();
                
            // Đếm số task được gán cho user
            $assignedTasks = $team->tasks()
                ->whereHas('assignments', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->count();
                
            // Tính tỷ lệ hoàn thành
            $completionRate = $totalTasks > 0 
                ? round(($completedTasks / $totalTasks) * 100, 1) 
                : 0;
                
            $result[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'overdue_tasks' => $overdueTasks,
                'assigned_tasks' => $assignedTasks,
                'completion_rate' => $completionRate,
                'members_count' => $team->members()->count()
            ];
        }
        
        return response()->json([
            'data' => $result
        ]);
    }
    
    /**
     * Lấy thống kê hiệu suất cá nhân
     */
    public function personalPerformance(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:week,month,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);
        
        $user = Auth::user();
        $userId = $user->id;
        $period = $validated['period'];
        
        // Xác định khoảng thời gian
        $startDate = isset($validated['start_date']) 
            ? Carbon::parse($validated['start_date']) 
            : Carbon::now()->subDays(30);
            
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : Carbon::now();
            
        // Lấy danh sách team của user
        $teamIds = $user->teams()->pluck('teams.id')->toArray();
        
        // Lấy task cá nhân và nhóm đã hoàn thành trong khoảng thời gian
        $personalTasks = Task::where('user_id', $userId)
            ->where('team_id', null)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();
            
        $teamTasks = Task::whereIn('team_id', $teamIds)
            ->whereHas('assignments', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();
            
        // Gộp task
        $allTasks = $personalTasks->merge($teamTasks);
        
        // Tính thời gian hoàn thành trung bình
        $totalCompletionTime = 0;
        $tasksWithDueDate = 0;
        $completedBeforeDue = 0;
        
        foreach ($allTasks as $task) {
            if ($task->due_date) {
                $tasksWithDueDate++;
                
                $dueDate = Carbon::parse($task->due_date);
                $completedDate = Carbon::parse($task->completed_at);
                
                if ($completedDate->lessThanOrEqualTo($dueDate)) {
                    $completedBeforeDue++;
                }
                
                $createdDate = Carbon::parse($task->created_at);
                $completionTime = $completedDate->diffInHours($createdDate);
                $totalCompletionTime += $completionTime;
            }
        }
        
        $avgCompletionTime = $tasksWithDueDate > 0 
            ? round($totalCompletionTime / $tasksWithDueDate, 1) 
            : 0;
            
        $onTimeRate = $tasksWithDueDate > 0 
            ? round(($completedBeforeDue / $tasksWithDueDate) * 100, 1) 
            : 0;
            
        // Tính số task hoàn thành theo thời gian
        $completedByTime = [];
        
        switch ($period) {
            case 'week':
                // Phân nhóm theo ngày trong tuần
                foreach ($allTasks as $task) {
                    $day = Carbon::parse($task->completed_at)->format('l');
                    
                    if (!isset($completedByTime[$day])) {
                        $completedByTime[$day] = 0;
                    }
                    
                    $completedByTime[$day]++;
                }
                break;
                
            case 'month':
                // Phân nhóm theo ngày trong tháng
                foreach ($allTasks as $task) {
                    $day = Carbon::parse($task->completed_at)->format('d');
                    
                    if (!isset($completedByTime[$day])) {
                        $completedByTime[$day] = 0;
                    }
                    
                    $completedByTime[$day]++;
                }
                break;
                
            case 'year':
                // Phân nhóm theo tháng
                foreach ($allTasks as $task) {
                    $month = Carbon::parse($task->completed_at)->format('M');
                    
                    if (!isset($completedByTime[$month])) {
                        $completedByTime[$month] = 0;
                    }
                    
                    $completedByTime[$month]++;
                }
                break;
        }
        
        // Chuyển đổi thành mảng
        $completionTimeline = [];
        foreach ($completedByTime as $time => $count) {
            $completionTimeline[] = [
                'period' => $time,
                'count' => $count
            ];
        }
        
        return response()->json([
            'data' => [
                'total_completed' => count($allTasks),
                'avg_completion_time_hours' => $avgCompletionTime,
                'on_time_completion_rate' => $onTimeRate,
                'completion_timeline' => $completionTimeline
            ]
        ]);
    }
}

