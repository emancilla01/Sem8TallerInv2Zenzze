<?php

namespace Tests\Feature;

use App\Services\PdfFirstPageImageConverter;
use App\Services\RegisterCardTextParser;
use App\Services\TesseractOcrService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ArrivalOcrPrefillTest extends TestCase
{
    public function test_it_prefills_day_first_arrival_dates_from_ocr(): void
    {
        Storage::fake('local');

        $imagePath = tempnam(sys_get_temp_dir(), 'ocr-');
        file_put_contents($imagePath, 'fake image');

        $this->mock(PdfFirstPageImageConverter::class, function (MockInterface $mock) use ($imagePath) {
            $mock->shouldReceive('convert')->once()->andReturn($imagePath);
        });

        $this->mock(TesseractOcrService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractText')->once()->andReturn('raw ocr text');
        });

        $this->mock(RegisterCardTextParser::class, function (MockInterface $mock) {
            $mock->shouldReceive('parse')->once()->with('raw ocr text')->andReturn([
                'nombre' => 'Gustavo',
                'apellido' => 'Velazquez',
                'fecha_llegada' => '02-04-26',
            ]);
        });

        $response = $this->post(route('arrivals.prefill-ocr'), [
            'register_card_pdf' => UploadedFile::fake()->create('register-card.pdf', 32, 'application/pdf'),
        ]);

        $response->assertRedirect(route('arrivals.create'));
        $response->assertSessionHas('_old_input.nombre', 'Gustavo');
        $response->assertSessionHas('_old_input.apellido', 'Velazquez');
        $response->assertSessionHas('_old_input.fecha_llegada', '2026-04-02');

        $ocrDocument = session('arrivals.ocr_document');

        $this->assertIsArray($ocrDocument);
        $this->assertSame('register-card.pdf', $ocrDocument['original_name']);
        $this->assertTrue(Storage::disk('local')->exists($ocrDocument['path']));
    }
}