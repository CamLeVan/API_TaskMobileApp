<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupChatMessage;
use App\Models\MessageReadStatus;
use App\Models\NotificationQueue;
use App\Models\SyncStatus;
use App\Models\TeamTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    // Đồng bộ toàn bộ cho lần đầu hoặc khi có vấn đề
    public function initialSync(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        $user = $request->user();
        $deviceId = $request->device_id;

        // Get user's teams
        $teams = $user->teams()->with('members.user')->get();

        // Get recent messages for each team (limit 50 per team)
        $allMessages = [];
        foreach ($teams as $team) {
            $messages = $team->chatMessages()
                ->with(['sender', 'readStatuses'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $allMessages[$team->id] = $messages;
        }

        // Get personal tasks
        $personalTasks = $user->personalTasks()->get();

        // Get team tasks assigned to user
        $teamTasks = $user->teamTaskAssignments()
            ->with(['task', 'task.team'])
            ->get()
            ->pluck('task');

        // Update sync status
        SyncStatus::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            ['last_synced_at' => now()]
        );

        return response()->json([
            'teams' => $teams,
            'messages' => $allMessages,
            'personal_tasks' => $personalTasks,
            'team_tasks' => $teamTasks,
            'sync_time' => now()->toIso8601String()
        ]);
    }

    // Đồng bộ nhanh - chỉ lấy dữ liệu mới từ lần đồng bộ cuối
    public function quickSync(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'last_synced_at' => 'required|date',
            'include' => 'nullable|array',
            'include.*' => 'in:messages,tasks,teams'
        ]);

        $user = $request->user();
        $deviceId = $request->device_id;
        $lastSynced = Carbon::parse($request->last_synced_at);
        $includes = $request->include ?: ['messages', 'tasks', 'teams'];

        $response = ['sync_time' => now()->toIso8601String()];

        // Lấy danh sách team của user
        $teamIds = $user->teams()->pluck('teams.id')->toArray();

        // Đồng bộ tin nhắn mới
        if (in_array('messages', $includes)) {
            $messages = GroupChatMessage::whereIn('team_id', $teamIds)
                ->where('created_at', '>', $lastSynced)
                ->with(['sender', 'readStatuses'])
                ->orderBy('created_at', 'asc')
                ->get();

            $readStatuses = MessageReadStatus::whereHas('message', function($q) use ($teamIds) {
                    $q->whereIn('team_id', $teamIds);
                })
                ->where('created_at', '>', $lastSynced)
                ->get();

            $response['messages'] = $messages;
            $response['read_statuses'] = $readStatuses;
        }

        // Đồng bộ task cá nhân
        if (in_array('tasks', $includes)) {
            $personalTasks = $user->personalTasks()
                ->where('updated_at', '>', $lastSynced)
                ->get();

            $teamTasks = $user->teamTaskAssignments()
                ->whereHas('task', function($q) use ($lastSynced) {
                    $q->where('updated_at', '>', $lastSynced);
                })
                ->with(['task', 'task.team'])
                ->get()
                ->pluck('task');

            $response['personal_tasks'] = $personalTasks;
            $response['team_tasks'] = $teamTasks;
        }

        // Đồng bộ thay đổi trong teams
        if (in_array('teams', $includes)) {
            $teams = $user->teams()
                ->where('teams.updated_at', '>', $lastSynced)
                ->orWhereHas('members', function($q) use ($lastSynced) {
                    $q->where('updated_at', '>', $lastSynced);
                })
                ->with('members.user')
                ->get();

            $response['teams'] = $teams;
        }

        // Cập nhật trạng thái đồng bộ
        SyncStatus::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            ['last_synced_at' => now()]
        );

        return response()->json($response);
    }

    // Đẩy dữ liệu từ thiết bị lên server
    public function push(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'messages' => 'nullable|array',
            'read_statuses' => 'nullable|array',
            'personal_tasks' => 'nullable|array',
        ]);

        $user = $request->user();
        $deviceId = $request->device_id;

        DB::beginTransaction();

        try {
            // Xử lý tin nhắn offline
            if ($request->has('messages') && !empty($request->messages)) {
                foreach ($request->messages as $messageData) {
                    // Kiểm tra xem tin nhắn đã tồn tại chưa
                    $existingMessage = GroupChatMessage::where('client_temp_id', $messageData['client_temp_id'])
                        ->first();

                    if (!$existingMessage) {
                        $message = GroupChatMessage::create([
                            'team_id' => $messageData['team_id'],
                            'sender_id' => $user->id,
                            'message' => $messageData['message'],
                            'file_url' => $messageData['file_url'] ?? null,
                            'reply_to_id' => $messageData['reply_to_id'] ?? null,
                            'status' => GroupChatMessage::STATUS_SENT,
                            'client_temp_id' => $messageData['client_temp_id'],
                            'created_at' => $messageData['created_at'] ?? now(),
                        ]);

                        // Gửi tin nhắn qua WebSocket
                        broadcast(new \App\Events\NewChatMessage($message));
                    }
                }
            }

            // Xử lý trạng thái đọc
            if ($request->has('read_statuses') && !empty($request->read_statuses)) {
                foreach ($request->read_statuses as $readData) {
                    MessageReadStatus::firstOrCreate([
                        'message_id' => $readData['message_id'],
                        'user_id' => $user->id,
                    ]);

                    // Broadcast trạng thái đọc
                    $message = GroupChatMessage::find($readData['message_id']);
                    if ($message) {
                        // Trong triển khai thực tế, bạn sẽ broadcast sự kiện MessageRead
                        // Ở đây chúng ta bỏ qua để tránh lỗi
                        // broadcast(new \App\Events\MessageRead($message, $user));
                    }
                }
            }

            // Xử lý task cá nhân
            if ($request->has('personal_tasks') && !empty($request->personal_tasks)) {
                foreach ($request->personal_tasks as $taskData) {
                    if (isset($taskData['id'])) {
                        // Cập nhật task hiện có
                        $task = $user->personalTasks()->find($taskData['id']);
                        if ($task) {
                            $task->update([
                                'title' => $taskData['title'],
                                'description' => $taskData['description'] ?? null,
                                'deadline' => $taskData['deadline'] ?? null,
                                'priority' => $taskData['priority'] ?? null,
                                'status' => $taskData['status'],
                            ]);
                        }
                    } else {
                        // Tạo task mới
                        $user->personalTasks()->create([
                            'title' => $taskData['title'],
                            'description' => $taskData['description'] ?? null,
                            'deadline' => $taskData['deadline'] ?? null,
                            'priority' => $taskData['priority'] ?? null,
                            'status' => $taskData['status'] ?? 'pending',
                        ]);
                    }
                }
            }

            // Cập nhật trạng thái đồng bộ
            SyncStatus::updateOrCreate(
                ['user_id' => $user->id, 'device_id' => $deviceId],
                ['last_synced_at' => now()]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'sync_time' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Selective sync - only sync specific data types
     */
    public function selective(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'last_synced_at' => 'required|date',
            'types' => 'required|array',
            'types.*' => 'in:personal_tasks,team_tasks,messages,teams,notifications'
        ]);

        $user = $request->user();
        $deviceId = $request->device_id;
        $lastSynced = Carbon::parse($request->last_synced_at);
        $types = $request->types;

        $response = ['sync_time' => now()->toIso8601String()];

        // Lấy danh sách team của user
        $teamIds = $user->teams()->pluck('teams.id')->toArray();

        // Đồng bộ tin nhắn
        if (in_array('messages', $types)) {
            $messages = GroupChatMessage::whereIn('team_id', $teamIds)
                ->where('created_at', '>', $lastSynced)
                ->with(['sender', 'readStatuses'])
                ->orderBy('created_at', 'asc')
                ->get();

            $readStatuses = MessageReadStatus::whereHas('message', function($q) use ($teamIds) {
                    $q->whereIn('team_id', $teamIds);
                })
                ->where('created_at', '>', $lastSynced)
                ->get();

            $response['messages'] = $messages;
            $response['read_statuses'] = $readStatuses;
        }

        // Đồng bộ task cá nhân
        if (in_array('personal_tasks', $types)) {
            $personalTasks = $user->personalTasks()
                ->where('updated_at', '>', $lastSynced)
                ->get();

            $response['personal_tasks'] = $personalTasks;
        }

        // Đồng bộ task nhóm
        if (in_array('team_tasks', $types)) {
            $teamTasks = $user->teamTaskAssignments()
                ->whereHas('task', function($q) use ($lastSynced) {
                    $q->where('updated_at', '>', $lastSynced);
                })
                ->with(['task', 'task.team'])
                ->get()
                ->pluck('task');

            $response['team_tasks'] = $teamTasks;
        }

        // Đồng bộ thay đổi trong teams
        if (in_array('teams', $types)) {
            $teams = $user->teams()
                ->where('teams.updated_at', '>', $lastSynced)
                ->orWhereHas('members', function($q) use ($lastSynced) {
                    $q->where('updated_at', '>', $lastSynced);
                })
                ->with('members.user')
                ->get();

            $response['teams'] = $teams;
        }

        // Đồng bộ thông báo
        if (in_array('notifications', $types)) {
            $notifications = NotificationQueue::where('user_id', $user->id)
                ->where('created_at', '>', $lastSynced)
                ->orderBy('created_at', 'asc')
                ->get();

            $response['notifications'] = $notifications;
        }

        // Cập nhật trạng thái đồng bộ
        SyncStatus::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            ['last_synced_at' => now()]
        );

        return response()->json($response);
    }

    /**
     * Resolve conflicts between local and server data
     */
    public function resolveConflicts(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'conflicts' => 'required|array',
            'conflicts.*.type' => 'required|in:personal_task,team_task,message',
            'conflicts.*.id' => 'required',
            'conflicts.*.resolution' => 'required|in:local,server,merge',
            'conflicts.*.local_data' => 'required_if:conflicts.*.resolution,local,merge',
            'conflicts.*.server_data' => 'required_if:conflicts.*.resolution,server,merge'
        ]);

        $user = $request->user();
        $deviceId = $request->device_id;
        $conflicts = $request->conflicts;

        DB::beginTransaction();

        try {
            $resolvedCount = 0;

            foreach ($conflicts as $conflict) {
                switch ($conflict['type']) {
                    case 'personal_task':
                        $resolvedCount += $this->resolvePersonalTaskConflict($user, $conflict);
                        break;
                    case 'team_task':
                        $resolvedCount += $this->resolveTeamTaskConflict($user, $conflict);
                        break;
                    case 'message':
                        $resolvedCount += $this->resolveMessageConflict($user, $conflict);
                        break;
                }
            }

            // Cập nhật trạng thái đồng bộ
            SyncStatus::updateOrCreate(
                ['user_id' => $user->id, 'device_id' => $deviceId],
                ['last_synced_at' => now()]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'resolved_count' => $resolvedCount,
                'sync_time' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to resolve personal task conflicts
     */
    private function resolvePersonalTaskConflict($user, $conflict)
    {
        $task = $user->personalTasks()->find($conflict['id']);

        if (!$task) {
            return 0;
        }

        switch ($conflict['resolution']) {
            case 'local':
                $task->update($conflict['local_data']);
                break;
            case 'server':
                // Do nothing, server data is already current
                break;
            case 'merge':
                // Merge logic - in a real implementation, this would be more sophisticated
                $mergedData = array_merge($conflict['server_data'], $conflict['local_data']);
                $task->update($mergedData);
                break;
        }

        return 1;
    }

    /**
     * Helper method to resolve team task conflicts
     */
    private function resolveTeamTaskConflict($user, $conflict)
    {
        $task = TeamTask::find($conflict['id']);

        if (!$task || !$task->team->members()->where('user_id', $user->id)->exists()) {
            return 0;
        }

        switch ($conflict['resolution']) {
            case 'local':
                $task->update($conflict['local_data']);
                break;
            case 'server':
                // Do nothing, server data is already current
                break;
            case 'merge':
                // Merge logic - in a real implementation, this would be more sophisticated
                $mergedData = array_merge($conflict['server_data'], $conflict['local_data']);
                $task->update($mergedData);
                break;
        }

        return 1;
    }

    /**
     * Helper method to resolve message conflicts
     */
    private function resolveMessageConflict($user, $conflict)
    {
        $message = GroupChatMessage::find($conflict['id']);

        if (!$message || $message->sender_id !== $user->id) {
            return 0;
        }

        switch ($conflict['resolution']) {
            case 'local':
                $message->update([
                    'message' => $conflict['local_data']['message'],
                    'edited_at' => now()
                ]);
                break;
            case 'server':
                // Do nothing, server data is already current
                break;
            case 'merge':
                // For messages, we typically don't merge, but we could append a note
                $message->update([
                    'message' => $conflict['server_data']['message'] . "\n\nEdited: " . $conflict['local_data']['message'],
                    'edited_at' => now()
                ]);
                break;
        }

        return 1;
    }

    // Thêm phương thức để xử lý đồng bộ dữ liệu offline
    public function syncOfflineChanges(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'changes' => 'required|array',
        ]);

        $user = $request->user();
        $deviceId = $request->device_id;
        $changes = $request->changes;
        
        DB::beginTransaction();
        
        try {
            // Xử lý các thay đổi từ thiết bị
            foreach ($changes as $change) {
                $type = $change['type'] ?? '';
                $action = $change['action'] ?? '';
                $data = $change['data'] ?? [];
                
                switch ($type) {
                    case 'task':
                        $this->processTaskChange($action, $data, $user);
                        break;
                    case 'message':
                        $this->processMessageChange($action, $data, $user);
                        break;
                    case 'team':
                        $this->processTeamChange($action, $data, $user);
                        break;
                }
            }
            
            DB::commit();
            
            // Cập nhật trạng thái đồng bộ
            SyncStatus::updateOrCreate(
                ['user_id' => $user->id, 'device_id' => $deviceId],
                ['last_synced_at' => now()]
            );
            
            return response()->json([
                'success' => true,
                'sync_time' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

    // Xử lý thay đổi task
    private function processTaskChange($action, $data, $user)
    {
        switch ($action) {
            case 'create':
                // Kiểm tra quyền nếu là task nhóm
                if (isset($data['team_id'])) {
                    $team = Team::find($data['team_id']);
                    if (!$team || !$team->members()->where('user_id', $user->id)->exists()) {
                        throw new \Exception('Không có quyền tạo task trong nhóm này');
                    }
                }
                
                // Tạo task mới
                $task = new Task();
                $task->title = $data['title'];
                $task->description = $data['description'] ?? null;
                $task->status = $data['status'] ?? 'todo';
                $task->priority = $data['priority'] ?? 'medium';
                $task->due_date = isset($data['due_date']) ? Carbon::parse($data['due_date']) : null;
                $task->user_id = $user->id;
                $task->team_id = $data['team_id'] ?? null;
                $task->save();
                break;
                
            case 'update':
                $task = Task::find($data['id']);
                
                if (!$task) {
                    throw new \Exception('Task không tồn tại');
                }
                
                // Kiểm tra quyền
                if ($task->user_id != $user->id && 
                    (!$task->team_id || !$task->team->members()->where('user_id', $user->id)->exists())) {
                    throw new \Exception('Không có quyền cập nhật task này');
                }
                
                // Cập nhật task
                if (isset($data['title'])) $task->title = $data['title'];
                if (isset($data['description'])) $task->description = $data['description'];
                if (isset($data['status'])) $task->status = $data['status'];
                if (isset($data['priority'])) $task->priority = $data['priority'];
                if (isset($data['due_date'])) $task->due_date = Carbon::parse($data['due_date']);
                if (isset($data['completed_at'])) {
                    $task->completed_at = $data['completed_at'] ? Carbon::parse($data['completed_at']) : null;
                }
                
                $task->save();
                break;
                
            case 'delete':
                $task = Task::find($data['id']);
                
                if (!$task) {
                    throw new \Exception('Task không tồn tại');
                }
                
                // Kiểm tra quyền
                if ($task->user_id != $user->id && 
                    (!$task->team_id || !$task->team->members()->where('user_id', $user->id)->exists())) {
                    throw new \Exception('Không có quyền xóa task này');
                }
                
                $task->delete();
                break;
        }
    }
}
