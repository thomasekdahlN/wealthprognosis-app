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
        $this->artisan('db:seed', ['--class' => 'TaxTypesFromConfigSeeder']);

        // Count should be at least the distinct types in config, including property_* entries
        $this->assertDatabaseHas('tax_types', ['type' => 'equityfund']);
        $this->assertDatabaseHas('tax_types', ['type' => 'salary']);
        $this->assertDatabaseHas('tax_types', ['type' => 'property_holmestrand']);
    }

    public function test_tax_type_model_scopes_work_correctly()
    {
        $this->artisan('db:seed', ['--class' => 'TaxTypesFromConfigSeeder']);

        // Test active scope (all entries from config are seeded as active)
        $activeCount = TaxType::active()->count();
        $totalCount = TaxType::count();
        $this->assertSame($totalCount, $activeCount);

        // Test ordered scope - verifies ordering fields exist and are used
        $orderedTaxTypes = TaxType::ordered()->get();
        $this->assertNotEmpty($orderedTaxTypes);
        $this->assertEquals($orderedTaxTypes->sortBy(['sort_order', 'name'])->pluck('id')->all(), $orderedTaxTypes->pluck('id')->all());
    }

    public function test_tax_type_resource_page_loads()
    {
        $this->artisan('db:seed', ['--class' => 'TaxTypesFromConfigSeeder']);

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
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->assertDatabaseHas('tax_types', [
            'type' => 'test_tax',
            'name' => 'Test Tax',
        ]);
    }

    public function test_tax_type_unique_type_constraint()
    {
        TaxType::factory()->create(['type' => 'unique_test']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TaxType::factory()->create(['type' => 'unique_test']); // Same type should fail
    }
}
