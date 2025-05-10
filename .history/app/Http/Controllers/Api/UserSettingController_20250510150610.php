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
            'theme' => 'sometimes|in:light,dark,system,amoled',
            'language' => 'sometimes|string|max:10',
            'timezone' => 'sometimes|string|max:50',
            'date_format' => 'sometimes|string|max:20',
            'time_format' => 'sometimes|string|max:20',
            'start_day_of_week' => 'sometimes|integer|min:0|max:6',
            'enable_biometric' => 'sometimes|boolean',
            'auto_save_drafts' => 'sometimes|boolean',
            'default_view' => 'sometimes|in:list,kanban,calendar',
            'notification_settings' => 'sometimes|array',
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

        if ($request->has('timezone')) {
            $settings->timezone = $request->timezone;
        }

        if ($request->has('date_format')) {
            $settings->date_format = $request->date_format;
        }

        if ($request->has('time_format')) {
            $settings->time_format = $request->time_format;
        }

        if ($request->has('start_day_of_week')) {
            $settings->start_day_of_week = $request->start_day_of_week;
        }

        if ($request->has('enable_biometric')) {
            $settings->enable_biometric = $request->enable_biometric;
        }

        if ($request->has('auto_save_drafts')) {
            $settings->auto_save_drafts = $request->auto_save_drafts;
        }

        if ($request->has('default_view')) {
            $settings->default_view = $request->default_view;
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

    /**
     * Đặt lại cài đặt về mặc định
     */
    public function reset(Request $request)
    {
        $user = $request->user();

        $defaultSettings = [
            'theme' => 'light',
            'language' => 'vi',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'start_day_of_week' => 1, // Thứ 2
            'enable_biometric' => false,
            'auto_save_drafts' => true,
            'default_view' => 'list',
            'notification_settings' => [
                'task_reminders' => true,
                'team_updates' => true,
                'chat_messages' => true
            ]
        ];

        $settings = $user->settings;

        if (!$settings) {
            $settings = new UserSetting(['user_id' => $user->id]);
        }

        foreach ($defaultSettings as $key => $value) {
            $settings->$key = $value;
        }

        $settings->save();

        return response()->json([
            'data' => $settings,
            'message' => 'Đã đặt lại cài đặt về mặc định'
        ]);
    }
}
