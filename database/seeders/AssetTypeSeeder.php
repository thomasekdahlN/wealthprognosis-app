<?php

namespace Database\Seeders;

use App\Models\AssetType;
use App\Models\TaxType;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AssetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Tax Types are available as dependency (FK for tax_type when present)
        $this->call(TaxTypesFromConfigSeeder::class);

        $jsonPath = config_path('assets/asset_types.json');

        if (! File::exists($jsonPath)) {
            $this->command?->warn("Asset types JSON not found at: {$jsonPath}");

            return;
        }

        $assetTypes = json_decode(File::get($jsonPath), true) ?? [];
        if (! is_array($assetTypes) || empty($assetTypes)) {
            $this->command?->warn('Asset types JSON is empty or invalid.');

            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($assetTypes as $index => $assetType) {
            $type = (string) ($assetType['type'] ?? '');
            if ($type === '') {
                $this->command?->warn("Skipping asset_types[{$index}] due to missing 'type' field.");
                $skipped++;

                continue;
            }

            // Validate referenced tax_type exists when provided
            $taxType = $assetType['tax_type'] ?? null;
            if ($taxType !== null && $taxType !== '' && ! TaxType::query()->where('type', $taxType)->exists()) {
                $this->command?->error("Skipping asset type '{$type}': Unknown tax_type '{$taxType}'. Add it to config/tax/tax_types.json (and seed) first.");
                $skipped++;

                continue;
            }

            // Ownership & audit fields only. All configuration must come from JSON 1:1.
            $assetType['user_id'] = 1;
            $assetType['team_id'] = 1;
            $assetType['created_by'] = 1;
            $assetType['updated_by'] = 1;
            $assetType['created_checksum'] = $assetType['created_checksum'] ?? hash('sha256', 'asset_type_created_'.$type);
            $assetType['updated_checksum'] = $assetType['updated_checksum'] ?? hash('sha256', 'asset_type_updated_'.$type);

            try {
                AssetType::updateOrCreate(
                    ['type' => $type],
                    $assetType
                );
                $created++;
            } catch (QueryException $e) {
                $this->command?->error("Failed to upsert asset type '{$type}' (index {$index}): ".$e->getMessage());
                $skipped++;
            }
        }

        $this->command?->info("Asset types seeded from JSON. Created/updated: {$created}, skipped: {$skipped}.");
    }
}
