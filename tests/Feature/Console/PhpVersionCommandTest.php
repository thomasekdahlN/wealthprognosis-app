<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class PhpVersionCommandTest extends TestCase
{
    public function test_it_displays_the_running_php_version(): void
    {
        $this->artisan('app:php-version')
            ->expectsOutputToContain('PHP Version: ')
            ->expectsOutputToContain('SAPI: ')
            ->expectsOutputToContain('Loaded php.ini: ')
            ->assertExitCode(0);
    }
}
