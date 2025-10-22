<?php

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;

it('each JSON file decodes without error and reports which file fails', function (): void {
    $roots = [
        base_path('config'),
        base_path('tests/Feature/config'),
    ];

    $errors = [];

    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'json') {
                continue;
            }

            $path = $file->getPathname();
            $contents = file_get_contents($path);
            if ($contents === false) {
                $errors[$path] = 'Unable to read file contents';

                continue;
            }

            json_decode($contents, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[$path] = json_last_error_msg();
            }
        }
    }

    if (! empty($errors)) {
        $pretty = '';
        foreach ($errors as $file => $message) {
            $pretty .= "\n - {$file}: {$message}";
        }

        throw new ExpectationFailedException(
            'Invalid JSON detected in the following files:'.$pretty
        );
    }

    expect(true)->toBeTrue();
});
