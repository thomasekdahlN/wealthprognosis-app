<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelFormatting
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function applyCommonAssetSheetFormatting(Worksheet $sheet, array $meta): void
    {
        $startRow = 6;
        $endRow = max($sheet->getHighestRow(), $startRow);

        // Header: bold + gray (extended to AR for new columns)
        $sheet->getStyle('A5:AR5')->getFont()->setBold(true);
        $sheet->getStyle('A5:AR5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('CCCCCC');

        // Amounts: Norwegian spacing + red negatives, right-aligned (extended to AR)
        $sheet->getStyle("B{$startRow}:AR{$endRow}")->getNumberFormat()->setFormatCode('# ##0;[Red]-# ##0');
        $sheet->getStyle("B{$startRow}:AR{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Percent columns (updated after adding AI capRate column)
        // D=Income%, F=Expense%, H=Tax%, J=Interest%, O=Deductable%, Q=Asset%, V=Fortune%, X=Fortune%, Z=Property%, AD=Realization%, AF=Shield%, AG=GrossYield%, AH=NetYield%, AI=CapRate%, AL=Mortgage%, AQ=SaveRate%
        foreach (['D', 'F', 'H', 'J', 'O', 'Q', 'V', 'X', 'AD', 'AF', 'AG', 'AH', 'AI', 'AL', 'AQ'] as $col) {
            $sheet->getStyle("{$col}{$startRow}:{$col}{$endRow}")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%');
        }
        $sheet->getStyle("Z{$startRow}:Z{$endRow}")->getNumberFormat()->setFormatCode('0.00%;[Red]-0.00%');

        // Color bands (vertical)
        $sheet->getStyle("C{$startRow}:C{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('90EE90');
        $sheet->getStyle("E{$startRow}:E{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCB');
        $sheet->getStyle("P{$startRow}:P{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('ADD8E6');
        $sheet->getStyle("W{$startRow}:W{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCB');
        $sheet->getStyle("Y{$startRow}:Y{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCB');
        $sheet->getStyle("AC{$startRow}:AC{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCB');
        $sheet->getStyle("AJ{$startRow}:AJ{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('ADD8E6');

        // Horizontal highlights (extended to AR for new columns)
        $prevYear = (int) ($meta['prevYear'] ?? (int) date('Y') - 1);
        $rows = [
            (int) ($meta['thisYear'] ?? date('Y')) => '32CD32',
            (int) ($meta['prognoseYear'] ?? 0) => '7FFFD4',
            (int) ($meta['pensionOfficialYear'] ?? 0) => 'CCCCCC',
            (int) ($meta['pensionWishYear'] ?? 0) => 'FFA500',
            (int) ($meta['deathYear'] ?? 0) => 'FFCCCB',
        ];
        $offset = 6;
        foreach ($rows as $year => $color) {
            if ($year <= 0) {
                continue;
            }
            $row = $year - $prevYear + $offset;
            if ($row >= $startRow && $row <= $endRow) {
                $sheet->getStyle("A{$row}:AR{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
            }
        }
    }

    public static function applyStatisticsSheetFormatting(Worksheet $sheet): void
    {
        // Header row for statistics
        $sheet->getStyle('A5:Z5')->getFont()->setBold(true);
        $sheet->getStyle('A5:Z5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('CCCCCC');

        // Percent formatting and right align
        $highestRow = max($sheet->getHighestRow(), 6);
        $sheet->getStyle("B6:Z{$highestRow}")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%');
        $sheet->getStyle("B6:Z{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Autosize columns A..Z
        for ($col = 'A'; $col <= 'Z'; $col++) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
