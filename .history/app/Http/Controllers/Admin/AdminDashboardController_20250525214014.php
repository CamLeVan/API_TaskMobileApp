<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use App\Models\PersonalTask;
use App\Models\TeamTask;
use App\Models\GroupChatMessage;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Thống kê cơ bản - AN TOÀN
        $stats = [
            'total_users' => User::count(),
            'active_users_today' => User::where('last_login_at', '>', now()->subDay())->count(),
            'total_teams' => Team::count(),
            'total_personal_tasks' => PersonalTask::count(),
            'total_team_tasks' => TeamTask::count(),
            'total_messages' => GroupChatMessage::count(),
        ];

        // Thống kê theo thời gian
        $userGrowth = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $teamGrowth = Team::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Hoạt động gần đây - CHỈ metadata
        $recentUsers = User::latest()
            ->take(5)
            ->select('id', 'name', 'email', 'created_at')
            ->get();

        $recentTeams = Team::latest()
            ->take(5)
            ->select('id', 'name', 'created_at', 'created_by')
            ->with('creator:id,name')
            ->get();

        return view('admin.dashboard', compact(
            'stats', 
            'userGrowth', 
            'teamGrowth', 
            'recentUsers', 
            'recentTeams'
        ));
    }
}
