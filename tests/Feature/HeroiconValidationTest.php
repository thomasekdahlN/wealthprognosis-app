<?php

namespace Tests\Feature;

use App\Models\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroiconValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_asset_type_icons_are_valid_heroicons()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        $assetTypes = AssetType::whereNotNull('icon')->get();

        $validHeroicons = [
            'heroicon-o-academic-cap',
            'heroicon-o-arrow-path',
            'heroicon-o-arrow-trending-up',
            'heroicon-o-banknotes',
            'heroicon-o-beaker',
            'heroicon-o-briefcase',
            'heroicon-o-building-library',
            'heroicon-o-building-office',
            'heroicon-o-chart-bar',
            'heroicon-o-chart-pie',
            'heroicon-o-circle-stack',
            'heroicon-o-clock',
            'heroicon-o-computer-desktop',
            'heroicon-o-credit-card',
            'heroicon-o-cube',
            'heroicon-o-currency-dollar',
            'heroicon-o-device-phone-mobile',
            'heroicon-o-document-text',
            'heroicon-o-gift',
            'heroicon-o-heart',
            'heroicon-o-home',
            'heroicon-o-key',
            'heroicon-o-paper-airplane',
            'heroicon-o-pencil',
            'heroicon-o-receipt-percent',
            'heroicon-o-scale',
            'heroicon-o-shield-check',
            'heroicon-o-shield-exclamation',
            'heroicon-o-star',
            'heroicon-o-ticket',
            'heroicon-o-truck',
            'heroicon-o-user',
            'heroicon-o-user-group',
        ];

        foreach ($assetTypes as $assetType) {
            $this->assertContains(
                $assetType->icon,
                $validHeroicons,
                "Asset type '{$assetType->type}' has invalid icon: {$assetType->icon}"
            );
        }

        $this->assertGreaterThan(0, $assetTypes->count(), 'Should have asset types with icons');
    }

    public function test_no_problematic_heroicons_exist()
    {
        $this->artisan('db:seed', ['--class' => 'AssetCategorySeeder']);
        $this->artisan('db:seed', ['--class' => 'AssetTypeSeeder']);

        $problematicIcons = [
            'heroicon-o-receipt-tax',
            'heroicon-o-home-modern',
            'heroicon-o-gem',
            'heroicon-o-sparkles',
            'heroicon-o-paint-brush',
            'heroicon-o-rocket-launch',
            'heroicon-o-document-currency-dollar',
            'heroicon-o-chart-bar-square',
            'heroicon-o-building-office-2',
        ];

        foreach ($problematicIcons as $problematicIcon) {
            $count = AssetType::where('icon', $problematicIcon)->count();
            $this->assertEquals(0, $count, "Found {$count} asset types still using problematic icon: {$problematicIcon}");
        }
    }
}
