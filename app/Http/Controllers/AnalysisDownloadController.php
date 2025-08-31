<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalysisDownloadController extends Controller
{
    public function download(Request $request, string $file): BinaryFileResponse
    {
        $filePath = storage_path('app/exports/'.$file);

        // Check if file exists
        if (! file_exists($filePath)) {
            abort(404, 'Analysis file not found');
        }

        // Security check - ensure file is in exports directory
        $realPath = realpath($filePath);
        $exportsPath = realpath(storage_path('app/exports'));

        if (! $realPath || ! str_starts_with($realPath, $exportsPath)) {
            abort(403, 'Access denied');
        }

        // Check file extension for security
        $allowedExtensions = ['xlsx', 'xls'];
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($fileExtension, $allowedExtensions)) {
            abort(403, 'File type not allowed');
        }

        // Set appropriate headers for Excel download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.basename($filePath).'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        // Return file download and delete after sending
        return response()->download($filePath, basename($filePath), $headers)->deleteFileAfterSend(true);
    }

    public function cleanup(): void
    {
        // Clean up old export files (older than 1 hour)
        $exportsPath = storage_path('app/exports');

        if (! is_dir($exportsPath)) {
            return;
        }

        $files = glob($exportsPath.'/*.xlsx');
        $cutoffTime = time() - 3600; // 1 hour ago

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}
