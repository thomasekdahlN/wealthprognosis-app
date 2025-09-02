<?php

namespace App\Console\Commands;

use App\Models\AssetConfiguration;
use App\Services\AssetExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExportAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:export {asset-configuration-id?} {--path= : Custom file path for export} {--all : Export all asset configurations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export asset configuration data to JSON file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            if ($this->option('all')) {
                return $this->exportAllAssetConfigurations();
            }

            $assetConfigurationId = $this->argument('asset-configuration-id');
            if (! $assetConfigurationId) {
                $this->error('Please provide an asset-configuration-id or use --all to export all asset configurations');

                return Command::FAILURE;
            }

            $customPath = $this->option('path');

            // Find the asset owner
            $assetConfiguration = AssetConfiguration::with(['assets.years'])->find($assetConfigurationId);

            if (! $assetConfiguration) {
                $this->error("Asset configuration not found with ID: {$assetConfigurationId}");

                return Command::FAILURE;
            }

            $this->info("Exporting asset configuration: {$assetConfiguration->name} (ID: {$assetConfiguration->id})");

            // Export to file
            $filePath = AssetExportService::export($assetConfiguration, $customPath);

            $this->info('✅ Export completed successfully!');
            $this->info("File saved to: {$filePath}");

            // Show summary
            $assetsCount = $assetConfiguration->assets()->count();
            $yearsCount = $assetConfiguration->assets()->withCount('years')->get()->sum('years_count');

            $this->info('Exported data:');
            $this->line("  - Assets: {$assetsCount}");
            $this->line("  - Asset Years: {$yearsCount}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            Log::error('Asset export command failed', [
                'asset_configuration_id' => $this->argument('asset-configuration-id'),
                'custom_path' => $this->option('path'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Export all asset configurations
     */
    protected function exportAllAssetConfigurations(): int
    {
        $assetConfigurations = AssetConfiguration::with(['assets.years'])->get();

        if ($assetConfigurations->isEmpty()) {
            $this->warn('No asset configurations found to export');

            return Command::SUCCESS;
        }

        $this->info("Exporting {$assetConfigurations->count()} asset configurations...");

        $successCount = 0;
        $errorCount = 0;

        foreach ($assetConfigurations as $assetConfiguration) {
            try {
                $this->line("Exporting: {$assetConfiguration->name} (ID: {$assetConfiguration->id})");

                $filePath = AssetExportService::export($assetConfiguration);

                $this->info('  ✅ Exported to: '.basename($filePath));
                $successCount++;

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to export {$assetConfiguration->name}: {$e->getMessage()}");
                Log::error("Failed to export asset configuration {$assetConfiguration->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }
        }

        $this->info("\n📊 Export Summary:");
        $this->info("  ✅ Successfully exported: {$successCount} asset configurations");
        if ($errorCount > 0) {
            $this->error("  ❌ Failed exports: {$errorCount} asset configurations");
        }

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
