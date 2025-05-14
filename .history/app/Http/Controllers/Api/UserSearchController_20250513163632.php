<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSearchController extends Controller
{
    /**
     * Tìm kiếm người dùng
     *
     * API này được sử dụng khi mời người dùng vào nhóm
     * Trả về tất cả người dùng phù hợp với từ khóa tìm kiếm
     */
    public function search(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|min:2',
            'email' => 'nullable|string|min:2',
            'exclude_team' => 'nullable|integer|exists:teams,id',
            'per_page' => 'nullable|integer|min:5|max:100'
        ]);

        // Ít nhất một trong hai tham số name hoặc email phải được cung cấp
        if (!$request->has('name') && !$request->has('email')) {
            return response()->json([
                'message' => 'Ít nhất một trong hai tham số name hoặc email phải được cung cấp'
            ], 422);
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $excludeTeamId = $request->input('exclude_team');
        $perPage = $request->input('per_page', 15);

        $usersQuery = User::query();

        // Tìm kiếm theo tên và/hoặc email
        $usersQuery->where(function($q) use ($name, $email) {
            if ($name) {
                $q->where('name', 'like', "%{$name}%");
            }

            if ($email) {
                if ($name) {
                    $q->orWhere('email', 'like', "%{$email}%");
                } else {
                    $q->where('email', 'like', "%{$email}%");
                }
            }
        });

        // Nếu có exclude_team, loại trừ những người đã là thành viên của nhóm đó
        if ($excludeTeamId) {
            $usersQuery->whereDoesntHave('teams', function($q) use ($excludeTeamId) {
                $q->where('teams.id', $excludeTeamId);
            });
        }

        // Loại trừ người dùng hiện tại
        $usersQuery->where('id', '!=', Auth::id());

        // Sắp xếp và phân trang
        $users = $usersQuery->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ]
        ]);
    }
}
