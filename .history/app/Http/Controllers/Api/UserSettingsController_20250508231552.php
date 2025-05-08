<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSettingsController extends Controller
{
    /**
     * Lấy cài đặt người dùng
     */
    public function index()
    {
        $user = Auth::user();
        $settings = $user->settings;

        if (!$settings) {
            // Tạo cài đặt mặc định nếu chưa có
            $settings = $user->settings()->create([
                'theme' => 'light',
                'language' => 'vi',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'start_day_of_week' => 1, // Thứ 2
                'enable_biometric' => false,
                'auto_save_drafts' => true,
                'default_view' => 'list'
            ]);
        }

        return response()->json([
            'data' => $settings
        ]);
    }

    /**
     * Cập nhật cài đặt người dùng
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'sometimes|in:light,dark,system',
            'language' => 'sometimes|string|max:10',
            'timezone' => 'sometimes|string|max:50',
            'date_format' => 'sometimes|string|max:20',
            'time_format' => 'sometimes|string|max:20',
            'start_day_of_week' => 'sometimes|integer|min:0|max:6',
            'enable_biometric' => 'sometimes|boolean',
            'auto_save_drafts' => 'sometimes|boolean',
            'default_view' => 'sometimes|in:list,kanban,calendar'
        ]);

        $user = Auth::user();

        $settings = $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'data' => $settings
        ]);
    }

    /**
     * Đặt lại cài đặt về mặc định
     */
    public function reset()
    {
        $user = Auth::user();

        $defaultSettings = [
            'theme' => 'light',
            'language' => 'vi',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'start_day_of_week' => 1, // Thứ 2
            'enable_biometric' => false,
            'auto_save_drafts' => true,
            'default_view' => 'list'