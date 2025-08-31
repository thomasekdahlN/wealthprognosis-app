<?php

namespace App\Helpers;

use Filament\Support\RawJs;

class AmountHelper
{
    /**
     * Format amount using Norwegian number formatting
     * - Space as thousand separator
     * - No decimals
     * - Hide zero values
     */
    public static function formatNorwegian(?float $amount, int $decimals = 0): string
    {
        if (is_null($amount) || $amount == 0) {
            return '';
        }

        return number_format((float) $amount, $decimals, ',', ' ');
    }

    /**
     * Format integer using Norwegian number formatting
     * - Space as thousand separator
     * - No decimals for integers like years, counts
     * - Hide zero values
     */
    public static function formatNorwegianInteger(?int $amount): string
    {
        if (is_null($amount) || $amount == 0) {
            return '';
        }

        return number_format((int) $amount, 0, ',', ' ');
    }

    /**
     * Get styling attributes for right-aligned amount fields
     */
    public static function getRightAlignedStyle(): array
    {
        return ['inputmode' => 'decimal', 'style' => 'text-align: right;'];
    }

    /**
     * Get input masking attributes for Norwegian amount formatting without decimals
     * - Space as thousand separator
     * - No decimals
     * - Right-aligned
     */
    public static function getNorwegianAmountMask(): array
    {
        return array_merge(
            self::getRightAlignedStyle(),
            ['x-mask:dynamic' => '$money($input, \'\', \' \')']
        );
    }

    /**
     * Get input masking attributes for Norwegian integer formatting
     * - Space as thousand separator
     * - No decimals
     * - Right-aligned
     */
    public static function getNorwegianIntegerMask(): array
    {
        return array_merge(
            self::getRightAlignedStyle(),
            ['x-mask:dynamic' => '$money($input, \'\', \' \')']
        );
    }

    /**
     * Get Alpine.js mask for Norwegian amount formatting without decimals
     */
    public static function getAlpineAmountMask(): RawJs
    {
        return RawJs::make('$money($input, \'\', \' \')');
    }

    /**
     * Get Alpine.js mask for Norwegian integer formatting
     */
    public static function getAlpineIntegerMask(): RawJs
    {
        return RawJs::make('$money($input, \'\', \' \')');
    }

    /**
     * Parse Norwegian formatted amount back to numeric value
     */
    public static function parseNorwegianAmount(?string $formattedAmount): ?float
    {
        if (is_null($formattedAmount) || trim($formattedAmount) === '') {
            return null;
        }

        // Remove spaces (thousand separators) and replace comma with dot for decimal
        $numericValue = str_replace([' ', ','], ['', '.'], $formattedAmount);

        return is_numeric($numericValue) ? (float) $numericValue : null;
    }

    /**
     * Get Norwegian locale for Filament numeric() method
     */
    public static function getNorwegianLocale(): string
    {
        return 'nb_NO';
    }
}
