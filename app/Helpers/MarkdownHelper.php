<?php

namespace App\Helpers;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownHelper
{
    private static ?MarkdownConverter $converter = null;

    /**
     * Get a configured markdown converter instance
     */
    private static function getConverter(): MarkdownConverter
    {
        if (self::$converter === null) {
            // Create environment with GitHub Flavored Markdown support
            $environment = new Environment([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 10,
            ]);

            // Add core CommonMark extension
            $environment->addExtension(new CommonMarkCoreExtension);

            // Add GitHub Flavored Markdown extension for better formatting
            try {
                $environment->addExtension(new GithubFlavoredMarkdownExtension);
            } catch (\Exception $e) {
                // Fallback if GFM extension is not available
            }

            self::$converter = new MarkdownConverter($environment);
        }

        return self::$converter;
    }

    /**
     * Convert markdown text to HTML
     */
    public static function toHtml(string $markdown): string
    {
        try {
            return self::getConverter()->convert($markdown)->getContent();
        } catch (\Exception $e) {
            // Fallback to plain text with line breaks if markdown parsing fails
            return nl2br(htmlspecialchars($markdown));
        }
    }

    /**
     * Convert markdown text to HTML with safe defaults for AI content
     */
    public static function aiContentToHtml(mixed $content): string
    {
        // Handle different content types
        if (is_string($content)) {
            return self::toHtml($content);
        } elseif (is_array($content)) {
            return '<pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto">'.
                   htmlspecialchars(json_encode($content, JSON_PRETTY_PRINT)).
                   '</pre>';
        } else {
            return '<div class="text-gray-500 italic">Unable to display content</div>';
        }
    }
}
