<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $teams = Team::whereHas('members', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->with(['members', 'creator'])->get();

        return response()->json($teams);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $team = Team::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => $request->user()->id
        ]);

        // Add creator as manager
        TeamMember::create([
            'team_id' => $team->team_id,
            'user_id' => $request->user()->id,
            'role' => 'manager'
        ]);

        return response()->json($team->load(['members', 'creator']), 201);
    }

    public function show(Request $request, Team $team)
    {
        if (!$team->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($team->load(['members', 'creator', 'tasks']));
    }

    public function update(Request $request, Team $team)
    {
        $member = $team->members()->where('user_id', $request->user()->id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $team->update($request->all());

        return response()->json($team->load(['members', 'creator']));
    }

    public function destroy(Request $request, Team $team)
    {
        $member = $team->members()->where('user_id', $request->user()->id)->first();
        
        if (!$member || $member->role !== 'manager') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $team->delete();

        return response()->json(null, 204);
    }
}
