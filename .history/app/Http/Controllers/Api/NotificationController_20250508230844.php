<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Đăng ký thiết bị
     */
    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
            'device_type' => 'required|in:android,ios,web',
            'device_name' => 'required|string'
        ]);
        
        $user = Auth::user();
        
        // Cập nhật hoặc tạo mới token thiết bị
        DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $validated['device_id']
            ],
            [
                'fcm_token' => $validated['fcm_token'],
                'device_type' => $validated['device_type'],
                'device_name' => $validated['device_name'],
                'last_active_at' => now()
            ]
        );
        
        return response()->json([
            'message' => 'Đăng ký thiết bị thành công'
        ]);
    }
    
    /**
     * Cập nhật cài đặt thông báo
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'task_assignments' => 'sometimes|boolean',
            'task_updates' => 'sometimes|boolean',
            'task_comments' => 'sometimes|boolean',
            'team_messages' => 'sometimes|boolean',
            'team_invitations' => 'sometimes|boolean',
            'quiet_hours' => 'sometimes|array',
            'quiet_hours.enabled' => 'required_with:quiet_hours|boolean',
            'quiet_hours.start' => 'required_with:quiet_hours|string',
            'quiet_hours.end' => 'required_with:quiet_hours|string'
        ]);
        
        $user = Auth::user();
        
        // Lấy hoặc tạo cài đặt thông báo
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'task_assignments' => true,
                'task_updates' => true,
                'task_comments' => true,
                'team_messages' => true,
                'team_invitations' => true,
                'quiet_hours' => [
                    'enabled' => false,
                    'start' => '22:00',
                    'end' => '07:00'
                ]
            ]
        );
        
        // Cập nhật cài đặt
        if (isset($validated['task_assignments'])) {
            $settings->task_assignments = $validated['task_assignments'];
        }
        
        if (isset($validated['task_updates'])) {
            $settings->task_updates = $validated['task_updates'];
        }
        
        if (isset($validated['task_comments'])) {
            $settings->task_comments = $validated['task_comments'];
        }
        
        if (isset($validated['team_messages'])) {
            $settings->team_messages = $validated['team_messages'];
        }
        
        if (isset($validated['team_invitations'])) {
            $settings->team_invitations = $validated['team_invitations'];
        }
        
        if (isset($validated['quiet_hours'])) {
            $settings->quiet_hours = $validated['quiet_hours'];
        }
        
        $settings->save();
        
        return response()->json([
            'data' => $settings
        ]);
    }
    
    /**
     * Lấy cài đặt thông báo
     */
    public function getSettings()
    {
        $user = Auth::user();
        
        // Lấy hoặc tạo cài đặt thông báo
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'task_assignments' => true,
                'task_updates' => true,
                'task_comments' => true,
                'team_messages' => true,
                'team_invitations' => true,
                'quiet_hours' => [
                    'enabled' => false,
                    'start' => '22:00',
                    'end' => '07:00'
                ]
            ]
        );
        
        return response()->json([
            'data' => $settings
        ]);
    }
    
    /**
     * Hủy đăng ký thiết bị
     */
    public function unregisterDevice(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string'
        ]);
        
        $user = Auth::user();
        
        // Xóa token thiết bị
        DeviceToken::where('user_id', $user->id)
            ->where('device_id', $validated['device_id'])
            ->delete();
        
        return response()->json([
            'message' => 'Hủy đăng ký thiết bị thành công'
        ]);
    }
}

