<?php

namespace App\Http\Controllers;

use App\Services\PdfFirstPageImageConverter;
use App\Services\RegisterCardTextParser;
use App\Services\TesseractOcrService;
use Illuminate\Http\Request;
use Throwable;

class RegisterCardOcrController extends Controller
{
    public function create()
    {
        return view('tools.register-card-ocr', [
            'imagePreview' => null,
            'rawOcrText' => null,
            'parsedFields' => null,
        ]);
    }

    public function store(
        Request $request,
        PdfFirstPageImageConverter $pdfFirstPageImageConverter,
        TesseractOcrService $tesseractOcrService,
        RegisterCardTextParser $registerCardTextParser,
    ) {
        $validated = $request->validate(
            [
                'register_card_pdf' => ['required', 'file', 'mimes:pdf'],
            ],
            [
                'register_card_pdf.required' => 'Debes seleccionar un PDF para procesar OCR.',
                'register_card_pdf.file' => 'El archivo debe ser válido.',
                'register_card_pdf.mimes' => 'El archivo debe estar en formato PDF.',
            ]
        );

        $imagePath = null;

        try {
            $imagePath = $pdfFirstPageImageConverter->convert($validated['register_card_pdf']->getRealPath());
            $rawOcrText = $tesseractOcrService->extractText($imagePath);
            $parsedFields = $registerCardTextParser->parse($rawOcrText);
            $imagePreview = 'data:image/png;base64,' . base64_encode((string) file_get_contents($imagePath));
        } catch (Throwable $exception) {
            return back()->withErrors([
                'register_card_pdf' => $exception->getMessage(),
            ]);
        } finally {
            if ($imagePath !== null && is_file($imagePath)) {
                @unlink($imagePath);
            }
        }

        return view('tools.register-card-ocr', [
            'imagePreview' => $imagePreview,
            'rawOcrText' => $rawOcrText,
            'parsedFields' => $parsedFields,
        ]);
    }
}