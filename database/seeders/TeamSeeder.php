<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resolve users by email to avoid hard-coded IDs
        $thomas = User::firstWhere('email', 'thomas@ekdahl.no');
        $tommy = User::firstWhere('email', 'tommyl@coretrek.no');
        $fallbackUserId = User::query()->min('id') ?? 1;

        // Create or update default team with ID = 1
        $defaultTeam = Team::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Default Team',
                'description' => 'Default team for all users',
                'owner_id' => $thomas?->id ?? $fallbackUserId,
                'is_active' => true,
                'settings' => [],
                'created_by' => $thomas?->id ?? $fallbackUserId,
                'updated_by' => $thomas?->id ?? $fallbackUserId,
                'created_checksum' => hash('sha256', 'default_team_created'),
                'updated_checksum' => hash('sha256', 'default_team_updated'),
            ]
        );

        echo "Ensured default team (ID: {$defaultTeam->id}) exists\n";

        // Create or update second team for Tommy user
        $secondTeam = Team::updateOrCreate(
            ['id' => 2],
            [
                'name' => 'Tommy Team',
                'description' => 'Team for Tommy L user',
                'owner_id' => $tommy?->id ?? $thomas?->id ?? $fallbackUserId,
                'is_active' => true,
                'settings' => [],
                'created_by' => $tommy?->id ?? $thomas?->id ?? $fallbackUserId,
                'updated_by' => $tommy?->id ?? $thomas?->id ?? $fallbackUserId,
                'created_checksum' => hash('sha256', 'tommy_team_created'),
                'updated_checksum' => hash('sha256', 'tommy_team_updated'),
            ]
        );

        echo "Ensured second team (ID: {$secondTeam->id}) exists\n";

        // Add users to their respective teams if not already added, and set current team by email
        $users = User::all();
        foreach ($users as $user) {
            if ($user->email === 'thomas@ekdahl.no') {
                $defaultTeam->addUser($user, 'owner');
                $user->current_team_id = $defaultTeam->id;
                echo "Ensured user {$user->name} is owner of default team (ID: {$defaultTeam->id})\n";
            } elseif ($user->email === 'tommyl@coretrek.no') {
                $secondTeam->addUser($user, 'owner');
                $user->current_team_id = $secondTeam->id;
                echo "Ensured user {$user->name} is owner of second team (ID: {$secondTeam->id})\n";
            } else {
                $defaultTeam->addUser($user, 'member');
                $user->current_team_id = $defaultTeam->id;
                echo "Ensured user {$user->name} is member of default team (ID: {$defaultTeam->id})\n";
            }

            $user->save();
        }
    }
}
