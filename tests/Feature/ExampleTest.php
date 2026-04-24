<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Wealth Prognosis', false);
    }

    public function test_the_features_page_returns_a_successful_response()
    {
        $response = $this->get('/features');

        $response->assertStatus(200);
        $response->assertSee('Features', false);
    }

    public function test_the_faq_page_returns_a_successful_response()
    {
        $response = $this->get('/faq');

        $response->assertStatus(200);
        $response->assertSee('Frequently asked', false);
    }

    public function test_the_about_page_returns_a_successful_response()
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('About', false);
        $response->assertSee('Principles', false);
    }

    public function test_the_pricing_page_returns_a_successful_response()
    {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
        $response->assertSee('Pricing', false);
        $response->assertSee('Self-hosted', false);
        $response->assertSee('Solo', false);
        $response->assertSee('Advisor', false);
    }
}
