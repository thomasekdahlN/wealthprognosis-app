<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_root_redirects_to_default_locale()
    {
        $response = $this->get('/');

        $response->assertStatus(301);
        $response->assertRedirect('/en');
    }

    public function test_the_english_home_page_returns_a_successful_response()
    {
        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('Wealth Prognosis', false);
    }

    public function test_the_norwegian_home_page_returns_a_successful_response()
    {
        $response = $this->get('/nb');

        $response->assertStatus(200);
        $response->assertSee('Wealth Prognosis', false);
        $response->assertSee('økonomiske fremtid', false);
    }

    public function test_the_english_features_page_returns_a_successful_response()
    {
        $response = $this->get('/en/features');

        $response->assertStatus(200);
        $response->assertSee('Features', false);
    }

    public function test_the_norwegian_features_page_returns_a_successful_response()
    {
        $response = $this->get('/nb/features');

        $response->assertStatus(200);
        $response->assertSee('Funksjoner', false);
    }

    public function test_the_english_about_page_returns_a_successful_response()
    {
        $response = $this->get('/en/about');

        $response->assertStatus(200);
        $response->assertSee('About', false);
        $response->assertSee('Principles', false);
    }

    public function test_the_norwegian_about_page_returns_a_successful_response()
    {
        $response = $this->get('/nb/about');

        $response->assertStatus(200);
        $response->assertSee('Om oss', false);
        $response->assertSee('Prinsipper', false);
    }

    public function test_the_english_pricing_page_returns_a_successful_response()
    {
        $response = $this->get('/en/pricing');

        $response->assertStatus(200);
        $response->assertSee('Pricing', false);
        $response->assertSee('Solo', false);
        $response->assertSee('Advisor', false);
    }

    public function test_the_norwegian_pricing_page_returns_a_successful_response()
    {
        $response = $this->get('/nb/pricing');

        $response->assertStatus(200);
        $response->assertSee('Priser', false);
        $response->assertSee('Solo', false);
        $response->assertSee('Rådgiver', false);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function phaseTwoRouteProvider(): array
    {
        return [
            'EN use-cases' => ['/en/use-cases', 'Use cases'],
            'NB use-cases' => ['/nb/use-cases', 'Bruksområder'],
            'EN faq' => ['/en/faq', 'FAQ'],
            'NB faq' => ['/nb/faq', 'FAQ'],
            'EN glossary' => ['/en/glossary', 'Glossary'],
            'NB glossary' => ['/nb/glossary', 'Ordliste'],
            'EN methodology' => ['/en/methodology', 'Methodology'],
            'NB methodology' => ['/nb/methodology', 'Metodikk'],
            'EN legal' => ['/en/legal', 'Legal'],
            'NB legal' => ['/nb/legal', 'Juridisk'],
        ];
    }

    #[DataProvider('phaseTwoRouteProvider')]
    public function test_phase_two_pages_return_200(string $url, string $expected): void
    {
        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertSee($expected, false);
    }

    public function test_the_footer_contains_company_contact_information()
    {
        $response = $this->get('/en');

        $response->assertStatus(200);
        $response->assertSee('Ekdahl Enterprises AS', false);
        $response->assertSee('933 662 541', false);
        $response->assertSee('Smørbukkveien 3', false);
        $response->assertSee('Tønsberg', false);
        $response->assertSee('thomas@ekdahl.no', false);
        $response->assertSee('+47 911 43 630', false);
    }
}
