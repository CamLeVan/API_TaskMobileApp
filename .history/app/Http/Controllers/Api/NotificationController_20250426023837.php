<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\GroupChatMessage;
use App\Models\NotificationQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Đăng ký thiết bị cho thông báo
    public function registerDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'platform' => 'required|in:android,ios'
        ]);
        
        $user = $request->user();
        
        Device::updateOrCreate(
            ['device_id' => $request->device_id],
            [
                'user_id' => $user->id,
                'platform' => $request->platform
            ]
        );
        
        return response()->json(['message' => 'Device registered successfully']);
    }
    
    // Hủy đăng ký thiết bị
    public function unregisterDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string'
        ]);
        
        $user = $request->user();
        
        Device::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->delete();
            
        return response()->json(['message' => 'Device unregistered successfully']);
    }
    
    // Lấy thông báo mới (polling)
    public function getNotifications(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'last_checked_at' => 'nullable|date'
        ]);
        
        $user = $request->user();
        $lastChecked = $request->has('last_checked_at') 
            ? Carbon::parse($request->last_checked_at)
            : Carbon::now()->subDays(7);
            
        // Lấy thông báo từ hàng đợi
        $notifications = NotificationQueue::where('user_id', $user->id)
            ->where('created_at', '>', $lastChecked)
            ->where('is_sent', false)
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Đánh dấu các thông báo đã được gửi
        if ($notifications->count() > 0) {
            NotificationQueue::whereIn('id', $notifications->pluck('id'))
                ->update(['is_sent' => true, 'sent_at' => now()]);
        }
        
        return response()->json([
            'notifications' => $notifications,
            'server_time' => now()->toIso8601String()
        ]);
    }
    
    // Helper method để thêm thông báo tin nhắn mới vào queue
    public static function queueChatNotification(GroupChatMessage $message)
    {
        // Lấy tất cả thành viên trong nhóm ngoại trừ người gửi
        $teamMembers = $message->team->members()
            ->where('user_id', '!=', $message->sender_id)
            ->pluck('user_id');
            
        foreach ($teamMembers as $userId) {
            NotificationQueue::create([
                'user_id' => $userId,
                'type' => 'new_message',
                'data' => [
                    'message_id' => $message->id,
                    'team_id' => $message->team_id,
                    'team_name' => $message->team->name,
                    'sender_name' => $message->sender->name,
                    'message_preview' => substr($message->message, 0, 100),
                    'sent_at' => $message->created_at->toIso8601String()
                ]
            ]);
        }
    }
    
    // Helper method để thêm thông báo phân công nhiệm vụ vào queue
    public static function queueTaskAssignmentNotification($assignment) 
    {
        NotificationQueue::create([
            'user_id' => $assignment->user_id,
            'type' => 'task_assignment',
            'data' => [
                'task_id' => $assignment->task_id,
                'team_id' => $assignment->task->team_id,
                'team_name' => $assignment->task->team->name,
                'task_title' => $assignment->task->title,
                'deadline' => $assignment->task->deadline ? $assignment->task->deadline->toIso8601String() : null,
                'priority' => $assignment->task->priority
            ]
        ]);
    }
} 