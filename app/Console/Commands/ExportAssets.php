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
    protected $signature = 'assets:export {asset-owner-id?} {--path= : Custom file path for export} {--all : Export all asset owners}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export asset owner data to JSON configuration file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            if ($this->option('all')) {
                return $this->exportAllAssetOwners();
            }

            $assetOwnerId = $this->argument('asset-owner-id');
            if (! $assetOwnerId) {
                $this->error('Please provide an asset-owner-id or use --all to export all asset owners');

                return Command::FAILURE;
            }

            $customPath = $this->option('path');

            // Find the asset owner
            $assetOwner = AssetConfiguration::with(['assets.years'])->find($assetOwnerId);

            if (! $assetOwner) {
                $this->error("Asset owner not found with ID: {$assetOwnerId}");

                return Command::FAILURE;
            }

            $this->info("Exporting asset owner: {$assetOwner->name} (ID: {$assetOwner->id})");

            // Export to file
            $filePath = AssetExportService::export($assetOwner, $customPath);

            $this->info('✅ Export completed successfully!');
            $this->info("File saved to: {$filePath}");

            // Show summary
            $assetsCount = $assetOwner->assets()->count();
            $yearsCount = $assetOwner->assets()->withCount('years')->get()->sum('years_count');

            $this->info('Exported data:');
            $this->line("  - Assets: {$assetsCount}");
            $this->line("  - Asset Years: {$yearsCount}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            Log::error('Asset export command failed', [
                'asset_owner_id' => $this->argument('asset-owner-id'),
                'custom_path' => $this->option('path'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Export all asset owners
     */
    protected function exportAllAssetOwners(): int
    {
        $assetOwners = AssetConfiguration::with(['assets.years'])->get();

        if ($assetOwners->isEmpty()) {
            $this->warn('No asset owners found to export');

            return Command::SUCCESS;
        }

        $this->info("Exporting {$assetOwners->count()} asset owners...");

        $successCount = 0;
        $errorCount = 0;

        foreach ($assetOwners as $assetOwner) {
            try {
                $this->line("Exporting: {$assetOwner->name} (ID: {$assetOwner->id})");

                $filePath = AssetExportService::export($assetOwner);

                $this->info('  ✅ Exported to: '.basename($filePath));
                $successCount++;

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to export {$assetOwner->name}: {$e->getMessage()}");
                Log::error("Failed to export asset owner {$assetOwner->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }
        }

        $this->info("\n📊 Export Summary:");
        $this->info("  ✅ Successfully exported: {$successCount} asset owners");
        if ($errorCount > 0) {
            $this->error("  ❌ Failed exports: {$errorCount} asset owners");
        }

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
