<?php

declare(strict_types=1);

namespace App\Support\ValueObjects;

/**
 * Value object representing a parsed Markdown page used by the public site.
 *
 * Holds the front matter metadata, the rendered HTML body, and routing
 * information (slug + locale) so the page can be rendered by a Blade
 * template selected via the front matter `template` key.
 *
 * @property-read array<string, mixed> $frontMatter
 */
readonly class MarkdownPage
{
    /**
     * @param  array<string, mixed>  $frontMatter  Raw YAML front matter as an associative array.
     */
    public function __construct(
        public string $slug,
        public string $locale,
        public string $template,
        public string $title,
        public string $description,
        public string $ogType,
        public string $body,
        public array $frontMatter,
    ) {}

    /**
     * Read a value from the parsed front matter using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->frontMatter;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
