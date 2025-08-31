<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Command to display the currently running PHP version information.
 */
class PhpVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:php-version';

    /**
     * The console command description.
     */
    protected $description = 'Display the currently running PHP version and environment details';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('PHP Version: '.PHP_VERSION);
        $this->line('SAPI: '.PHP_SAPI);

        $loadedIni = php_ini_loaded_file();
        $this->line('Loaded php.ini: '.($loadedIni !== false ? $loadedIni : 'none'));

        return self::SUCCESS;
    }
}
