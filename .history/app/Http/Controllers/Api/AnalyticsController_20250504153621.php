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

    /**
     * Export analytics report
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,csv',
            'period' => 'required|in:week,month,year',
            'report_type' => 'required|in:personal,team,productivity',
            'team_id' => 'required_if:report_type,team|exists:teams,id'
        ]);

        $user = $request->user();
        $period = $request->period;
        $format = $request->format;
        $reportType = $request->report_type;

        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();

        // Generate report data based on report type
        switch ($reportType) {
            case 'personal':
                $reportData = $this->generatePersonalReport($user->id, $startDate, $endDate);
                $reportTitle = 'Personal Task Report';
                break;
            case 'team':
                // Verify user is a member of the team
                $team = $user->teams()->find($request->team_id);
                if (!$team) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
                $reportData = $this->generateTeamReport($team->id, $startDate, $endDate);
                $reportTitle = 'Team Performance Report: ' . $team->name;
                break;
            case 'productivity':
                $reportData = $this->generateProductivityReport($user->id, $startDate, $endDate);
                $reportTitle = 'Productivity Report';
                break;
        }

        // Export based on format
        if ($format === 'csv') {
            return $this->exportToCsv($reportData, $reportTitle, $period);
        } else {
            // For PDF, we'll return a JSON response with the data
            // In a real implementation, you would generate a PDF
            return response()->json([
                'message' => 'PDF export is not implemented in this example',
                'report_data' => $reportData,
                'report_title' => $reportTitle,
                'period' => $period,
                'start_date' => $startDate->toIso8601String(),
                'end_date' => $endDate->toIso8601String()
            ]);
        }
    }

    /**
     * Generate personal report data
     */
    private function generatePersonalReport($userId, $startDate, $endDate)
    {
        // Get personal task stats
        $personalTaskStats = $this->getPersonalTaskStats($userId, $startDate, $endDate);

        // Get timeline data
        $timelineData = PersonalTask::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'created_at' => $task->created_at->toIso8601String(),
                    'deadline' => $task->deadline ? $task->deadline->toIso8601String() : null,
                    'completed_at' => $task->status === 'completed' ? $task->updated_at->toIso8601String() : null
                ];
            });

        return [
            'summary' => $personalTaskStats,
            'tasks' => $timelineData
        ];
    }

    /**
     * Generate team report data
     */
    private function generateTeamReport($teamId, $startDate, $endDate)
    {
        // Get team stats
        $totalTasks = TeamTask::where('team_id', $teamId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $completedTasks = TeamTask::where('team_id', $teamId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $teamCompletionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        // Get member stats
        $memberStats = DB::table('team_members')
            ->join('users', 'team_members.user_id', '=', 'users.id')
            ->leftJoin('team_task_assignments', 'team_members.user_id', '=', 'team_task_assignments.user_id')
            ->leftJoin('team_tasks', function($join) use ($teamId, $startDate, $endDate) {
                $join->on('team_task_assignments.task_id', '=', 'team_tasks.id')
                    ->where('team_tasks.team_id', '=', $teamId)
                    ->whereBetween('team_tasks.created_at', [$startDate, $endDate]);
            })
            ->where('team_members.team_id', $teamId)
            ->select(
                'users.id as user_id',
                'users.name',
                DB::raw('COUNT(DISTINCT team_tasks.id) as tasks_assigned'),
                DB::raw('SUM(CASE WHEN team_tasks.status = "completed" THEN 1 ELSE 0 END) as tasks_completed')
            )
            ->groupBy('users.id', 'users.name')
            ->get()
            ->map(function($member) {
                $completionRate = $member->tasks_assigned > 0
                    ? ($member->tasks_completed / $member->tasks_assigned) * 100
                    : 0;

                return [
                    'user_id' => $member->user_id,
                    'name' => $member->name,
                    'tasks_assigned' => $member->tasks_assigned,
                    'tasks_completed' => $member->tasks_completed,
                    'completion_rate' => round($completionRate, 1)
                ];
            });

        // Get task list
        $tasks = TeamTask::where('team_id', $teamId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['assignments.assignedTo'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'created_at' => $task->created_at->toIso8601String(),
                    'deadline' => $task->deadline ? $task->deadline->toIso8601String() : null,
                    'assigned_to' => $task->assignments->map(function($assignment) {
                        return [
                            'user_id' => $assignment->assignedTo->id,
                            'name' => $assignment->assignedTo->name
                        ];
                    })
                ];
            });

        return [
            'summary' => [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => round($teamCompletionRate, 1)
            ],
            'member_stats' => $memberStats,
            'tasks' => $tasks
        ];
    }

    /**
     * Generate productivity report data
     */
    private function generateProductivityReport($userId, $startDate, $endDate)
    {
        // Calculate completion rate
        $personalTasksTotal = PersonalTask::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $personalTasksCompleted = PersonalTask::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $teamTasksTotal = TeamTaskAssignment::where('user_id', $userId)
            ->whereHas('task', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->count();

        $teamTasksCompleted = TeamTaskAssignment::where('user_id', $userId)
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
        $overdueCount = PersonalTask::where('user_id', $userId)
            ->where('status', 'overdue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $overdueCount += TeamTaskAssignment::where('user_id', $userId)
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

        // Get daily productivity data
        $dailyData = $this->getTimelineStats($userId, 'day', $startDate, $endDate);

        return [
            'summary' => [
                'productivity_score' => round($productivityScore, 1),
                'completion_rate' => round($completionRate, 1),
                'on_time_rate' => round($onTimeRate, 1),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks
            ],
            'daily_data' => $dailyData
        ];
    }

    /**
     * Export report data to CSV
     */
    private function exportToCsv($reportData, $reportTitle, $period)
    {
        $csv = "{$reportTitle} - Period: {$period}\n\n";

        // Add summary section
        $csv .= "SUMMARY\n";
        foreach ($reportData['summary'] as $key => $value) {
            $csv .= str_replace('_', ' ', ucfirst($key)) . ": {$value}\n";
        }
        $csv .= "\n";

        // Add tasks section if available
        if (isset($reportData['tasks']) && count($reportData['tasks']) > 0) {
            $csv .= "TASKS\n";

            // Headers
            $headers = array_keys($reportData['tasks'][0]);
            $csv .= implode(',', array_map(function($header) {
                return '"' . str_replace('_', ' ', ucfirst($header)) . '"';
            }, $headers)) . "\n";

            // Data rows
            foreach ($reportData['tasks'] as $task) {
                $row = [];
                foreach ($headers as $header) {
                    if ($header === 'assigned_to' && is_array($task[$header])) {
                        $assignees = array_map(function($assignee) {
                            return $assignee['name'];
                        }, $task[$header]);
                        $row[] = '"' . str_replace('"', '""', implode(', ', $assignees)) . '"';
                    } else {
                        $row[] = '"' . str_replace('"', '""', $task[$header] ?? '') . '"';
                    }
                }
                $csv .= implode(',', $row) . "\n";
            }
        }

        // Add member stats if available
        if (isset($reportData['member_stats']) && count($reportData['member_stats']) > 0) {
            $csv .= "\nTEAM MEMBER PERFORMANCE\n";

            // Headers
            $headers = array_keys($reportData['member_stats'][0]);
            $csv .= implode(',', array_map(function($header) {
                return '"' . str_replace('_', ' ', ucfirst($header)) . '"';
            }, $headers)) . "\n";

            // Data rows
            foreach ($reportData['member_stats'] as $member) {
                $row = [];
                foreach ($headers as $header) {
                    $row[] = '"' . str_replace('"', '""', $member[$header] ?? '') . '"';
                }
                $csv .= implode(',', $row) . "\n";
            }
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . str_replace(' ', '_', strtolower($reportTitle)) . '.csv"');
    }
}
