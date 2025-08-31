<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AssetImportService;
use Illuminate\Database\Seeder;
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

        foreach ($jsonFiles as $filePath) {
            $filename = basename($filePath);

            try {
                $this->command->line("Importing: {$filename}");

                $assetOwner = $importService->importFromFile($filePath);

                $assetsCount = $assetOwner->assets()->count();
                $yearsCount = $assetOwner->assets()->withCount('years')->get()->sum('years_count');

                $this->command->info("  ✅ Created: {$assetOwner->name} (ID: {$assetOwner->id}) - {$assetsCount} assets, {$yearsCount} year entries");
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
        if ($errorCount > 0) {
            $this->command->error("  ❌ Failed imports: {$errorCount} files");
        }

        $totalAssetOwners = \App\Models\AssetConfiguration::where('user_id', $user->id)->count();
        $totalAssets = \App\Models\Asset::where('user_id', $user->id)->count();
        $totalYears = \App\Models\AssetYear::where('user_id', $user->id)->count();

        $this->command->info("  📈 Total created for user {$user->id}:");
        $this->command->info("     - Asset Owners: {$totalAssetOwners}");
        $this->command->info("     - Assets: {$totalAssets}");
        $this->command->info("     - Asset Years: {$totalYears}");
    }
}
