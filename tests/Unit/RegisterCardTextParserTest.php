<?php

namespace Tests\Unit;

use App\Services\RegisterCardTextParser;
use PHPUnit\Framework\TestCase;

class RegisterCardTextParserTest extends TestCase
{
    public function test_it_extracts_the_required_fields_from_noisy_text(): void
    {
        $parser = new RegisterCardTextParser();

        $rawText = "Registro Opera\n\nApellido   :   Velazquez\nNombre\n Gustavo   \nObservacion extra\nLlegada : 02-04-26\n";

        $parsed = $parser->parse($rawText);

        $this->assertSame('Velazquez', $parsed['apellido']);
        $this->assertSame('Gustavo', $parsed['nombre']);
        $this->assertSame('02-04-26', $parsed['fecha_llegada']);
    }

    public function test_it_extracts_fields_when_labels_and_values_are_split_into_blocks(): void
    {
        $parser = new RegisterCardTextParser();

        $rawText = "Hab.:\nSalida:\nAdultos:\nLlegada:\nConf.#:Tipo:\nNoches:\nInicialesHupsped:\nApellido:Nombre:\nEmpresa:\nDirecciyin\n:\nDirecciyin2\n:\nCiudad:\nEstado:\nCyd.Postal\n:\n101\n03-04-26\n2\n02-04-26\n556677\nSTD\n1\nGV\nVelazquez\nGustavo\nAcme\nPrimera\nSegunda\nCDMX\nCDMX\n01234";

        $parsed = $parser->parse($rawText);

        $this->assertSame('Velazquez', $parsed['apellido']);
        $this->assertSame('Gustavo', $parsed['nombre']);
        $this->assertSame('02-04-26', $parsed['fecha_llegada']);
    }

    public function test_it_handles_invalid_utf8_bytes_in_extracted_text(): void
    {
        $parser = new RegisterCardTextParser();

        $rawText = "Hab.:\nSalida:\nAdultos:\nLlegada:\nConf.#:Tipo:\nNoches:\nInicialesHupsped:\nApellido:Nombre:\nEmpresa:\nDirecciyin\xC3\n:\nDirecciyin2\n:\nCiudad:\nEstado:\nCyd.Postal\n:\n101\n03-04-26\n2\n02-04-26\n556677\nSTD\n1\nGV\nVelazquez\nGustavo\nAcme\nPrimera\nSegunda\nCDMX\nCDMX\n01234";

        $parsed = $parser->parse($rawText);
        $debug = $parser->debug($rawText);

        $this->assertNotEmpty($debug['prepared_lines']);
        $this->assertSame('Velazquez', $parsed['apellido']);
        $this->assertSame('Gustavo', $parsed['nombre']);
        $this->assertSame('02-04-26', $parsed['fecha_llegada']);
    }
}