<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class PdfFirstPageImageConverter
{
    public function convert(string $pdfPath): string
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('No se encontró el PDF para convertir.');
        }

        $binary = $this->resolveBinaryPath();
        $workingDirectory = storage_path('app/private/ocr-tests');

        if (! is_dir($workingDirectory) && ! mkdir($workingDirectory, 0777, true) && ! is_dir($workingDirectory)) {
            throw new RuntimeException('No se pudo crear el directorio temporal para OCR.');
        }

        $prefix = $workingDirectory . DIRECTORY_SEPARATOR . 'ocr-page-' . bin2hex(random_bytes(8));
        $imagePath = $prefix . '.png';

        $process = new Process([
            $binary,
            '-png',
            '-f',
            '1',
            '-singlefile',
            $pdfPath,
            $prefix,
        ]);

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($imagePath)) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());

            throw new RuntimeException(
                $errorOutput !== ''
                    ? 'No se pudo convertir la primera página del PDF a imagen. ' . $errorOutput
                    : 'No se pudo convertir la primera página del PDF a imagen.'
            );
        }

        return $imagePath;
    }

    private function resolveBinaryPath(): string
    {
        $installedPoppler = glob((string) env('LOCALAPPDATA', sys_get_temp_dir()) . '\\Microsoft\\WinGet\\Packages\\oschwartz10612.Poppler_*\\poppler-*\\Library\\bin\\pdftoppm.exe');

        $candidates = array_filter([
            env('PDFTOPPM_BINARY'),
            ...(is_array($installedPoppler) ? $installedPoppler : []),
            'pdftoppm',
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate === 'pdftoppm' || is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('No se encontró el binario de pdftoppm para convertir el PDF.');
    }
}