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
            $dateRange[$dateString]['actual'] = max(0,