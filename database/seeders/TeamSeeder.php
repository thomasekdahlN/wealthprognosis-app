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
        // Create default team with ID = 1
        $defaultTeam = Team::create([
            'id' => 1,
            'name' => 'Default Team',
            'description' => 'Default team for all users',
            'owner_id' => 1, // Will be set to first user
            'is_active' => true,
            'settings' => [],
            'created_by' => 1,
            'updated_by' => 1,
            'created_checksum' => hash('sha256', 'default_team_created'),
            'updated_checksum' => hash('sha256', 'default_team_updated'),
        ]);

        echo "Created default team (ID: {$defaultTeam->id})\n";

        // Create second team for tommyl user
        $secondTeam = Team::create([
            'id' => 2,
            'name' => 'Tommy Team',
            'description' => 'Team for Tommy L user',
            'owner_id' => 2, // Tommy L
            'is_active' => true,
            'settings' => [],
            'created_by' => 2,
            'updated_by' => 2,
            'created_checksum' => hash('sha256', 'tommy_team_created'),
            'updated_checksum' => hash('sha256', 'tommy_team_updated'),
        ]);

        echo "Created second team (ID: {$secondTeam->id})\n";

        // Add users to their respective teams
        $users = User::all();
        foreach ($users as $user) {
            if ($user->id === 1) {
                // Thomas Ekdahl -> Team 1
                $defaultTeam->addUser($user, 'owner');
                $user->current_team_id = 1;
                echo "Added user {$user->name} to default team (ID: 1)\n";
            } elseif ($user->id === 2) {
                // Tommy L -> Team 2
                $secondTeam->addUser($user, 'owner');
                $user->current_team_id = 2;
                echo "Added user {$user->name} to second team (ID: 2)\n";
            } else {
                // Any other users -> Team 1
                $defaultTeam->addUser($user, 'member');
                $user->current_team_id = 1;
                echo "Added user {$user->name} to default team (ID: 1)\n";
            }

            $user->save();
        }
    }
}
