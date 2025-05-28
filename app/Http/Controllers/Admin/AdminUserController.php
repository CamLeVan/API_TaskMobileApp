<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Tìm kiếm
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Lọc theo status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_admin', $request->status === 'admin');
        }

        // CHỈ lấy thông tin không nhạy cảm
        $users = $query->select([
                'id',
                'name', 
                'email',
                'is_admin',
                'last_login_at',
                'created_at'
            ])
            ->withCount(['personalTasks', 'teams'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        // CHỈ thông tin tổng quan, KHÔNG có nội dung cá nhân
        $userData = [
            'basic_info' => $user->only(['id', 'name', 'email', 'created_at', 'last_login_at']),
            'statistics' => [
                'total_personal_tasks' => $user->personalTasks()->count(),
                'completed_personal_tasks' => $user->personalTasks()->where('status', 'completed')->count(),
                'teams_count' => $user->teams()->count(),
                'team_tasks_assigned' => $user->teamTaskAssignments()->count(),
            ],
            'teams' => $user->teams()
                ->select('teams.id', 'teams.name', 'team_members.role', 'team_members.joined_at')
                ->get(),
        ];

        return view('admin.users.show', compact('user', 'userData'));
    }

    public function toggleStatus(User $user)
    {
        // Chỉ cho phép toggle admin status, không thay đổi thông tin khác
        $user->update([
            'is_admin' => !$user->is_admin
        ]);

        $status = $user->is_admin ? 'granted' : 'revoked';
        
        return redirect()->back()->with('success', "Admin privileges {$status} for {$user->name}");
    }
}
