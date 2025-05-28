<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;

class AdminTeamController extends Controller
{
    public function index(Request $request)
    {
        $query = Team::query();

        // Tìm kiếm
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // CHỈ lấy metadata, không có nội dung
        $teams = $query->select([
                'id',
                'name',
                'created_at',
                'created_by'
            ])
            ->withCount(['members', 'tasks', 'chatMessages'])
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.teams.index', compact('teams'));
    }

    public function show(Team $team)
    {
        // CHỈ thống kê, KHÔNG có nội dung chat hay task details
        $teamData = [
            'basic_info' => $team->only(['id', 'name', 'created_at']),
            'creator' => $team->creator->only(['id', 'name']),
            'statistics' => [
                'members_count' => $team->members()->count(),
                'tasks_count' => $team->tasks()->count(),
                'messages_count' => $team->chatMessages()->count(),
                'active_members' => $team->members()
                    ->whereHas('user', function($q) {
                        $q->where('last_login_at', '>', now()->subDays(7));
                    })->count(),
                'completed_tasks' => $team->tasks()->where('status', 'completed')->count(),
            ],
            'members_list' => $team->members()
                ->with('user:id,name,email,last_login_at')
                ->get()
                ->map(function($member) {
                    return [
                        'id' => $member->user->id,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'role' => $member->role,
                        'joined_at' => $member->joined_at,
                        'last_login' => $member->user->last_login_at,
                    ];
                }),
        ];

        return view('admin.teams.show', compact('team', 'teamData'));
    }
}
