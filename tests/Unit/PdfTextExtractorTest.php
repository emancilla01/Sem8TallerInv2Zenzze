<?php

namespace Tests\Unit;

use App\Services\PdfTextExtractor;
use PHPUnit\Framework\TestCase;

class PdfTextExtractorTest extends TestCase
{
    public function test_it_decodes_text_with_a_tounicode_character_map(): void
    {
        $pdf = <<<'PDF'
%PDF-1.4
1 0 obj
<< /Length 107 >>
stream
1 beginbfchar
<01> <0041>
<02> <0070>
<03> <0065>
<04> <006C>
<05> <006C>
<06> <0069>
<07> <0064>
<08> <006F>
<09> <0020>
<0A> <004E>
<0B> <006F>
<0C> <006D>
<0D> <0062>
<0E> <0072>
<0F> <0065>
endbfchar
endstream
endobj
2 0 obj
<< /Length 21 >>
stream
BT
<0102030405060708090A0B0C0D0E0F>
ET
endstream
endobj
trailer
<< /Root 1 0 R >>
%%EOF
PDF;

        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, $pdf);

        try {
            $text = (new PdfTextExtractor())->extractFromPath($path);
        } finally {
            if ($path !== false && file_exists($path)) {
                unlink($path);
            }
        }

        $this->assertStringContainsString('Apellido Nombre', $text);
    }

    public function test_it_extracts_acroform_field_values(): void
    {
        $pdf = <<<'PDF'
%PDF-1.4
1 0 obj
<< /T (Apellido) /V (Velazquez) >>
endobj
2 0 obj
<< /T (Nombre) /V (Gustavo) >>
endobj
3 0 obj
<< /TU (Llegada) /V (02-04-26) >>
endobj
trailer
<< /Root 1 0 R >>
%%EOF
PDF;

        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, $pdf);

        try {
            $text = (new PdfTextExtractor())->extractFromPath($path);
        } finally {
            if ($path !== false && file_exists($path)) {
                unlink($path);
            }
        }

        $this->assertStringContainsString('Apellido: Velazquez', $text);
        $this->assertStringContainsString('Nombre: Gustavo', $text);
        $this->assertStringContainsString('Llegada: 02-04-26', $text);
    }
}