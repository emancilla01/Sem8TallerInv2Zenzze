<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpedienteFirmaController extends Controller
{
    public function store(Request $request, Expediente $expediente)
    {
        $request->validate([
            'firma_base64' => 'required|string',
        ]);

        $b64 = $request->input('firma_base64');
        if (preg_match('/^data:image\/png;base64,/', $b64)) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }

        $sigData = base64_decode($b64);
        if ($sigData === false) {
            return response()->json(['error' => 'Firma inválida'], 422);
        }

        $documento = $expediente->documentos()->where('path', 'like', '%.pdf')->first()
            ?? $expediente->documentos()->first();

        if (! $documento) {
            return response()->json(['error' => 'No hay documentos para firmar'], 422);
        }

        $originalFull = storage_path('app/public/' . $documento->path);
        if (! file_exists($originalFull)) {
            return response()->json(['error' => 'Archivo original no encontrado'], 422);
        }

        // Write signature PNG to a temp file for FPDI
        $tmpSig = tempnam(sys_get_temp_dir(), 'firma_') . '.png';
        file_put_contents($tmpSig, $sigData);

        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($originalFull);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                if ($i === $pageCount) {
                    $imgWidthMm = 99;
                    $x = 70;
                    $y = $size['height'] - 25;
                    $pdf->Image($tmpSig, $x, $y, $imgWidthMm);
                }
            }

            // Overwrite the original PDF
            $pdf->Output('F', $originalFull);

            $documento->signed_at = now();
            $documento->save();

        } catch (\Exception $e) {
            @unlink($tmpSig);
            return response()->json(['error' => 'Error al firmar PDF: ' . $e->getMessage()], 500);
        }

        @unlink($tmpSig);

        return response()->json(['success' => true]);
    }
}
