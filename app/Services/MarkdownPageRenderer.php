<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\ValueObjects\MarkdownPage;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Resolves and parses the per-locale Markdown source files that power the
 * public marketing site.
 *
 * Each `.md` file is expected to begin with a YAML front matter block
 * delimited by `---` lines. The front matter must declare at least a
 * `template` key, which maps to a Blade view under `public/*` that knows
 * how to render the structured data the front matter provides.
 */
final class MarkdownPageRenderer
{
    private ?MarkdownConverter $converter = null;

    public function __construct(
        private readonly string $contentPath,
        private readonly string $defaultLocale,
    ) {}

    /**
     * Locate, parse and return a {@see MarkdownPage} for the given slug + locale.
     *
     * Falls back to the default locale when a translation file is missing.
     * Returns null when neither file exists.
     */
    public function find(string $slug, string $locale): ?MarkdownPage
    {
        $file = $this->resolveFile($slug, $locale);

        if ($file === null) {
            return null;
        }

        return $this->parse($file, $slug, $locale);
    }

    /**
     * Resolve the absolute path to the Markdown file for the slug + locale,
     * with a fallback to the default locale.
     */
    private function resolveFile(string $slug, string $locale): ?string
    {
        $candidates = [
            $this->contentPath.'/'.$locale.'/'.$slug.'.md',
            $this->contentPath.'/'.$this->defaultLocale.'/'.$slug.'.md',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Parse a Markdown file with YAML front matter into a {@see MarkdownPage}.
     */
    private function parse(string $file, string $slug, string $locale): MarkdownPage
    {
        $raw = (string) file_get_contents($file);

        [$frontMatter, $body] = $this->splitFrontMatter($raw);

        $template = (string) ($frontMatter['template'] ?? '');

        if ($template === '') {
            throw new RuntimeException("Markdown file {$file} is missing the required `template` front matter key.");
        }

        $title = (string) ($frontMatter['title'] ?? 'Wealth Prognosis');
        $description = (string) ($frontMatter['description'] ?? '');
        $ogType = (string) ($frontMatter['og_type'] ?? 'website');

        return new MarkdownPage(
            slug: $slug,
            locale: $locale,
            template: $template,
            title: $title,
            description: $description,
            ogType: $ogType,
            body: trim($body) === '' ? '' : $this->converter()->convert($body)->getContent(),
            frontMatter: $frontMatter,
        );
    }

    /**
     * Split the raw file contents into [front matter array, markdown body].
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function splitFrontMatter(string $raw): array
    {
        if (! str_starts_with($raw, '---')) {
            return [[], $raw];
        }

        $parts = preg_split('/^---\s*$/m', $raw, 3);

        if ($parts === false || count($parts) < 3) {
            return [[], $raw];
        }

        /** @var array<string, mixed>|null $parsed */
        $parsed = Yaml::parse(trim($parts[1]));

        return [is_array($parsed) ? $parsed : [], ltrim($parts[2], "\r\n")];
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
