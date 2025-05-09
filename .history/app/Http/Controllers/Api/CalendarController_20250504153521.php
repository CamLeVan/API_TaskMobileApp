<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalTask;
use App\Models\TeamTaskAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Get tasks by date range
     */
    public function getTasksByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $user = $request->user();
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Get personal tasks in date range
        $personalTasks = PersonalTask::where('user_id', $user->id)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('deadline', [$startDate, $endDate])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
            })
            ->get()
            ->map(function($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'start' => $task->deadline ? $task->deadline->toIso8601String() : $task->created_at->toIso8601String(),
                    'allDay' => true,
                    'type' => 'personal',
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'description' => $task->description
                ];
            });

        // Get team tasks in date range
        $teamTasks = TeamTaskAssignment::where('user_id', $user->id)
            ->whereHas('task', function($query) use ($startDate, $endDate) {
                $query->where(function($q) use ($startDate, $endDate) {
                    $q->whereBetween('deadline', [$startDate, $endDate])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
                });
            })
            ->with(['task', 'task.team'])
            ->get()
            ->map(function($assignment) {
                $task = $assignment->task;
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'start' => $task->deadline ? $task->deadline->toIso8601String() : $task->created_at->toIso8601String(),
                    'allDay' => true,
                    'type' => 'team',
                    'team_id' => $task->team_id,
                    'team_name' => $task->team->name,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'description' => $task->description
                ];
            });

        // Combine all events
        $events = $personalTasks->concat($teamTasks);

        return response()->json($events);
    }

    /**
     * Get tasks for a specific date
     */
    public function getTasksByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $user = $request->user();
        $date = Carbon::parse($request->date);
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        // Get personal tasks for the date
        $personalTasks = PersonalTask::where('user_id', $user->id)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('deadline', [$startDate, $endDate]);
            })
            ->get();

        // Get team tasks for the date
        $teamTasks = TeamTaskAssignment::where('user_id', $user->id)
            ->whereHas('task', function($query) use ($startDate, $endDate) {
                $query->whereBetween('deadline', [$startDate, $endDate]);
            })
            ->with(['task', 'task.team'])
            ->get()
            ->pluck('task');

        return response()->json([
            'date' => $date->toDateString(),
            'personal_tasks' => $personalTasks,
            'team_tasks' => $teamTasks
        ]);
    }

    /**
     * Update calendar sync settings
     */
    public function updateCalendarSync(Request $request)
    {
        $request->validate([
            'google_calendar' => 'sometimes|boolean',
            'outlook_calendar' => 'sometimes|boolean',
            'sync_personal_tasks' => 'sometimes|boolean',
            'sync_team_tasks' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        // Get or create settings
        $settings = $user->settings;

        if (!$settings) {
            $settings = $user->settings()->create([
                'theme' => 'light',
                'language' => 'en'
            ]);
        }

        // Update calendar sync settings
        $calendarSync = $settings->calendar_sync ?: [];

        if ($request->has('google_calendar')) {
            $calendarSync['google_calendar'] = $request->google_calendar;
        }

        if ($request->has('outlook_calendar')) {
            $calendarSync['outlook_calendar'] = $request->outlook_calendar;
        }

        if ($request->has('sync_personal_tasks')) {
            $calendarSync['sync_personal_tasks'] = $request->sync_personal_tasks;
        }

        if ($request->has('sync_team_tasks')) {
            $calendarSync['sync_team_tasks'] = $request->sync_team_tasks;
        }

        $settings->calendar_sync = $calendarSync;
        $settings->save();

        return response()->json([
            'message' => 'Calendar sync settings updated',
            'calendar_sync' => $calendarSync
        ]);
    }

    /**
     * Export calendar to iCalendar format
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:ical,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $user = $request->user();
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $format = $request->format;

        // Get personal tasks
        $personalTasks = PersonalTask::where('user_id', $user->id)
            ->whereBetween('deadline', [$startDate, $endDate])
            ->get();

        // Get team tasks
        $teamTasks = TeamTaskAssignment::where('user_id', $user->id)
            ->whereHas('task', function($query) use ($startDate, $endDate) {
                $query->whereBetween('deadline', [$startDate, $endDate]);
            })
            ->with(['task', 'task.team'])
            ->get();

        if ($format === 'ical') {
            // Create iCalendar
            $calendar = "BEGIN:VCALENDAR\r\n";
            $calendar .= "VERSION:2.0\r\n";
            $calendar .= "PRODID:-//TaskApp//EN\r\n";
            $calendar .= "CALSCALE:GREGORIAN\r\n";
            $calendar .= "METHOD:PUBLISH\r\n";

            // Add personal tasks
            foreach ($personalTasks as $task) {
                $calendar .= "BEGIN:VEVENT\r\n";
                $calendar .= "UID:" . uniqid('personal_task_') . "\r\n";
                $calendar .= "SUMMARY:" . $this->escapeIcalText($task->title) . "\r\n";
                if ($task->description) {
                    $calendar .= "DESCRIPTION:" . $this->escapeIcalText($task->description) . "\r\n";
                }
                $calendar .= "DTSTART;VALUE=DATE:" . $task->deadline->format('Ymd') . "\r\n";
                $calendar .= "DTEND;VALUE=DATE:" . $task->deadline->addDay()->format('Ymd') . "\r\n";
                $calendar .= "CATEGORIES:Personal Task\r\n";
                $calendar .= "END:VEVENT\r\n";
            }

            // Add team tasks
            foreach ($teamTasks as $assignment) {
                $task = $assignment->task;
                $calendar .= "BEGIN:VEVENT\r\n";
                $calendar .= "UID:" . uniqid('team_task_') . "\r\n";
                $calendar .= "SUMMARY:" . $this->escapeIcalText($task->title) . " (" . $task->team->name . ")\r\n";
                if ($task->description) {
                    $calendar .= "DESCRIPTION:" . $this->escapeIcalText($task->description) . "\r\n";
                }
                $calendar .= "DTSTART;VALUE=DATE:" . $task->deadline->format('Ymd') . "\r\n";
                $calendar .= "DTEND;VALUE=DATE:" . $task->deadline->addDay()->format('Ymd') . "\r\n";
                $calendar .= "CATEGORIES:Team Task\r\n";
                $calendar .= "END:VEVENT\r\n";
            }

            $calendar .= "END:VCALENDAR\r\n";

            return response($calendar)
                ->header('Content-Type', 'text/calendar')
                ->header('Content-Disposition', 'attachment; filename="tasks.ics"');
        } else {
            // Create CSV
            $csv = "Title,Description,Deadline,Type,Team\n";

            // Add personal tasks
            foreach ($personalTasks as $task) {
                $csv .= '"' . str_replace('"', '""', $task->title) . '",';
                $csv .= '"' . str_replace('"', '""', $task->description ?? '') . '",';
                $csv .= '"' . $task->deadline->format('Y-m-d') . '",';
                $csv .= '"Personal",';
                $csv .= '""\n';
            }

            // Add team tasks
            foreach ($teamTasks as $assignment) {
                $task = $assignment->task;
                $csv .= '"' . str_replace('"', '""', $task->title) . '",';
                $csv .= '"' . str_replace('"', '""', $task->description ?? '') . '",';
                $csv .= '"' . $task->deadline->format('Y-m-d') . '",';
                $csv .= '"Team",';
                $csv .= '"' . str_replace('"', '""', $task->team->name) . '"\n';
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="tasks.csv"');
        }
    }

    /**
     * Helper method to escape text for iCalendar format
     */
    private function escapeIcalText($text)
    {
        $text = str_replace(["\\", "\n", ";", ","], ["\\\\", "\\n", "\\;", "\\,"], $text);
        return $text;
    }
}
