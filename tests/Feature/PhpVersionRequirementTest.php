<?php

use function Pest\Laravel\artisan;

it('runs on PHP 8.4 or newer within major version 8', function (): void {
    // Ensures we are not accidentally running on an older PHP, like Homebrew php@7.2
    expect(PHP_MAJOR_VERSION)->toBe(8);
    expect(version_compare(PHP_VERSION, '8.4.0', '>='))->toBeTrue();

    // Optional sanity: our custom command should report the version
    $result = artisan('app:php-version');
    $result->assertSuccessful();
});
