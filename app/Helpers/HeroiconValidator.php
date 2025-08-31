<?php

namespace App\Helpers;

class HeroiconValidator
{
    /**
     * Cache for available icons from BladeUI Icons
     */
    private static ?array $availableIcons = null;

    /**
     * Get available icons - use fallback list for reliability
     */
    private static function getAvailableIcons(): array
    {
        if (self::$availableIcons === null) {
            self::$availableIcons = self::getFallbackIcons();
        }

        return self::$availableIcons;
    }

    /**
     * Test if an icon actually exists by trying to render it
     */
    public static function canRender(string $icon): bool
    {
        try {
            // Try to render the icon using Blade
            $blade = app('view');
            $rendered = $blade->make('components.icon', ['name' => $icon])->render();

            return ! empty($rendered) && ! str_contains($rendered, 'not found');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fallback list of common Heroicons v2 outline icons
     */
    private static function getFallbackIcons(): array
    {
        return [
            // Common icons that definitely exist in Heroicons v2
            'heroicon-o-academic-cap',
            'heroicon-o-adjustments-horizontal',
            'heroicon-o-adjustments-vertical',
            'heroicon-o-archive-box',
            'heroicon-o-arrow-down',
            'heroicon-o-arrow-down-circle',
            'heroicon-o-arrow-down-left',
            'heroicon-o-arrow-down-on-square',
            'heroicon-o-arrow-down-right',
            'heroicon-o-arrow-down-tray',
            'heroicon-o-arrow-left',
            'heroicon-o-arrow-left-circle',
            'heroicon-o-arrow-left-on-rectangle',
            'heroicon-o-arrow-long-down',
            'heroicon-o-arrow-long-left',
            'heroicon-o-arrow-long-right',
            'heroicon-o-arrow-long-up',
            'heroicon-o-arrow-path',
            'heroicon-o-arrow-path-rounded-square',
            'heroicon-o-arrow-right',
            'heroicon-o-arrow-right-circle',
            'heroicon-o-arrow-right-on-rectangle',
            'heroicon-o-arrow-small-down',
            'heroicon-o-arrow-small-left',
            'heroicon-o-arrow-small-right',
            'heroicon-o-arrow-small-up',
            'heroicon-o-arrow-top-right-on-square',
            'heroicon-o-arrow-trending-down',
            'heroicon-o-arrow-trending-up',
            'heroicon-o-arrow-up',
            'heroicon-o-arrow-up-circle',
            'heroicon-o-arrow-up-left',
            'heroicon-o-arrow-up-on-square',
            'heroicon-o-arrow-up-right',
            'heroicon-o-arrow-up-tray',
            'heroicon-o-arrow-uturn-down',
            'heroicon-o-arrow-uturn-left',
            'heroicon-o-arrow-uturn-right',
            'heroicon-o-arrow-uturn-up',
            'heroicon-o-arrows-pointing-in',
            'heroicon-o-arrows-pointing-out',
            'heroicon-o-arrows-right-left',
            'heroicon-o-arrows-up-down',
            'heroicon-o-at-symbol',
            'heroicon-o-backspace',
            'heroicon-o-backward',
            'heroicon-o-banknotes',
            'heroicon-o-bars-2',
            'heroicon-o-bars-3',
            'heroicon-o-bars-3-bottom-left',
            'heroicon-o-bars-3-bottom-right',
            'heroicon-o-bars-3-center-left',
            'heroicon-o-bars-4',
            'heroicon-o-bars-arrow-down',
            'heroicon-o-bars-arrow-up',
            'heroicon-o-battery-0',
            'heroicon-o-battery-100',
            'heroicon-o-battery-50',
            'heroicon-o-beaker',
            'heroicon-o-bell',
            'heroicon-o-bell-alert',
            'heroicon-o-bell-slash',
            'heroicon-o-bell-snooze',
            'heroicon-o-bolt',
            'heroicon-o-bolt-slash',
            'heroicon-o-book-open',
            'heroicon-o-bookmark',
            'heroicon-o-bookmark-slash',
            'heroicon-o-bookmark-square',
            'heroicon-o-briefcase',
            'heroicon-o-bug-ant',
            'heroicon-o-building-library',
            'heroicon-o-building-office',
            'heroicon-o-building-office-2',
            'heroicon-o-building-storefront',
            'heroicon-o-cake',
            'heroicon-o-calculator',
            'heroicon-o-calendar',
            'heroicon-o-calendar-days',
            'heroicon-o-camera',
            'heroicon-o-chart-bar',
            'heroicon-o-chart-bar-square',
            'heroicon-o-chart-pie',
            'heroicon-o-chat-bubble-bottom-center',
            'heroicon-o-chat-bubble-bottom-center-text',
            'heroicon-o-chat-bubble-left',
            'heroicon-o-chat-bubble-left-ellipsis',
            'heroicon-o-chat-bubble-left-right',
            'heroicon-o-chat-bubble-oval-left',
            'heroicon-o-chat-bubble-oval-left-ellipsis',
            'heroicon-o-check',
            'heroicon-o-check-badge',
            'heroicon-o-check-circle',
            'heroicon-o-chevron-double-down',
            'heroicon-o-chevron-double-left',
            'heroicon-o-chevron-double-right',
            'heroicon-o-chevron-double-up',
            'heroicon-o-chevron-down',
            'heroicon-o-chevron-left',
            'heroicon-o-chevron-right',
            'heroicon-o-chevron-up',
            'heroicon-o-chevron-up-down',
            'heroicon-o-circle-stack',
            'heroicon-o-clipboard',
            'heroicon-o-clipboard-document',
            'heroicon-o-clipboard-document-check',
            'heroicon-o-clipboard-document-list',
            'heroicon-o-clock',
            'heroicon-o-cloud',
            'heroicon-o-cloud-arrow-down',
            'heroicon-o-cloud-arrow-up',
            'heroicon-o-code-bracket',
            'heroicon-o-code-bracket-square',
            'heroicon-o-cog',
            'heroicon-o-cog-6-tooth',
            'heroicon-o-cog-8-tooth',
            'heroicon-o-command-line',
            'heroicon-o-computer-desktop',
            'heroicon-o-cpu-chip',
            'heroicon-o-credit-card',
            'heroicon-o-cube',
            'heroicon-o-cube-transparent',
            'heroicon-o-currency-bangladeshi',
            'heroicon-o-currency-dollar',
            'heroicon-o-currency-euro',
            'heroicon-o-currency-pound',
            'heroicon-o-currency-rupee',
            'heroicon-o-currency-yen',
            'heroicon-o-cursor-arrow-rays',
            'heroicon-o-cursor-arrow-ripple',
            'heroicon-o-device-phone-mobile',
            'heroicon-o-device-tablet',
            'heroicon-o-document',
            'heroicon-o-document-arrow-down',
            'heroicon-o-document-arrow-up',
            'heroicon-o-document-chart-bar',
            'heroicon-o-document-check',
            'heroicon-o-document-duplicate',
            'heroicon-o-document-magnifying-glass',
            'heroicon-o-document-minus',
            'heroicon-o-document-plus',
            'heroicon-o-document-text',
            'heroicon-o-ellipsis-horizontal',
            'heroicon-o-ellipsis-horizontal-circle',
            'heroicon-o-ellipsis-vertical',
            'heroicon-o-envelope',
            'heroicon-o-envelope-open',
            'heroicon-o-exclamation-circle',
            'heroicon-o-exclamation-triangle',
            'heroicon-o-eye',
            'heroicon-o-eye-dropper',
            'heroicon-o-eye-slash',
            'heroicon-o-face-frown',
            'heroicon-o-face-smile',
            'heroicon-o-film',
            'heroicon-o-finger-print',
            'heroicon-o-fire',
            'heroicon-o-flag',
            'heroicon-o-folder',
            'heroicon-o-folder-arrow-down',
            'heroicon-o-folder-minus',
            'heroicon-o-folder-open',
            'heroicon-o-folder-plus',
            'heroicon-o-forward',
            'heroicon-o-funnel',
            'heroicon-o-gif',
            'heroicon-o-gift',
            'heroicon-o-gift-top',
            'heroicon-o-globe-alt',
            'heroicon-o-globe-americas',
            'heroicon-o-globe-asia-australia',
            'heroicon-o-globe-europe-africa',
            'heroicon-o-hand-raised',
            'heroicon-o-hand-thumb-down',
            'heroicon-o-hand-thumb-up',
            'heroicon-o-hashtag',
            'heroicon-o-heart',
            'heroicon-o-home',
            'heroicon-o-home-modern',
            'heroicon-o-identification',
            'heroicon-o-inbox',
            'heroicon-o-inbox-arrow-down',
            'heroicon-o-inbox-stack',
            'heroicon-o-information-circle',
            'heroicon-o-key',
            'heroicon-o-language',
            'heroicon-o-lifebuoy',
            'heroicon-o-light-bulb',
            'heroicon-o-link',
            'heroicon-o-list-bullet',
            'heroicon-o-lock-closed',
            'heroicon-o-lock-open',
            'heroicon-o-magnifying-glass',
            'heroicon-o-magnifying-glass-circle',
            'heroicon-o-magnifying-glass-minus',
            'heroicon-o-magnifying-glass-plus',
            'heroicon-o-map',
            'heroicon-o-map-pin',
            'heroicon-o-megaphone',
            'heroicon-o-microphone',
            'heroicon-o-minus',
            'heroicon-o-minus-circle',
            'heroicon-o-minus-small',
            'heroicon-o-moon',
            'heroicon-o-musical-note',
            'heroicon-o-newspaper',
            'heroicon-o-no-symbol',
            'heroicon-o-paint-brush',
            'heroicon-o-paper-airplane',
            'heroicon-o-paper-clip',
            'heroicon-o-pause',
            'heroicon-o-pause-circle',
            'heroicon-o-pencil',
            'heroicon-o-pencil-square',
            'heroicon-o-phone',
            'heroicon-o-phone-arrow-down-left',
            'heroicon-o-phone-arrow-up-right',
            'heroicon-o-phone-x-mark',
            'heroicon-o-photo',
            'heroicon-o-play',
            'heroicon-o-play-circle',
            'heroicon-o-play-pause',
            'heroicon-o-plus',
            'heroicon-o-plus-circle',
            'heroicon-o-plus-small',
            'heroicon-o-power',
            'heroicon-o-presentation-chart-bar',
            'heroicon-o-presentation-chart-line',
            'heroicon-o-printer',
            'heroicon-o-puzzle-piece',
            'heroicon-o-qr-code',
            'heroicon-o-question-mark-circle',
            'heroicon-o-queue-list',
            'heroicon-o-radio',
            'heroicon-o-receipt-percent',
            'heroicon-o-receipt-refund',
            'heroicon-o-rectangle-group',
            'heroicon-o-rectangle-stack',
            'heroicon-o-rocket-launch',
            'heroicon-o-rss',
            'heroicon-o-scale',
            'heroicon-o-scissors',
            'heroicon-o-server',
            'heroicon-o-server-stack',
            'heroicon-o-share',
            'heroicon-o-shield-check',
            'heroicon-o-shield-exclamation',
            'heroicon-o-shopping-bag',
            'heroicon-o-shopping-cart',
            'heroicon-o-signal',
            'heroicon-o-signal-slash',
            'heroicon-o-sparkles',
            'heroicon-o-speaker-wave',
            'heroicon-o-speaker-x-mark',
            'heroicon-o-square-2-stack',
            'heroicon-o-square-3-stack-3d',
            'heroicon-o-squares-2x2',
            'heroicon-o-squares-plus',
            'heroicon-o-star',
            'heroicon-o-stop',
            'heroicon-o-stop-circle',
            'heroicon-o-sun',
            'heroicon-o-swatch',
            'heroicon-o-table-cells',
            'heroicon-o-tag',
            'heroicon-o-ticket',
            'heroicon-o-trash',
            'heroicon-o-trophy',
            'heroicon-o-truck',
            'heroicon-o-tv',
            'heroicon-o-user',
            'heroicon-o-user-circle',
            'heroicon-o-user-group',
            'heroicon-o-user-minus',
            'heroicon-o-user-plus',
            'heroicon-o-users',
            'heroicon-o-variable',
            'heroicon-o-video-camera',
            'heroicon-o-video-camera-slash',
            'heroicon-o-view-columns',
            'heroicon-o-viewfinder-circle',
            'heroicon-o-wallet',
            'heroicon-o-wifi',
            'heroicon-o-window',
            'heroicon-o-wrench',
            'heroicon-o-wrench-screwdriver',
            'heroicon-o-x-circle',
            'heroicon-o-x-mark',
        ];
    }

    /**
     * Validate if a given icon name is a valid Heroicon
     */
    public static function isValid(?string $icon): bool
    {
        if (empty($icon)) {
            return false;
        }

        // First check against our known good list
        if (in_array($icon, self::getAvailableIcons(), true)) {
            return true;
        }

        // If not in our list, try to render it (slower but more accurate)
        return self::canRender($icon);
    }

    /**
     * Get all valid Heroicon names
     */
    public static function getValidIcons(): array
    {
        return self::getAvailableIcons();
    }

    /**
     * Validate and sanitize an icon name
     * Returns the icon if valid, null if invalid
     */
    public static function validateAndSanitize(?string $icon): ?string
    {
        if (empty($icon)) {
            return null;
        }

        // Trim whitespace and convert to lowercase for comparison
        $cleanIcon = trim($icon);

        if (self::isValid($cleanIcon)) {
            return $cleanIcon;
        }

        return null;
    }

    /**
     * Get suggestions for similar icon names
     */
    public static function getSuggestions(string $invalidIcon): array
    {
        $suggestions = [];
        $invalidIcon = strtolower($invalidIcon);

        foreach (self::getAvailableIcons() as $validIcon) {
            // Check if the invalid icon is contained in the valid icon name
            if (strpos(strtolower($validIcon), $invalidIcon) !== false) {
                $suggestions[] = $validIcon;
            }
        }

        return array_slice($suggestions, 0, 3); // Return max 3 suggestions
    }
}
