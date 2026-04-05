<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class TesseractOcrService
{
    public function extractText(string $imagePath): string
    {
        if (! is_file($imagePath)) {
            throw new RuntimeException('No se encontró la imagen para OCR.');
        }

        $binary = $this->resolveBinaryPath();
        $language = $this->resolveLanguage();

        $process = new Process([
            $binary,
            $imagePath,
            'stdout',
            '--psm',
            '6',
            '-l',
            $language,
        ]);

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('No se pudo ejecutar Tesseract OCR sobre la imagen generada.');
        }

        return trim($process->getOutput());
    }

    private function resolveBinaryPath(): string
    {
        $candidates = array_filter([
            env('TESSERACT_BINARY'),
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'tesseract',
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate === 'tesseract' || is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('No se encontró el binario de Tesseract OCR.');
    }

    private function resolveLanguage(): string
    {
        $tessdataDirectory = dirname($this->resolveBinaryPath()) . DIRECTORY_SEPARATOR . 'tessdata';
        $spanishData = $tessdataDirectory . DIRECTORY_SEPARATOR . 'spa.traineddata';
        $englishData = $tessdataDirectory . DIRECTORY_SEPARATOR . 'eng.traineddata';

        if (is_file($spanishData) && is_file($englishData)) {
            return 'spa+eng';
        }

        if (is_file($spanishData)) {
            return 'spa';
        }

        return 'eng';
    }
}