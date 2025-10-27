<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AssetImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:import {configfile} {--user-id= : User ID to import assets for} {--team-id= : Team ID to import assets for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import assets from JSON configuration file into the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $configFile = $this->argument('configfile');
        $userId = $this->option('user-id');
        $teamId = $this->option('team-id');

        // Validate config file exists
        if (! file_exists($configFile)) {
            $this->error("Configuration file not found: {$configFile}");

            return Command::FAILURE;
        }

        // Get user if specified
        $user = null;
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User not found with ID: {$userId}");

                return Command::FAILURE;
            }
        } else {
            // Use first available user if none specified
            $user = User::first();
            if (! $user) {
                $this->error('No users found in the system. Please create a user first.');

                return Command::FAILURE;
            }
            $this->info("Using user: {$user->name} (ID: {$user->id})");
        }

        try {
            $this->info("Starting import from: {$configFile}");

            $importService = new AssetImportService($user, $teamId ? (int) $teamId : null);
            $assetConfiguration = $importService->importFromFile($configFile);

            $this->info('✅ Import completed successfully!');
            $this->info("Created Asset Configuration: {$assetConfiguration->name} (ID: {$assetConfiguration->id})");
            $this->info("Assets created: {$assetConfiguration->assets()->count()}");

            // Show summary of created assets
            $assets = $assetConfiguration->assets()->get();
            if ($assets->count() > 0) {
                $this->info("\nCreated assets:");
                /** @var \App\Models\Asset $asset */
                foreach ($assets as $asset) {
                    $yearsCount = $asset->years()->count();
                    $this->line("  - {$asset->name} ({$asset->asset_type}) - {$yearsCount} year entries");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            Log::error('Asset import command failed', [
                'config_file' => $configFile,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
