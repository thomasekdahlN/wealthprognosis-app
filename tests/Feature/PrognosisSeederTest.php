<?php

namespace Tests\Feature;

use App\Models\PrognosisType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrognosisSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_prognoses_with_icon_color_and_public_true(): void
    {
        $this->artisan('db:seed', ['--class' => 'PrognosisSeeder']);

        $prognoses = PrognosisType::all();
        $this->assertNotEmpty($prognoses);

        foreach ($prognoses as $p) {
            $this->assertTrue($p->public);
            $this->assertNotNull($p->icon);
            $this->assertNotNull($p->color);
        }
    }
}
