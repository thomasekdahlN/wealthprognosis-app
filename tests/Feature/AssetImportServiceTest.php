<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\AssetYear;
use App\Models\User;
use App\Services\AssetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AssetImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database with necessary data for asset type validation
        $this->seed([
            \Database\Seeders\AssetCategorySeeder::class,
            \Database\Seeders\AssetTypeSeeder::class,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Auth::login($this->user);
    }

    public function test_can_import_simple_json_configuration(): void
    {
        $jsonContent = json_encode([
            'meta' => [
                'name' => 'Test Owner',
                'birthYear' => '1980',
                'prognoseAge' => '50',
                'pensionOfficialAge' => '67',
                'pensionWishAge' => '63',
                'deathAge' => '82',
            ],
            'house' => [
                'meta' => [
                    'type' => 'house',
                    'group' => 'private',
                    'name' => 'Test House',
                    'description' => 'A test house',
                    'active' => true,
                    'tax' => 'house',
                ],
                '2023' => [
                    'asset' => [
                        'marketAmount' => 3000000,
                        'changerate' => 'changerates.house',
                        'description' => 'House asset',
                        'repeat' => true,
                    ],
                    'expence' => [
                        'name' => 'House Expenses',
                        'description' => 'Monthly expenses',
                        'amount' => 7300,
                        'factor' => 'monthly',
                        'changerate' => 'changerates.kpi',
                        'repeat' => true,
                    ],
                ],
            ],
        ]);

        $service = new AssetImportService($this->user);
        $assetOwner = $service->importFromJson($jsonContent, 'test-config');

        // Assert AssetOwner was created correctly
        $this->assertInstanceOf(AssetConfiguration::class, $assetOwner);
        $this->assertEquals('Test Owner', $assetOwner->name);
        $this->assertEquals(1980, $assetOwner->birth_year);
        $this->assertEquals(50, $assetOwner->prognose_age);
        $this->assertEquals(67, $assetOwner->pension_official_age);
        $this->assertEquals(63, $assetOwner->pension_wish_age);
        $this->assertEquals(82, $assetOwner->death_age);
        $this->assertEquals($this->user->id, $assetOwner->user_id);

        // Assert Asset was created
        $this->assertEquals(1, $assetOwner->assets()->count());
        $asset = $assetOwner->assets()->first();
        $this->assertEquals('Test House', $asset->name);
        $this->assertEquals('house', $asset->asset_type);
        $this->assertEquals('private', $asset->group);
        $this->assertEquals('house', $asset->tax_type);
        $this->assertTrue($asset->is_active);

        // Assert AssetYear was created
        $this->assertEquals(1, $asset->years()->count());
        $assetYear = $asset->years()->first();
        $this->assertEquals(2023, $assetYear->year);
        $this->assertEquals(3000000, $assetYear->asset_market_amount);
        $this->assertEquals('House Expenses', $assetYear->expence_name);
        $this->assertEquals(7300, $assetYear->expence_amount);
        $this->assertEquals('monthly', $assetYear->expence_factor);
    }

    public function test_can_import_from_existing_test_file(): void
    {
        $testFile = base_path('tests/Feature/config/boat.json');

        if (! file_exists($testFile)) {
            $this->markTestSkipped('Test file boat.json not found');
        }

        $service = new AssetImportService($this->user);
        $assetOwner = $service->importFromFile($testFile);

        $this->assertInstanceOf(AssetConfiguration::class, $assetOwner);
        $this->assertEquals('Marina Svendsen', $assetOwner->name);
        $this->assertEquals(1975, $assetOwner->birth_year);

        // Should have one boat asset
        $this->assertEquals(1, $assetOwner->assets()->count());
        $asset = $assetOwner->assets()->first();
        $this->assertEquals('Sailing Yacht \'Sea Breeze\'', $asset->name);
        $this->assertEquals('boat', $asset->asset_type);
    }

    public function test_static_import_file_method(): void
    {
        $testFile = base_path('tests/Feature/config/cash.json');

        if (! file_exists($testFile)) {
            $this->markTestSkipped('Test file cash.json not found');
        }

        $assetOwner = AssetImportService::importFile($testFile, $this->user);

        $this->assertInstanceOf(AssetConfiguration::class, $assetOwner);
        $this->assertDatabaseHas('asset_owners', [
            'id' => $assetOwner->id,
            'name' => 'Lars Kristiansen',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_static_import_test_file_method(): void
    {
        $testFiles = AssetImportService::listTestFiles();

        if (empty($testFiles)) {
            $this->markTestSkipped('No test files found');
        }

        // Try to import the first available test file
        $filename = $testFiles[0];
        $assetOwner = AssetImportService::importTestFile($filename, $this->user);

        $this->assertInstanceOf(AssetConfiguration::class, $assetOwner);
        $this->assertDatabaseHas('asset_owners', [
            'id' => $assetOwner->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_handles_invalid_json(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON content');

        $service = new AssetImportService($this->user);
        $service->importFromJson('invalid json content');
    }

    public function test_handles_missing_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file not found');

        $service = new AssetImportService($this->user);
        $service->importFromFile('/nonexistent/file.json');
    }

    public function test_requires_authenticated_user(): void
    {
        Auth::logout();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No authenticated user found for import');

        $service = new AssetImportService;
        $service->importFromJson('{"meta": {"name": "Test"}}');
    }

    public function test_list_test_files(): void
    {
        $files = AssetImportService::listTestFiles();

        $this->assertIsArray($files);
        // Should contain some of the known test files if they exist
        $expectedFiles = ['boat.json', 'cash.json', 'house.json', 'example_simple.json'];

        foreach ($expectedFiles as $expectedFile) {
            if (file_exists(base_path('tests/Feature/config/'.$expectedFile))) {
                $this->assertContains($expectedFile, $files);
            }
        }
    }
}
