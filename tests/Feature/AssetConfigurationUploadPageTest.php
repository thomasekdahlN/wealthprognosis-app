<?php

namespace Tests\Feature;

use App\Filament\Pages\AssetConfigurationUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetConfigurationUploadPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        \Livewire\Livewire::test(AssetConfigurationUpload::class)
            ->assertStatus(200);
    }

    public function test_export_generates_excel_from_fixture_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $json = file_get_contents(base_path('tests/Feature/config/example_simple.json'));
        $this->assertNotEmpty($json);

        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('example.json', $json);
        $storedPath = $file->store('asset-configs', 'local');
        $this->assertTrue(Storage::disk('local')->exists($storedPath));

        $exportDir = storage_path('app/exports');
        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }
        $exportPath = $exportDir.'/example_simple_realistic.xlsx';

        new \App\Exports\PrognosisExport2(
            Storage::disk('local')->path($storedPath),
            $exportPath,
            'realistic',
            'all'
        );

        $this->assertFileExists($exportPath);
    }
}
