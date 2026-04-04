<?php

namespace App\Http\Controllers;

use App\Services\PdfTextExtractor;
use App\Services\RegisterCardTextParser;
use Illuminate\Http\Request;

class PdfExtractionController extends Controller
{
    public function create()
    {
        return view('tools.pdf-extraction', [
            'rawText' => null,
            'parsedFields' => null,
            'parserDebug' => null,
        ]);
    }

    public function store(Request $request, PdfTextExtractor $pdfTextExtractor, RegisterCardTextParser $registerCardTextParser)
    {
        $validated = $request->validate(
            [
                'register_card_pdf' => ['required', 'file', 'mimes:pdf'],
            ],
            [
                'register_card_pdf.required' => 'Debes seleccionar un PDF para extraer texto.',
                'register_card_pdf.file' => 'El archivo debe ser válido.',
                'register_card_pdf.mimes' => 'El archivo debe estar en formato PDF.',
            ]
        );

        try {
            $rawText = $pdfTextExtractor->extractFromPath($validated['register_card_pdf']->getRealPath());
        } catch (\Throwable $exception) {
            return back()
                ->withErrors([
                    'register_card_pdf' => 'No se pudo extraer texto del PDF seleccionado.',
                ]);
        }

        $parsedFields = $registerCardTextParser->parse($rawText);
        $parserDebug = $registerCardTextParser->debug($rawText);

        return view('tools.pdf-extraction', [
            'rawText' => $rawText,
            'parsedFields' => $parsedFields,
            'parserDebug' => $parserDebug,
        ]);
    }
}