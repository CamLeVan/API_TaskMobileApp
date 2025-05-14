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
            'q' => 'required|string|min:2',
            'exclude_team' => 'nullable|integer|exists:teams,id',
            'per_page' => 'nullable|integer|min:5|max:100'
        ]);

        $query = $request->input('q');
        $excludeTeamId = $request->input('exclude_team');
        $perPage = $request->input('per_page', 15);
        
        $usersQuery = User::query();
        
        // Tìm kiếm theo từ khóa
        $usersQuery->where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
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
