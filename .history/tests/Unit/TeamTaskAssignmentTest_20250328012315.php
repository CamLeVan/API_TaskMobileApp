<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Team;
use App\Models\TeamTask;
use App\Models\TeamTaskAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamTaskAssignmentTest extends TestCase
{
    use RefreshDatabase; // Làm mới database mỗi khi chạy test

    public function test_user_can_view_task_assignments()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user->user_id); // Gán user vào team

        $task = TeamTask::factory()->create(['team_id' => $team->team_id]);

        $assignment = TeamTaskAssignment::factory()->create([
            'team_task_id' => $task->team_task_id,
            'assigned_to' => $user->user_id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/teams/{$team->team_id}/tasks/{$task->team_task_id}/assignments");

        $response->assertStatus(200)
                 ->assertJsonFragment(['assigned_to' => $user->user_id]);
    }
}
