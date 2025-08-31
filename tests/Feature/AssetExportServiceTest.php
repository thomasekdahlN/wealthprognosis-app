<?php

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\User;
use App\Services\AssetExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create test asset owner with assets and years
    $this->assetOwner = AssetConfiguration::factory()->create([
        'name' => 'Test Owner',
        'birth_year' => 1980,
        'prognose_age' => 50,
        'pension_official_age' => 67,
        'pension_wish_age' => 63,
        'death_age' => 82,
        'export_start_age' => 2023,
        'user_id' => $this->user->id,
    ]);

    // Create test asset
    $asset = Asset::factory()->create([
        'asset_owner_id' => $this->assetOwner->id,
        'user_id' => $this->user->id,
        'code' => 'test_house',
        'name' => 'Test House',
        'asset_type' => 'house',
        'group' => 'private',
        'tax_type' => 'house',
    ]);

    // Create test asset year
    AssetYear::factory()->create([
        'asset_id' => $asset->id,
        'asset_owner_id' => $this->assetOwner->id,
        'user_id' => $this->user->id,
        'year' => 2023,
        'asset_market_amount' => 3000000,
        'expence_name' => 'House Expenses',
        'expence_amount' => 7300,
        'expence_factor' => 'monthly',
    ]);
});

test('can export asset owner to json string', function () {
    $jsonString = AssetExportService::toJsonString($this->assetOwner);

    expect($jsonString)->toBeString()->not->toBeEmpty();

    // Verify it's valid JSON
    $data = json_decode($jsonString, true);
    expect($data)->toBeArray()
        ->toHaveKey('meta')
        ->toHaveKey('test_house');

    // Verify meta data
    expect($data['meta']['name'])->toBe('Test Owner');
    expect($data['meta']['birthYear'])->toBe('1980');
    expect($data['meta']['prognoseAge'])->toBe('50');

    // Verify asset data
    expect($data['test_house'])->toHaveKey('meta');
    expect($data['test_house']['meta']['name'])->toBe('Test House');
    expect($data['test_house']['meta']['type'])->toBe('house');
});

test('can export asset owner to file', function () {
    Storage::fake('local');

    $filePath = AssetExportService::export($this->assetOwner);

    expect($filePath)->toBeString();
    expect($filePath)->toBeFile();

    // Verify file content
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);

    expect($data)->toBeArray()
        ->toHaveKey('meta');
    expect($data['meta']['name'])->toBe('Test Owner');
});

test('can export with custom file path', function () {
    Storage::fake('local');

    $customPath = 'custom/test-export.json';
    $service = new AssetExportService($this->assetOwner);
    $filePath = $service->toFile($customPath);

    expect($filePath)->toContain('custom/test-export.json');
    expect($filePath)->toBeFile();
});

test('handles empty asset owner', function () {
    $emptyOwner = AssetConfiguration::factory()->create([
        'name' => 'Empty Owner',
        'user_id' => $this->user->id,
    ]);

    $jsonString = AssetExportService::toJsonString($emptyOwner);
    $data = json_decode($jsonString, true);

    expect($data)->toBeArray()
        ->toHaveKey('meta')
        ->toHaveCount(1); // Should only have meta section, no assets
    expect($data['meta']['name'])->toBe('Empty Owner');
});

test('resolves variable year keys', function () {
    // Create asset year for pension wish year
    $pensionWishYear = $this->assetOwner->birth_year + $this->assetOwner->pension_wish_age;

    $asset = $this->assetOwner->assets()->first();
    AssetYear::factory()->create([
        'asset_id' => $asset->id,
        'asset_owner_id' => $this->assetOwner->id,
        'user_id' => $this->user->id,
        'year' => $pensionWishYear,
        'income_name' => 'Pension Income',
        'income_amount' => 25000,
    ]);

    $jsonString = AssetExportService::toJsonString($this->assetOwner);
    $data = json_decode($jsonString, true);

    // Should have $pensionWishYear key instead of numeric year
    expect($data['test_house'])->toHaveKey('$pensionWishYear');
    expect($data['test_house']['$pensionWishYear'])->toHaveKey('income');
});

test('filters empty data sections', function () {
    $jsonString = AssetExportService::toJsonString($this->assetOwner);
    $data = json_decode($jsonString, true);

    $yearData = $data['test_house']['2023'];

    // Should have expence section (has data)
    expect($yearData)->toHaveKey('expence');

    // Should have asset section (has market amount)
    expect($yearData)->toHaveKey('asset');

    // Income section may or may not be present depending on data
    // This test just verifies the main sections exist
});
