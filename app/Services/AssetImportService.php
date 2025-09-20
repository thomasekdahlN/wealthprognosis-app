<?php

namespace App\Services;

use App\Helpers\AssetTypeValidator;
use App\Helpers\HeroiconValidator;
use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssetImportService
{
    protected array $config;

    protected ?User $user;

    protected ?int $teamId;

    protected ?Carbon $fileCreatedAt = null;

    protected ?Carbon $fileUpdatedAt = null;

    protected int $currentSortOrder = 1; // Track sort order for assets

    public function __construct(?User $user = null, ?int $teamId = null)
    {
        $this->user = $user ?? Auth::user();
        $this->teamId = $teamId ?? $this->user?->current_team_id;
    }

    /**
     * Import JSON configuration file and create AssetConfiguration with all related assets
     */
    public function importFromFile(string $filePath): AssetConfiguration
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Configuration file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read configuration file: {$filePath}");
        }

        // Get file timestamps
        $fileCreatedAt = Carbon::createFromTimestamp(filectime($filePath));
        $fileUpdatedAt = Carbon::createFromTimestamp(filemtime($filePath));

        return $this->importFromJson($content, basename($filePath, '.json'), $fileCreatedAt, $fileUpdatedAt);
    }

    /**
     * Import from JSON string content
     */
    public function importFromJson(string $jsonContent, ?string $sourceName = null, ?Carbon $fileCreatedAt = null, ?Carbon $fileUpdatedAt = null): AssetConfiguration
    {
        $decodedConfig = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON content: '.json_last_error_msg());
        }

        if (! is_array($decodedConfig)) {
            throw new \InvalidArgumentException('JSON content must be an object/array');
        }

        $this->config = $decodedConfig;

        if (! $this->user) {
            throw new \RuntimeException('No authenticated user found for import');
        }

        Log::info('AssetImportService: Starting import', [
            'user_id' => $this->user->id,
            'team_id' => $this->teamId,
            'source' => $sourceName,
        ]);

        // Store timestamps for use in creation methods
        $this->fileCreatedAt = $fileCreatedAt;
        $this->fileUpdatedAt = $fileUpdatedAt;

        return DB::transaction(function () use ($sourceName) {
            // Create AssetConfiguration from meta data
            $assetConfiguration = $this->createAssetConfiguration($sourceName);

            // Reset sort order counter for this asset configuration
            $this->currentSortOrder = 1;

            // Process each asset section in the JSON
            foreach ($this->config as $key => $section) {
                if ($key === 'meta') {
                    continue; // Skip meta section
                }

                if (! is_array($section) || ! isset($section['meta'])) {
                    Log::warning('AssetImportService: Skipping invalid section', ['section' => $key]);

                    continue;
                }

                $asset = $this->createAssetFromSection($assetConfiguration, $key, $section);
                if ($asset === null) {
                    continue; // Skip this asset if it couldn't be created due to invalid type
                }
            }

            Log::info('AssetImportService: Import completed', [
                'asset_configuration_id' => $assetConfiguration->id,
                'assets_created' => $assetConfiguration->assets()->count(),
            ]);

            return $assetConfiguration;
        });
    }

    /**
     * Validate and sanitize an icon, with warning if invalid
     */
    protected function validateIcon(?string $icon, string $context = 'asset owner'): ?string
    {
        if (empty($icon)) {
            return null;
        }

        $validatedIcon = HeroiconValidator::validateAndSanitize($icon);

        if ($validatedIcon === null) {
            $suggestions = HeroiconValidator::getSuggestions($icon);
            $suggestionText = empty($suggestions) ? '' : ' Suggestions: '.implode(', ', $suggestions);

            Log::warning("Invalid Heroicon '{$icon}' for {$context}. Setting to null.{$suggestionText}");
            echo "  ⚠️  Invalid icon '{$icon}' for {$context}. Setting to null.{$suggestionText}\n";
        }

        return $validatedIcon;
    }

    /**
     * Validate and sanitize an asset type, with warning if invalid
     */
    protected function validateAssetType(?string $assetType, string $context = 'asset'): ?string
    {
        return AssetTypeValidator::validateAndSanitize($assetType, $context);
    }

    /**
     * Create AssetConfiguration from meta section
     */
    protected function createAssetConfiguration(?string $sourceName = null): AssetConfiguration
    {
        $meta = Arr::get($this->config, 'meta', []);

        $name = Arr::get($meta, 'name', $sourceName ?? 'Imported Asset Configuration');
        $birthYear = (int) Arr::get($meta, 'birthYear');
        $prognoseAge = (int) Arr::get($meta, 'prognoseAge');
        $pensionOfficialAge = (int) Arr::get($meta, 'pensionOfficialAge');
        $pensionWishAge = (int) Arr::get($meta, 'pensionWishAge');
        $deathAge = (int) Arr::get($meta, 'deathAge');
        $exportStartYear = (int) Arr::get($meta, 'exportStartYear', now()->year - 1);

        // Validate icon
        $rawIcon = Arr::get($meta, 'icon');
        $validatedIcon = $this->validateIcon($rawIcon, "asset owner '{$name}'");

        $data = [
            'name' => $name,
            'description' => Arr::get($meta, 'description') ?: 'Imported from JSON configuration'.($sourceName ? " ({$sourceName})" : ''),
            'birth_year' => $birthYear ?: null,
            'prognose_age' => $prognoseAge ?: null,
            'pension_official_age' => $pensionOfficialAge ?: null,
            'pension_wish_age' => $pensionWishAge ?: null,
            'death_age' => $deathAge ?: null,
            'export_start_age' => $exportStartYear,
            'public' => false,
            'icon' => $validatedIcon,
            'color' => Arr::get($meta, 'color'),
            'tags' => Arr::get($meta, 'tags', []),
            'user_id' => $this->user->id,
            'team_id' => $this->teamId,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', $name.'_created'),
            'updated_checksum' => hash('sha256', $name.'_updated'),
        ];

        // Create the asset configuration
        $assetConfiguration = AssetConfiguration::create($data);

        // Set file timestamps if provided
        if ($this->fileCreatedAt || $this->fileUpdatedAt) {
            AssetConfiguration::withoutTimestamps(function () use ($assetConfiguration) {
                if ($this->fileCreatedAt) {
                    $assetConfiguration->created_at = $this->fileCreatedAt;
                }
                if ($this->fileUpdatedAt) {
                    $assetConfiguration->updated_at = $this->fileUpdatedAt;
                }
                $assetConfiguration->save();
            });
            $assetConfiguration->refresh(); // Refresh to get the updated timestamps
        }

        Log::info('AssetImportService: Created AssetConfiguration', [
            'id' => $assetConfiguration->id,
            'name' => $assetConfiguration->name,
        ]);

        return $assetConfiguration;
    }

    /**
     * Create Asset from a section in the JSON config
     */
    protected function createAssetFromSection(AssetConfiguration $assetConfiguration, string $sectionKey, array $section): ?Asset
    {
        $meta = $section['meta'];

        // Validate asset type
        $rawAssetType = Arr::get($meta, 'type', 'unknown');
        $validatedAssetType = $this->validateAssetType($rawAssetType, "asset '{$sectionKey}'");

        // If asset type is invalid, skip this asset
        if ($validatedAssetType === null) {
            Log::warning("Skipping asset '{$sectionKey}' due to invalid asset type '{$rawAssetType}'");
            echo "  ❌ Skipping asset '{$sectionKey}' due to invalid asset type '{$rawAssetType}'\n";

            return null;
        }

        $data = [
            'asset_configuration_id' => $assetConfiguration->id,
            'user_id' => $this->user->id,
            'team_id' => $this->teamId,
            'name' => Arr::get($meta, 'name', $sectionKey),
            'description' => Arr::get($meta, 'description', ''),
            'asset_type' => $validatedAssetType,
            'group' => Arr::get($meta, 'group', 'private'),
            'tax_type' => Arr::get($meta, 'tax'),
            'tax_property' => Arr::get($meta, 'taxProperty'),
            'tax_country' => 'no',
            'is_active' => Arr::get($meta, 'active', true),
            'sort_order' => $this->currentSortOrder++,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', $sectionKey.'_created'),
            'updated_checksum' => hash('sha256', $sectionKey.'_updated'),
        ];

        // Create the asset
        $asset = Asset::create($data);

        // Set file timestamps if provided
        if ($this->fileCreatedAt || $this->fileUpdatedAt) {
            Asset::withoutTimestamps(function () use ($asset) {
                if ($this->fileCreatedAt) {
                    $asset->created_at = $this->fileCreatedAt;
                }
                if ($this->fileUpdatedAt) {
                    $asset->updated_at = $this->fileUpdatedAt;
                }
                $asset->save();
            });
        }

        // Process yearly data
        foreach ($section as $yearKey => $yearData) {
            if ($yearKey === 'meta' || ! is_array($yearData)) {
                continue;
            }

            $this->createAssetYearData($asset, $yearKey, $yearData);
        }

        Log::info('AssetImportService: Created Asset', [
            'id' => $asset->id,
            'name' => $asset->name,
            'asset_type' => $asset->asset_type,
            'years_created' => $asset->years()->count(),
        ]);

        return $asset;
    }

    /**
     * Create AssetYear data from yearly section
     */
    protected function createAssetYearData(Asset $asset, string $yearKey, array $yearData): void
    {
        // Handle variable year keys like $pensionWishYear
        $year = $this->resolveYearKey($yearKey);

        if (! $year) {
            Log::warning('AssetImportService: Could not resolve year key', ['year_key' => $yearKey]);

            return;
        }

        $data = [
            'user_id' => $this->user->id,
            'team_id' => $this->teamId,
            'year' => $year,
            'asset_id' => $asset->id,
            'asset_configuration_id' => $asset->asset_configuration_id,

            // Unified description: append all existing descriptions into a single field
            'description' => trim(collect([
                Arr::get($yearData, 'income.description') ? 'Income: '.Arr::get($yearData, 'income.description') : null,
                Arr::get($yearData, 'expence.description') ? 'Expense: '.Arr::get($yearData, 'expence.description') : null,
                Arr::get($yearData, 'asset.description') ? 'Asset: '.Arr::get($yearData, 'asset.description') : null,
                Arr::get($yearData, 'mortgage.description') ? 'Mortgage: '.Arr::get($yearData, 'mortgage.description') : null,
            ])->filter()->implode(' | ')),

            // Income data
            'income_amount' => (float) Arr::get($yearData, 'income.amount', 0),
            'income_factor' => $this->convertFactorToEnum(Arr::get($yearData, 'income.factor', 1)),
            'income_rule' => Arr::get($yearData, 'income.rule'),
            'income_transfer' => Arr::get($yearData, 'income.transfer'),
            'income_source' => Arr::get($yearData, 'income.source'),
            'income_changerate' => Arr::get($yearData, 'income.changerate'),
            'income_repeat' => (bool) Arr::get($yearData, 'income.repeat', false),

            // Expense data
            'expence_amount' => (float) Arr::get($yearData, 'expence.amount', 0),
            'expence_factor' => $this->convertFactorToEnum(Arr::get($yearData, 'expence.factor', 1)),
            'expence_rule' => Arr::get($yearData, 'expence.rule'),
            'expence_transfer' => Arr::get($yearData, 'expence.transfer'),
            'expence_source' => Arr::get($yearData, 'expence.source'),
            'expence_changerate' => Arr::get($yearData, 'expence.changerate'),
            'expence_repeat' => (bool) Arr::get($yearData, 'expence.repeat', false),

            // Asset data
            'asset_market_amount' => (float) Arr::get($yearData, 'asset.marketAmount', 0),
            'asset_acquisition_amount' => (float) Arr::get($yearData, 'asset.acquisitionAmount', 0),
            'asset_equity_amount' => (float) Arr::get($yearData, 'asset.equityAmount', 0),
            'asset_taxable_initial_amount' => (float) Arr::get($yearData, 'asset.taxableInitialAmount', 0),
            'asset_paid_amount' => (float) Arr::get($yearData, 'asset.paidAmount', 0),
            'asset_changerate' => Arr::get($yearData, 'asset.changerate'),
            'asset_rule' => Arr::get($yearData, 'asset.rule'),
            'asset_transfer' => Arr::get($yearData, 'asset.transfer'),
            'asset_source' => Arr::get($yearData, 'asset.source'),
            'asset_repeat' => (bool) Arr::get($yearData, 'asset.repeat', false),

            // Mortgage data
            'mortgage_amount' => (float) Arr::get($yearData, 'mortgage.amount', 0),
            'mortgage_years' => (int) Arr::get($yearData, 'mortgage.years', 0),
            'mortgage_interest' => Arr::get($yearData, 'mortgage.interest'),
            'mortgage_gebyr' => (float) Arr::get($yearData, 'mortgage.gebyr', 0),
            'mortgage_tax' => (float) Arr::get($yearData, 'mortgage.tax', 0),
            'mortgage_extra_downpayment_amount' => Arr::get($yearData, 'mortgage.paymentExtra'),

            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_checksum' => hash('sha256', $asset->id.'_'.$year.'_created'),
            'updated_checksum' => hash('sha256', $asset->id.'_'.$year.'_updated'),
        ];

        // Create the asset year
        $assetYear = AssetYear::create($data);

        // Set file timestamps if provided
        if ($this->fileCreatedAt || $this->fileUpdatedAt) {
            AssetYear::withoutTimestamps(function () use ($assetYear) {
                if ($this->fileCreatedAt) {
                    $assetYear->created_at = $this->fileCreatedAt;
                }
                if ($this->fileUpdatedAt) {
                    $assetYear->updated_at = $this->fileUpdatedAt;
                }
                $assetYear->save();
            });
        }

        Log::debug('AssetImportService: Created AssetYear', [
            'asset_id' => $asset->id,
            'year' => $year,
            'id' => $assetYear->id,
        ]);
    }

    /**
     * Resolve year key to actual year number
     */
    protected function resolveYearKey(string $yearKey): ?int
    {
        // If it's already a numeric year, return it
        if (is_numeric($yearKey)) {
            return (int) $yearKey;
        }

        // Handle variable year keys like $pensionWishYear
        $meta = Arr::get($this->config, 'meta', []);
        $birthYear = (int) Arr::get($meta, 'birthYear', 0);

        switch ($yearKey) {
            case '$pensionWishYear':
                $pensionWishAge = (int) Arr::get($meta, 'pensionWishAge', 0);

                return $birthYear && $pensionWishAge ? $birthYear + $pensionWishAge : null;

            case '$pensionOfficialYear':
                $pensionOfficialAge = (int) Arr::get($meta, 'pensionOfficialAge', 0);

                return $birthYear && $pensionOfficialAge ? $birthYear + $pensionOfficialAge : null;

            case '$otpStartYear':
                // This would need more complex logic based on the specific configuration
                return null;

            default:
                Log::warning('AssetImportService: Unknown year key', ['year_key' => $yearKey]);

                return null;
        }
    }

    /**
     * Convert numeric factor to enum value
     */
    protected function convertFactorToEnum($factor): string
    {
        if (is_string($factor)) {
            // Already an enum value, validate it
            return in_array($factor, ['monthly', 'yearly']) ? $factor : 'yearly';
        }

        // Convert numeric factor to enum
        $numericFactor = (int) $factor;
        return $numericFactor === 12 ? 'monthly' : 'yearly';
    }

    /**
     * Static method for easy tinker usage
     */
    public static function importFile(string $filePath, ?User $user = null, ?int $teamId = null): AssetConfiguration
    {
        $service = new static($user, $teamId);

        return $service->importFromFile($filePath);
    }

    /**
     * Import from JSON string for tinker usage
     */
    public static function importJson(string $jsonContent, ?string $sourceName = null, ?User $user = null, ?int $teamId = null): AssetConfiguration
    {
        $service = new static($user, $teamId);

        return $service->importFromJson($jsonContent, $sourceName);
    }

    /**
     * Import a test file from the test config directory
     */
    public static function importTestFile(string $filename, ?User $user = null, ?int $teamId = null): AssetConfiguration
    {
        $testPath = base_path('tests/Feature/config/'.$filename);
        if (! str_ends_with($filename, '.json')) {
            $testPath .= '.json';
        }

        if (! file_exists($testPath)) {
            throw new \InvalidArgumentException("Test file not found: {$testPath}");
        }

        return static::importFile($testPath, $user, $teamId);
    }

    /**
     * List available test files
     */
    public static function listTestFiles(): array
    {
        $testDir = base_path('tests/Feature/config');
        if (! is_dir($testDir)) {
            return [];
        }

        $files = glob($testDir.'/*.json');

        return array_map(fn ($file) => basename($file), $files);
    }
}
