<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Expediente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArrivalStoreAndDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_back_to_base_de_datos_after_deleting_from_that_page(): void
    {
        $expediente = Expediente::query()->create([
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'fecha_llegada' => '2026-04-06',
            'identificacion_path' => null,
        ]);

        $response = $this->delete(route('expedientes.destroy', $expediente->id), [
            'redirect_to' => '/base-de-datos?search=ana&sort=apellido',
        ]);

        $response->assertRedirect('/base-de-datos?search=ana&sort=apellido');
        $this->assertDatabaseMissing('expedientes', ['id' => $expediente->id]);
    }

    public function test_it_allows_saving_a_manual_record_without_a_register_card(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $temporaryPath = 'private/ocr-tests/register-cards/existing-register-card.pdf';
        Storage::disk('local')->put($temporaryPath, 'fake pdf contents');

        $response = $this->withSession([
            'arrivals.ocr_document' => [
                'path' => $temporaryPath,
                'original_name' => 'existing-register-card.pdf',
            ],
        ])->post(route('arrivals.store'), [
            'nombre' => 'Lucia',
            'apellido' => 'Martinez',
            'fecha_llegada' => '2026-04-06',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('expedientes', [
            'nombre' => 'Lucia',
            'apellido' => 'Martinez',
        ]);
        $this->assertSame(0, Documento::query()->count());
        $this->assertTrue(Storage::disk('local')->exists($temporaryPath));
    }
}