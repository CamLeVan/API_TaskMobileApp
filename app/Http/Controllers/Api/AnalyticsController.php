<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalTask;
use App\Models\TeamTask;
use App\Models\TeamTaskAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get task statistics
     */
    public function getTaskStats(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', 'month'); // week, month, year
        
        // Set date range based on period
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();
        
        // Get personal task stats
        $personalTaskStats = $this->getPersonalTaskStats($user->id, $startDate, $endDate);
        
        // Get team task stats
        $teamTaskStats = $this->getTeamTaskStats($user->id, $startDate, $endDate);
        
        // Get timeline stats
        $timelineStats = $this->getTimelineStats($user->id, $period, $startDate, $endDate);
        
        return response()->json([
            'personal_tasks' => $personalTaskStats,
            'team_tasks' => $teamTaskStats,
            'timeline' => $timelineStats,
            'period' => $period,
            'start_date' => $startDate->toIso8601String(),
            'end_date' => $endDate->toIso8601String()
        ]);
    }
    
    /**
     * Get productivity score
     */
    public function getProductivityScore(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', 'month');
        
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();
        
        // Calculate completion rate
        $personalTasksTotal = PersonalTask::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $personalTasksCompleted = PersonalTask::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $teamTasksTotal = TeamTaskAssignment::where('user_id', $user->id)
            ->whereHas('task', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->count();
            
        $teamTasksCompleted = TeamTaskAssignment::where('user_id', $user->id)
            ->whereHas('task', function($q) {
                $q->where('status', 'completed');
            })
            ->whereHas('task', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->count();
            
        $totalTasks = $personalTasksTotal + $teamTasksTotal;
        $completedTasks = $personalTasksCompleted + $teamTasksCompleted;
        
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        
        // Calculate on-time completion rate
        $overdueCount = PersonalTask::where('user_id', $user->id)
            ->where('status', 'overdue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $overdueCount += TeamTaskAssignment::where('user_id', $user->id)
            ->whereHas('task', function($q) {
                $q->where('status', 'overdue');
            })
            ->whereHas('task', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->count();
            
        $onTimeRate = $completedTasks > 0 ? (($completedTasks - $overdueCount) / $completedTasks) * 100 : 0;
        
        // Calculate productivity score (weighted average)
        $productivityScore = ($completionRate * 0.7) + ($onTimeRate * 0.3);
        
        return response()->json([
            'productivity_score' => round($productivityScore, 1),
            'completion_rate' => round($completionRate, 1),
            'on_time_rate' => round($onTimeRate, 1),
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'period' => $period
        ]);
    }
    
    /**
     * Get team performance stats
     */
    public function getTeamPerformance(Request $request)
    {
        $user = $request->user();
        $teamId = $request->get('team_id');
        $period = $request->get('period', 'month');
        
        // Verify user is a member of the team
        $team = $user->teams()->find($teamId);
        if (!$team) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();
        
        // Get team members
        $members = $team->members()->with('user')->get();
        
        // Get stats for each member
        $memberStats = [];
        foreach ($members as $member) {
            $userId = $member->user_id;
            
            $tasksAssigned = TeamTaskAssignment::where('user_id', $userId)
                ->whereHas('task', function($q) use ($teamId) {
                    $q->where('team_id', $teamId);
                })
                ->whereHas('task', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->count();
                
            $tasksCompleted = TeamTaskAssignment::where('user_id', $userId)
                ->whereHas('task', function($q) use ($teamId) {
                    $q->where('team_id', $teamId);
                })
                ->whereHas('task', function($q) {
                    $q->where('status', 'completed');
                })
                ->whereHas('task', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->count();
                
            $completionRate = $tasksAssigned > 0 ? ($tasksCompleted / $tasksAssigned) * 100 : 0;
            
            $memberStats[] = [
                'user_id' => $userId,
                'name' => $member->user->name,
                'tasks_assigned' => $tasksAssigned,
                'tasks_completed' => $tasksCompleted,
                'completion_rate' => round($completionRate, 1)
            ];
        }
        
        // Get overall team stats
        $totalTasks = TeamTask::where('team_id', $teamId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $completedTasks = TeamTask::where('team_id', $teamId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $teamCompletionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        
        return response()->json([
            'team_id' => $teamId,
            'team_name' => $team->name,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'team_completion_rate' => round($teamCompletionRate, 1),
            'member_stats' => $memberStats,
            'period' => $period
        ]);
    }
    
    /**
     * Helper to get personal task stats
     */
    private function getPersonalTaskStats($userId, $startDate, $endDate)
    {
        $total = PersonalTask::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        $byStatus = PersonalTask::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
            
        $byPriority = PersonalTask::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority')
            ->toArray();
            
        return [
            'total' => $total,
            'by_status' => [
                'pending' => $byStatus['pending'] ?? 0,
                'in_progress' => $byStatus['in_progress'] ?? 0,
                'completed' => $byStatus['completed'] ?? 0,
                'overdue' => $byStatus['overdue'] ?? 0
            ],
            'by_priority' => $byPriority
        ];
    }
    
    /**
     * Helper to get team task stats
     */
    private function getTeamTaskStats($userId, $startDate, $endDate)
    {
        $assignments = TeamTaskAssignment::where('user_id', $userId)
            ->whereHas('task', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with('task')
            ->get();
            
        $total = $assignments->count();
        
        $byStatus = [];
        $byPriority = [];
        
        foreach ($assignments as $assignment) {
            $status = $assignment->task->status;
            $priority = $assignment->task->priority;
            
            if (!isset($byStatus[$status])) {
                $byStatus[$status] = 0;
            }
            $byStatus[$status]++;
            
            if (!isset($byPriority[$priority])) {
                $byPriority[$priority] = 0;
            }
            $byPriority[$priority]++;
        }
        
        return [
            'total' => $total,
            'by_status' => [
                'pending' => $byStatus['pending'] ?? 0,
                'in_progress' => $byStatus['in_progress'] ?? 0,
                'completed' => $byStatus['completed'] ?? 0,
                'overdue' => $byStatus['overdue'] ?? 0
            ],
            'by_priority' => $byPriority
        ];
    }
    
    /**
     * Helper to get timeline stats
     */
    private function getTimelineStats($userId, $period, $startDate, $endDate)
    {
        $format = $this->getDateFormat($period);
        $groupBy = $this->getGroupBy($period);
        
        // Get personal tasks by date
        $personalTasksByDate = PersonalTask::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw("DATE_FORMAT(created_at, '{$format}') as date"), 
                     DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
            
        // Get completed personal tasks by date
        $completedPersonalTasksByDate = PersonalTask::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw("DATE_FORMAT(created_at, '{$format}') as date"), 
                     DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
            
        // Generate all dates in the period
        $dates = $this->generateDates($period, $startDate, $endDate);
        
        $timeline = [];
        foreach ($dates as $date) {
            $formattedDate = $date->format($this->getPhpDateFormat($period));
            $timeline[] = [
                'date' => $formattedDate,
                'personal_tasks' => $personalTasksByDate[$formattedDate] ?? 0,
                'completed_personal_tasks' => $completedPersonalTasksByDate[$formattedDate] ?? 0
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Helper to get start date based on period
     */
    private function getStartDate($period)
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'week':
                return $now->copy()->subWeek();
            case 'month':
                return $now->copy()->subMonth();
            case 'year':
                return $now->copy()->subYear();
            default:
                return $now->copy()->subMonth();
        }
    }
    
    /**
     * Helper to get date format for SQL
     */
    private function getDateFormat($period)
    {
        switch ($period) {
            case 'week':
                return '%Y-%m-%d'; // Daily for week
            case 'month':
                return '%Y-%m-%d'; // Daily for month
            case 'year':
                return '%Y-%m'; // Monthly for year
            default:
                return '%Y-%m-%d';
        }
    }
    
    /**
     * Helper to get PHP date format
     */
    private function getPhpDateFormat($period)
    {
        switch ($period) {
            case 'week':
                return 'Y-m-d';
            case 'month':
                return 'Y-m-d';
            case 'year':
                return 'Y-m';
            default:
                return 'Y-m-d';
        }
    }
    
    /**
     * Helper to get group by clause
     */
    private function getGroupBy($period)
    {
        switch ($period) {
            case 'week':
                return 'day';
            case 'month':
                return 'day';
            case 'year':
                return 'month';
            default:
                return 'day';
        }
    }
    
    /**
     * Helper to generate all dates in the period
     */
    private function generateDates($period, $startDate, $endDate)
    {
        $dates = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $dates[] = $current->copy();
            
            switch ($period) {
                case 'week':
                case 'month':
                    $current->addDay();
                    break;
                case 'year':
                    $current->addMonth();
                    break;
                default:
                    $current->addDay();
            }
        }
        
        return $dates;
    }
}
