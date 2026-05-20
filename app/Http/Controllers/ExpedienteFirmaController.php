<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpedienteFirmaController extends Controller
{
    public function show(Expediente $expediente)
    {
        $documento = $expediente->documentos()->where('path', 'like', '%.pdf')->first() ?? $expediente->documentos()->first();

        return view('expedientes.firma', compact('expediente', 'documento'));
    }

    public function store(Request $request, Expediente $expediente)
    {
        $request->validate([
            'firma_base64' => 'required|string',
        ]);

        $b64 = $request->input('firma_base64');
        if (preg_match('/^data:image\/png;base64,/', $b64)) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }

        $data = base64_decode($b64);
        if ($data === false) {
            return back()->with('error', 'Firma inválida');
        }

        $filename = 'firmas/' . now()->format('Ymd_His') . '_' . $expediente->id . '_' . Str::random(6) . '.png';
        Storage::disk('public')->put($filename, $data);

        // Find first PDF documento
        $documento = $expediente->documentos()->where('path', 'like', '%.pdf')->first() ?? $expediente->documentos()->first();
        if (! $documento) {
            return back()->with('error', 'No hay documentos para firmar');
        }

        $originalRel = $documento->path;
        $originalFull = storage_path('app/public/' . $originalRel);
        if (! file_exists($originalFull)) {
            return back()->with('error', 'Archivo original no encontrado: ' . $originalRel);
        }

        $signedDir = 'documentos_firmados';
        $signedRel = $signedDir . '/' . now()->format('Ymd_His') . '_' . $documento->id . '.pdf';
        $signedFull = storage_path('app/public/' . $signedRel);

        try {
            if (! class_exists(\setasign\Fpdi\Fpdi::class)) {
                return back()->with('error', 'FPDI library not installed. Run: composer require setasign/fpdi setasign/fpdf');
            }

            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($originalFull);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                if ($i === $pageCount) {
                    $imgFull = storage_path('app/public/' . $filename);
                    $imgWidthMm = 90;  // width of signature image in mm (≈70% of 45)
                    $x = 70;           // mm from left
                    $y = $size['height'] - 31; // moved up ~40%
                    $pdf->Image($imgFull, $x, $y, $imgWidthMm);
                }
            }

            if (! file_exists(dirname($signedFull))) {
                mkdir(dirname($signedFull), 0755, true);
            }

            $pdf->Output('F', $signedFull);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al firmar PDF: ' . $e->getMessage());
        }

        return redirect()->route('expedientes.firma.show', $expediente->id)
            ->with('success', 'Firma guardada y PDF firmado.')
            ->with('signed_path', $signedRel);
    }
}
