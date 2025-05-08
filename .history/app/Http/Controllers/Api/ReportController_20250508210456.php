<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Báo cáo burndown chart
     */
    public function burndownChart(Request $request, Team $team)
    {
        // Kiểm tra quyền truy cập
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        // Tính tổng số task ban đầu và số task đã hoàn thành theo ngày
        $initialTasks = $team->tasks()
            ->where('created_at', '<', $startDate)
            ->count();
            
        $tasksByDay = $team->tasks()
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->orWhereBetween('completed_at', [$startDate, $endDate]);
            })
            ->get();
            
        // Tạo mảng dữ liệu cho từng ngày
        $dateRange = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dateRange[$dateString] = [
                'date' => $dateString,
                'ideal' => 0,
                'actual' => 0
            ];
            $currentDate->addDay();
        }
        
        // Tính toán đường lý tưởng (ideal)
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $taskPerDay = $initialTasks / $totalDays;
        
        $remainingTasks = $initialTasks;
        $currentDate = clone $startDate;
        $i = 0;
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $remainingTasks = max(0, $initialTasks - ($taskPerDay * $i));
            $dateRange[$dateString]['ideal'] = round($remainingTasks, 1);
            $currentDate->addDay();
            $i++;
        }
        
        // Tính toán đường thực tế (actual)
        $remainingTasks = $initialTasks;
        $currentDate = clone $startDate;
        
        // Thêm task mới vào tổng
        foreach ($tasksByDay as $task) {
            if ($task->created_at >= $startDate && $task->created_at <= $endDate) {
                $createdDate = $task->created_at->format('Y-m-d');
                $remainingTasks++;
            }
        }
        
        // Tính số task còn lại theo ngày
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Trừ các task đã hoàn thành trong ngày này
            $completedToday = $tasksByDay->filter(function($task) use ($currentDate) {
                return $task->completed_at && 
                       $task->completed_at->format('Y-m-d') === $currentDate->format('Y-m-d');
            })->count();
            
            $remainingTasks -= $completedToday;
            $dateRange[$dateString]['actual'] = max(0, $remainingTasks);
            
            $currentDate->addDay();
        }
        
        return response()->json([
            'data' => array_values($dateRange)
        ]);
    }
    
    /**
     * Báo cáo velocity chart
     */
    public function velocityChart(Request $request, Team $team)
    {
        // Kiểm tra quyền truy cập
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'periods' => 'required|integer|min:1|max:12',
            'period_type' => 'required|in:week,month'
        ]);
        
        $periods = $validated['periods'];
        $periodType = $validated['period_type'];
        
        $endDate = Carbon::now();
        $startDate = clone $endDate;
        
        if ($periodType === 'week') {
            $startDate->subWeeks($periods);
        } else {
            $startDate->subMonths($periods);
        }
        
        $result = [];
        $currentDate = clone $startDate;
        
        for ($i = 0; $i < $periods; $i++) {
            $periodStart = clone $currentDate;
            $periodEnd = clone $currentDate;
            
            if ($periodType === 'week') {
                $periodEnd->addWeek()->subDay();
                $periodLabel = 'Week ' . $periodStart->format('W');
            } else {
                $periodEnd->addMonth()->subDay();
                $periodLabel = $periodStart->format('M Y');
            }
            
            // Đếm số task hoàn thành trong kỳ
            $completedTasks = $team->tasks()
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$periodStart, $periodEnd])
                ->count();
                
            $result[] = [
                'period' => $periodLabel,
                'start_date' => $periodStart->format('Y-m-d'),
                'end_date' => $periodEnd->format('Y-m-d'),
                'completed_tasks' => $completedTasks
            ];
            
            if ($periodType === 'week') {
                $currentDate->addWeek();
            } else {
                $currentDate->addMonth();
            }
        }
        
        return response()->json([
            'data' => $result
        ]);
    }
    
    /**
     * Báo cáo phân phối công việc theo thành viên
     */
    public function memberDistribution(Team $team)
    {
        // Kiểm tra quyền truy cập
        if (!$team->members()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $members = $team->members()->with('user')->get();
        $result = [];
        
        foreach ($members as $member) {
            $userId = $member->user_id;
            $userName = $member->user->name;
            
            // Đếm số task đã gán cho thành viên
            $assignedTasks = $team->tasks()
                ->whereHas('assignments', function($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->count();
                
            // Đếm số task đã hoàn thành
            $completedTasks = $team->tasks()
                ->whereNotNull('completed_at')
                ->whereHas('assignments', function($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->count();
                
            // Đếm số task quá hạn
            $overdueTasks = $team->tasks()
                ->whereNull('completed_at')
                ->where('due_date', '<', Carbon::now())
                ->whereHas('assignments', function($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->count();
                
            $result[] = [
                'user_id' => $userId,
                'name' => $userName,
                'assigned_tasks' => $assignedTasks,
                'completed_tasks' => $completedTasks,
                'overdue_tasks' => $overdueTasks,
                'completion_rate' => $assignedTasks > 0 ? round(($completedTasks / $assignedTasks) * 100, 1) : 0
            ];
        }
        
        return response()->json([
            'data' => $result
        ]);
    }
}
