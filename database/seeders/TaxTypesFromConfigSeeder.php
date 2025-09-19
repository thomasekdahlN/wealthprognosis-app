<?php

namespace Database\Seeders;

use App\Models\TaxType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TaxTypesFromConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base user/team
        $user = \App\Models\User::first() ?? \App\Models\User::create([
            'name' => 'System Admin',
            'email' => 'admin@system.local',
            'password' => bcrypt('password'),
        ]);

        $team = \App\Models\Team::first() ?? \App\Models\Team::create([
            'name' => 'Default Team',
            'description' => 'System default team',
            'owner_id' => $user->id,
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_checksum' => hash('sha256', 'default_team_created'),
            'updated_checksum' => hash('sha256', 'default_team_updated'),
        ]);

        $jsonPath = config_path('tax/tax_types.json');
        if (! File::exists($jsonPath)) {
            $this->command?->error("Tax types JSON not found: {$jsonPath}");
            return;
        }

        $items = json_decode(File::get($jsonPath), true) ?? [];
        foreach ($items as $i => $row) {
            $type = (string) ($row['type'] ?? '');
            $name = (string) ($row['name'] ?? $type);
            $description = (string) ($row['description'] ?? null);
            if ($type === '') {
                $this->command?->warn("Skipping item #{$i} with empty type");
                continue;
            }

            TaxType::updateOrCreate(
                ['type' => $type],
                [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'name' => $name === '' ? $type : $name,
                    'description' => $description,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => hash('sha256', 'tax_type_created_' . $type),
                    'updated_checksum' => hash('sha256', 'tax_type_updated_' . $type),
                ]
            );
        }
    }
}

