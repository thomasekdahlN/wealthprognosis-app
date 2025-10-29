<?php

namespace App\Helpers;

use Filament\Support\RawJs;
use NumberFormatter;

class AmountHelper
{
    /**
     * Get the Norwegian locale string for NumberFormatter
     */
    public static function getNorwegianLocale(): string
    {
        return 'nb_NO';
    }

    /**
     * Format amount using Norwegian number formatting via NumberFormatter
     * - Uses PHP's NumberFormatter (same as Filament's money() method)
     * - Space as thousand separator
     * - Comma as decimal separator
     * - Configurable decimal places
     * - Hide zero values
     *
     * @param  float|null  $amount  The amount to format
     * @param  int  $decimals  Number of decimal places (default: 0)
     * @return string Formatted amount or empty string for null/zero
     */
    public static function formatNorwegian(?float $amount, int $decimals = 0): string
    {
        if (is_null($amount) || $amount == 0) {
            return '';
        }

        $formatter = new NumberFormatter(self::getNorwegianLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

        return $formatter->format($amount);
    }

    /**
     * Format integer using Norwegian number formatting via NumberFormatter
     * - Uses PHP's NumberFormatter (same as Filament's money() method)
     * - Space as thousand separator
     * - No decimals for integers like years, counts
     * - Hide zero values
     *
     * @param  int|null  $amount  The integer to format
     * @return string Formatted integer or empty string for null/zero
     */
    public static function formatNorwegianInteger(?int $amount): string
    {
        if (is_null($amount) || $amount == 0) {
            return '';
        }

        $formatter = new NumberFormatter(self::getNorwegianLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);

        return $formatter->format($amount);
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
}
