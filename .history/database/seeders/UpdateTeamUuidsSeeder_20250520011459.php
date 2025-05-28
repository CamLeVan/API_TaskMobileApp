<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UpdateTeamUuidsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy tất cả các team chưa có UUID
        $teams = Team::whereNull('uuid')->get();

        foreach ($teams as $team) {
            // Tạo UUID mới
            $team->uuid = Str::uuid()->toString();
            $team->save();

            $this->command->info("Updated UUID for team {$team->id}: {$team->name} - {$team->uuid}");
        }

        $this->command->info("Total teams updated: {$teams->count()}");
    }
}
