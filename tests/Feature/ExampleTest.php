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
        $response->assertSee('Solo', false);
        $response->assertSee('Family', false);
        $response->assertSee('Advisor', false);
    }

    public function test_the_use_cases_page_returns_a_successful_response()
    {
        $response = $this->get('/use-cases');

        $response->assertStatus(200);
        $response->assertSee('Use cases', false);
        $response->assertSee('F.I.R.E', false);
        $response->assertSee('Property investor', false);
    }

    public function test_the_glossary_page_returns_a_successful_response()
    {
        $response = $this->get('/glossary');

        $response->assertStatus(200);
        $response->assertSee('Glossary', false);
        $response->assertSee('Fritaksmetoden', false);
        $response->assertSee('Crossover', false);
        $response->assertSee('DefinedTermSet', false);
    }

    public function test_the_methodology_page_returns_a_successful_response()
    {
        $response = $this->get('/methodology');

        $response->assertStatus(200);
        $response->assertSee('Methodology', false);
        $response->assertSee('FIRE number', false);
        $response->assertSee('Fortune tax', false);
        $response->assertSee('TechArticle', false);
    }

    public function test_the_legal_page_returns_a_successful_response()
    {
        $response = $this->get('/legal');

        $response->assertStatus(200);
        $response->assertSee('Terms of service', false);
        $response->assertSee('Privacy policy', false);
        $response->assertSee('Cookies', false);
    }

    public function test_the_personvern_page_returns_a_successful_response()
    {
        $response = $this->get('/personvern');

        $response->assertStatus(200);
        $response->assertSee('Personvern', false);
        $response->assertSee('Ekdahl Enterprises AS', false);
        $response->assertSee('933 662 541', false);
        $response->assertSee('Datatilsynet', false);
        $response->assertSee('PrivacyPolicy', false);
    }

    public function test_the_footer_contains_company_contact_information()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Ekdahl Enterprises AS', false);
        $response->assertSee('933 662 541', false);
        $response->assertSee('Smørbukkveien 3', false);
        $response->assertSee('Tønsberg', false);
        $response->assertSee('thomas@ekdahl.no', false);
        $response->assertSee('+47 911 43 630', false);
    }
}
