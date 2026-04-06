<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Expediente;
use App\Services\PdfFirstPageImageConverter;
use App\Services\RegisterCardTextParser;
use App\Services\TesseractOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class BatchArrivalTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_processes_register_cards_for_batch_review_without_creating_records(): void
    {
        Storage::fake('local');

        $firstImagePath = tempnam(sys_get_temp_dir(), 'ocr-batch-');
        $secondImagePath = tempnam(sys_get_temp_dir(), 'ocr-batch-');
        file_put_contents($firstImagePath, 'fake image 1');
        file_put_contents($secondImagePath, 'fake image 2');

        $this->mock(PdfFirstPageImageConverter::class, function (MockInterface $mock) use ($firstImagePath, $secondImagePath) {
            $mock->shouldReceive('convert')->twice()->andReturn($firstImagePath, $secondImagePath);
        });

        $this->mock(TesseractOcrService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractText')->twice()->andReturn('raw text 1', 'raw text 2');
        });

        $this->mock(RegisterCardTextParser::class, function (MockInterface $mock) {
            $mock->shouldReceive('parse')->once()->with('raw text 1')->andReturn([
                'nombre' => 'Ana',
                'apellido' => 'Lopez',
                'fecha_llegada' => '05-04-26',
            ]);

            $mock->shouldReceive('parse')->once()->with('raw text 2')->andReturn([
                'nombre' => 'Bruno',
                'apellido' => '',
                'fecha_llegada' => '06-04-26',
            ]);
        });

        $response = $this->post(route('arrivals.batch.process'), [
            'register_card_pdfs' => [
                UploadedFile::fake()->create('ana-lopez.pdf', 32, 'application/pdf'),
                UploadedFile::fake()->create('bruno.pdf', 32, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('arrivals.batch.index'));
        $this->assertDatabaseCount('expedientes', 0);

        $batchRows = session('arrivals.batch_rows');

        $this->assertIsArray($batchRows);
        $this->assertCount(2, $batchRows);
        $this->assertSame('ana-lopez.pdf', $batchRows[0]['original_name']);
        $this->assertSame('ready', $batchRows[0]['status']);
        $this->assertSame('2026-04-05', $batchRows[0]['fecha_llegada']);
        $this->assertTrue(Storage::disk('local')->exists($batchRows[0]['temp_path']));
        $this->assertSame('bruno.pdf', $batchRows[1]['original_name']);
        $this->assertSame('incomplete', $batchRows[1]['status']);
        $this->assertSame('Bruno', $batchRows[1]['nombre']);
    }

    public function test_it_saves_selected_ready_batch_rows_and_stores_the_pdf_as_documento(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $temporaryPath = 'private/ocr-tests/register-cards/batch/row-1-register-card.pdf';
        Storage::disk('local')->put($temporaryPath, 'fake pdf contents');

        $response = $this->withSession([
            'arrivals.batch_rows' => [[
                'id' => 'row-1',
                'temp_path' => $temporaryPath,
                'original_name' => 'register-card-row-1.pdf',
                'nombre' => 'Lucia',
                'apellido' => 'Martinez',
                'fecha_llegada' => '2026-04-05',
                'error_message' => null,
                'status' => 'ready',
            ]],
        ])->post(route('arrivals.batch.store-selected'), [
            'selected_rows' => ['row-1'],
        ]);

        $response->assertRedirect(route('arrivals.batch.index'));
        $this->assertSame(1, Expediente::query()->count());

        $expediente = Expediente::query()->first();

        $this->assertNotNull($expediente);
        $this->assertSame('Lucia', $expediente->nombre);
        $this->assertSame('Martinez', $expediente->apellido);
        $this->assertSame('2026-04-05', $expediente->fecha_llegada?->format('Y-m-d'));

        $documento = Documento::query()->first();

        $this->assertNotNull($documento);
        $this->assertSame('register-card-row-1.pdf', $documento->original_name);
        $this->assertTrue(Storage::disk('public')->exists($documento->path));
        $this->assertFalse(Storage::disk('local')->exists($temporaryPath));
        $this->assertNull(session('arrivals.batch_rows'));
    }
}