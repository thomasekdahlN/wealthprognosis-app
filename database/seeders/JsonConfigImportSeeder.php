<?php

namespace Database\Seeders;

use App\Models\AssetConfiguration;
use App\Models\User;
use App\Services\AssetImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class JsonConfigImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create user with ID 1
        $user = User::find(1);
        if (! $user) {
            $user = User::firstOrCreate(
                ['email' => 'thomas@ekdahl.no'],
                [
                    'name' => 'Thomas Ekdahl',
                    'password' => bcrypt('ballball'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info("Importing JSON config files as user: {$user->name} (ID: {$user->id})");

        // Ensure valid asset types cache is fresh before validating types
        \App\Helpers\AssetTypeValidator::clearCache();
        $validTypes = \App\Helpers\AssetTypeValidator::getValidAssetTypes();
        $this->command->info('Valid asset types available: '.count($validTypes));

        $forceReimport = (bool) env('JSON_IMPORT_FORCE', false);
        if ($forceReimport) {
            $this->command->warn('JSON_IMPORT_FORCE=true — existing configurations with the same name will be deleted before import');
        }

        // Get the test config directory
        $configDir = base_path('tests/Feature/config');

        if (! File::isDirectory($configDir)) {
            $this->command->error("Config directory not found: {$configDir}");

            return;
        }

        // Get all JSON files from the directory
        $jsonFiles = File::glob($configDir.'/*.json');

        if (empty($jsonFiles)) {
            $this->command->warn("No JSON files found in: {$configDir}");

            return;
        }

        $this->command->info('Found '.count($jsonFiles).' JSON config files to import');

        $importService = new AssetImportService($user);
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($jsonFiles as $filePath) {
            $filename = basename($filePath);

            try {
                $this->command->line("Importing: {$filename}");

                // First, read the JSON to get the name
                $jsonContent = File::get($filePath);
                $sanitized = $this->sanitizeJson($jsonContent);
                $data = json_decode($sanitized, true);

                if (! $data || ! isset($data['meta']['name'])) {
                    $this->command->warn("  ⚠️  Skipping {$filename}: Invalid JSON or missing meta.name");
                    $skippedCount++;

                    continue;
                }

                $configName = $data['meta']['name'];

                // Check if this configuration already exists for this user
                $existingConfig = AssetConfiguration::where('user_id', $user->id)
                    ->where('name', $configName)
                    ->first();

                if ($existingConfig && ! $forceReimport) {
                    $this->command->warn("  ⚠️  Skipping {$filename}: Configuration '{$configName}' already exists (ID: {$existingConfig->id})");
                    $skippedCount++;

                    continue;
                } elseif ($existingConfig && $forceReimport) {
                    // Safe force re-import: delete dependents then delete the existing configuration
                    $this->command->warn("  ♻️  Re-import enabled: Deleting existing '{$configName}' (ID: {$existingConfig->id}) and dependents");

                    DB::transaction(function () use ($existingConfig) {
                        // Delete related asset years
                        \App\Models\AssetYear::where('asset_configuration_id', $existingConfig->id)->delete();
                        // Delete assets under this configuration
                        \App\Models\Asset::where('asset_configuration_id', $existingConfig->id)->delete();
                        // Finally delete the configuration
                        $existingConfig->delete();
                    });
                }

                // Pre-validate asset types to report how many sections may be skipped
                $totalSections = 0;
                $invalidTypeCount = 0;
                foreach ($data as $key => $section) {
                    if ($key === 'meta') {
                        continue;
                    }
                    if (! is_array($section) || ! isset($section['meta']['type'])) {
                        continue;
                    }
                    $totalSections++;
                    $rawType = $section['meta']['type'];
                    if (! \App\Helpers\AssetTypeValidator::isValid($rawType)) {
                        $invalidTypeCount++;
                    }
                }
                if ($totalSections > 0) {
                    if ($invalidTypeCount > 0) {
                        $this->command->warn("  ⚠️  Pre-check: {$invalidTypeCount}/{$totalSections} assets have invalid types and will be skipped");
                    } else {
                        $this->command->line("  ℹ️  Pre-check: All {$totalSections} asset types look valid");
                    }
                }

                $assetConfiguration = $importService->importFromFile($filePath);

                $assetsCount = $assetConfiguration->assets()->count();
                $yearsCount = $assetConfiguration->assets()->withCount('years')->get()->sum('years_count');

                $this->command->info("  ✅ Created: {$assetConfiguration->name} (ID: {$assetConfiguration->id}) - {$assetsCount} assets, {$yearsCount} year entries");
                $successCount++;

            } catch (\Exception $e) {
                $this->command->error("  ❌ Failed to import {$filename}: {$e->getMessage()}");
                Log::error("JsonConfigImportSeeder: Failed to import {$filename}", [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }
        }

        $this->command->info("\n📊 Import Summary:");
        $this->command->info("  ✅ Successfully imported: {$successCount} files");
        if ($skippedCount > 0) {
            $this->command->warn("  ⚠️  Skipped (already exist): {$skippedCount} files");
        }
        if ($errorCount > 0) {
            $this->command->error("  ❌ Failed imports: {$errorCount} files");
        }
        if ($forceReimport) {
            $this->command->info('  ♻️ Re-import mode was enabled for this run');
        }

        $totalAssetConfigurations = \App\Models\AssetConfiguration::where('user_id', $user->id)->count();
        $totalAssets = \App\Models\Asset::where('user_id', $user->id)->count();
        $totalYears = \App\Models\AssetYear::where('user_id', $user->id)->count();

        $this->command->info("  📈 Total created for user {$user->id}:");
        $this->command->info("     - Asset Configurations: {$totalAssetConfigurations}");
        $this->command->info("     - Assets: {$totalAssets}");
        $this->command->info("     - Asset Years: {$totalYears}");
    }

    /**
     * Basic JSON sanitization: remove BOM and trailing commas before } or ]
     */
    protected function sanitizeJson(string $json): string
    {
        // Remove UTF-8 BOM
        if (substr($json, 0, 3) === "\xEF\xBB\xBF") {
            $json = substr($json, 3);
        }
        // Remove trailing commas before } or ] (simple heuristic)
        $json = preg_replace('/,\s*([}\]])/', '$1', $json);

        return $json;
    }
}
