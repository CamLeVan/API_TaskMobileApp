<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\Request;

class UserSettingController extends Controller
{
    /**
     * Get user settings
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get or create settings
        $settings = $user->settings;
        
        if (!$settings) {
            $settings = UserSetting::create([
                'user_id' => $user->id,
                'theme' => 'light',
                'language' => 'en',
                'notification_settings' => [
                    'task_reminders' => true,
                    'team_updates' => true,
                    'chat_messages' => true
                ]
            ]);
        }
        
        return response()->json($settings);
    }
    
    /**
     * Update user settings
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'theme' => 'sometimes|required|in:light,dark,amoled',
            'language' => 'sometimes|required|string|max:10',
            'notification_settings' => 'sometimes|required|array',
            'notification_settings.task_reminders' => 'boolean',
            'notification_settings.team_updates' => 'boolean',
            'notification_settings.chat_messages' => 'boolean',
            'calendar_sync' => 'sometimes|array'
        ]);
        
        // Update or create settings
        $settings = $user->settings;
        
        if (!$settings) {
            $settings = new UserSetting(['user_id' => $user->id]);
        }
        
        // Update only provided fields
        if ($request->has('theme')) {
            $settings->theme = $request->theme;
        }
        
        if ($request->has('language')) {
            $settings->language = $request->language;
        }
        
        if ($request->has('notification_settings')) {
            // Merge with existing settings to avoid overwriting unspecified settings
            $currentSettings = $settings->notification_settings ?: [];
            $settings->notification_settings = array_merge($currentSettings, $request->notification_settings);
        }
        
        if ($request->has('calendar_sync')) {
            $settings->calendar_sync = $request->calendar_sync;
        }
        
        $settings->save();
        
        return response()->json($settings);
    }
}
