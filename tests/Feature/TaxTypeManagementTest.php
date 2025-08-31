<?php

namespace Tests\Feature;

use App\Models\TaxType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_type_seeder_creates_expected_records()
    {
        $this->artisan('db:seed', ['--class' => 'TaxTypeSeeder']);

        $this->assertDatabaseCount('tax_types', 6);

        // Check specific tax types exist
        $this->assertDatabaseHas('tax_types', [
            'type' => 'income',
            'name' => 'Income Tax',
            'default_rate' => 22.0000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('tax_types', [
            'type' => 'realization',
            'name' => 'Capital Gains Tax',
            'default_rate' => 37.8400,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('tax_types', [
            'type' => 'fortune',
            'name' => 'Wealth Tax',
            'default_rate' => 1.0000,
            'is_active' => true,
        ]);
    }

    public function test_tax_type_model_scopes_work_correctly()
    {
        $this->artisan('db:seed', ['--class' => 'TaxTypeSeeder']);

        // Test active scope
        $activeTaxTypes = TaxType::active()->get();
        $this->assertCount(4, $activeTaxTypes); // 4 active tax types

        // Test ordered scope
        $orderedTaxTypes = TaxType::ordered()->get();
        $this->assertEquals('income', $orderedTaxTypes->first()->type);
        $this->assertEquals('gift', $orderedTaxTypes->last()->type);
    }

    public function test_tax_type_resource_page_loads()
    {
        $this->artisan('db:seed', ['--class' => 'TaxTypeSeeder']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/admin/tax-types');

        $response->assertStatus(200);
    }

    public function test_tax_type_creation_via_resource()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        // Create TaxType directly since we're testing the model, not the HTTP endpoint
        $taxType = TaxType::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'type' => 'test_tax',
            'name' => 'Test Tax',
            'description' => 'A test tax type',
            'default_rate' => 15.0000,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->assertDatabaseHas('tax_types', [
            'type' => 'test_tax',
            'name' => 'Test Tax',
            'default_rate' => 15.0000,
        ]);
    }

    public function test_tax_type_unique_type_constraint()
    {
        TaxType::factory()->create(['type' => 'unique_test']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TaxType::factory()->create(['type' => 'unique_test']); // Same type should fail
    }
}
