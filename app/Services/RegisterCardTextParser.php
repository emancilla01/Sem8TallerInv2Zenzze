<?php

namespace App\Services;

class RegisterCardTextParser
{
    private const LABEL_ALIASES = [
        'hab' => ['Hab', 'Hab.'],
        'salida' => ['Salida'],
        'adultos' => ['Adultos'],
        'llegada' => ['Llegada'],
        'conf' => ['Conf.#', 'Conf#', 'Conf. #'],
        'tipo' => ['Tipo'],
        'noches' => ['Noches'],
        'iniciales_huesped' => ['InicialesHupsped', 'InicialesHuesped', 'InicialesHuesped.', 'InicialesHuesped:'],
        'apellido' => ['Apellido'],
        'nombre' => ['Nombre'],
        'empresa' => ['Empresa'],
        'direccion' => ['Direccion', 'Direcciyin', 'Direccion1', 'Direcciyin1'],
        'direccion2' => ['Direccion2', 'Direcciyin2'],
        'ciudad' => ['Ciudad'],
        'estado' => ['Estado'],
        'codigo_postal' => ['Cyd.Postal', 'Cod.Postal', 'Codigo Postal'],
    ];

    public function parse(string $rawText): array
    {
        $normalizedText = $this->normalizeText($rawText);

        $parsedFields = [
            'apellido' => $this->extractValue($normalizedText, 'Apellido', ['Nombre', 'Llegada']),
            'nombre' => $this->extractValue($normalizedText, 'Nombre', ['Llegada']),
            'fecha_llegada' => $this->extractArrivalDate($normalizedText),
        ];

        $fallbackFields = $this->extractFromSequentialLabelBlock($normalizedText);

        return [
            'apellido' => $parsedFields['apellido'] ?? $fallbackFields['apellido'],
            'nombre' => $parsedFields['nombre'] ?? $fallbackFields['nombre'],
            'fecha_llegada' => $parsedFields['fecha_llegada'] ?? $fallbackFields['fecha_llegada'],
        ];
    }

    public function debug(string $rawText): array
    {
        $normalizedText = $this->normalizeText($rawText);
        $preparedLines = $this->prepareLines($normalizedText);
        $sequentialMatch = $this->findSequentialLabelBlockMatch($preparedLines);

        return [
            'normalized_text' => $normalizedText,
            'prepared_lines' => $preparedLines,
            'sequential_match' => $sequentialMatch,
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = $this->sanitizeEncoding($text);
        $text = str_replace(["\r\n", "\r", "\t", "\0", "\x0B", "\f", "\xC2\xA0"], ["\n", "\n", ' ', ' ', ' ', ' ', ' '], $text);
        $text = preg_replace('/Apellido\s*:\s*Nombre\s*:/i', "Apellido:\nNombre:", $text) ?? $text;
        $text = preg_replace('/Conf\s*\.?\s*#\s*:\s*Tipo\s*:/i', "Conf.#:\nTipo:", $text) ?? $text;
        $text = preg_replace("/[ ]{2,}/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{2,}/", "\n", $text) ?? $text;

        return trim($text);
    }

    private function sanitizeEncoding(string $text): string
    {
        if (function_exists('mb_check_encoding') && mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        if (function_exists('iconv')) {
            $sanitized = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

            if (is_string($sanitized)) {
                return $sanitized;
            }
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        return $text;
    }

    private function extractFromSequentialLabelBlock(string $text): array
    {
        $match = $this->findSequentialLabelBlockMatch($this->prepareLines($text));

        if ($match !== null) {
            return [
                'apellido' => $match['mapped_values']['apellido'] ?? null,
                'nombre' => $match['mapped_values']['nombre'] ?? null,
                'fecha_llegada' => $match['mapped_values']['llegada'] ?? null,
            ];
        }

        return [
            'apellido' => null,
            'nombre' => null,
            'fecha_llegada' => null,
        ];
    }

    private function findSequentialLabelBlockMatch(array $lines): ?array
    {
        for ($index = 0; $index < count($lines); $index++) {
            $labels = [];
            $cursor = $index;

            while ($cursor < count($lines)) {
                $labelsFromLine = $this->extractLabelsFromLine($lines[$cursor]);

                if ($labelsFromLine === []) {
                    break;
                }

                array_push($labels, ...$labelsFromLine);
                $cursor++;
            }

            if (count($labels) < 5) {
                continue;
            }

            $values = [];

            while ($cursor < count($lines) && count($values) < count($labels)) {
                $line = $lines[$cursor];
                $cursor++;

                if ($line === '' || $this->extractLabelsFromLine($line) !== []) {
                    continue;
                }

                $values[] = $line;
            }

            if ($values === []) {
                continue;
            }

            $mappedValues = [];

            foreach ($labels as $position => $label) {
                if (! array_key_exists($position, $values)) {
                    break;
                }

                $mappedValues[$label] = $this->cleanValue($values[$position]);
            }

            return [
                'labels' => $labels,
                'values' => $values,
                'mapped_values' => $mappedValues,
                'start_index' => $index,
            ];
        }

        return null;
    }

    private function prepareLines(string $text): array
    {
        $rawLines = preg_split('/\r?\n/', $text) ?: [];
        $lines = [];

        foreach ($rawLines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            if ($line === ':' && $lines !== []) {
                $lines[array_key_last($lines)] = rtrim($lines[array_key_last($lines)], ':') . ':';
                continue;
            }

            $lines[] = $line;
        }

        return array_values($lines);
    }

    private function extractLabelsFromLine(string $line): array
    {
        $segments = preg_split('/\s*:\s*/', trim($line, " \t\n\r\0\x0B:"), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($segments === []) {
            return [];
        }

        $labels = [];

        foreach ($segments as $segment) {
            $canonicalLabel = $this->canonicalizeLabel($segment);

            if ($canonicalLabel === null) {
                return [];
            }

            $labels[] = $canonicalLabel;
        }

        return $labels;
    }

    private function canonicalizeLabel(string $line): ?string
    {
        $normalizedLine = mb_strtolower(trim($line, " \t\n\r\0\x0B:"));
        $normalizedLine = preg_replace('/\s+/', '', $normalizedLine) ?? $normalizedLine;
        $normalizedLine = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', '#', '.'], ['a', 'e', 'i', 'o', 'u', 'u', '', ''], $normalizedLine);

        foreach (self::LABEL_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = mb_strtolower($alias);
                $normalizedAlias = preg_replace('/\s+/', '', $normalizedAlias) ?? $normalizedAlias;
                $normalizedAlias = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', '#', '.'], ['a', 'e', 'i', 'o', 'u', 'u', '', ''], $normalizedAlias);

                if ($normalizedLine === $normalizedAlias) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    private function containsKnownLabel(string $value): bool
    {
        foreach (self::LABEL_ALIASES as $aliases) {
            foreach ($aliases as $alias) {
                $pattern = '/\b' . preg_quote($alias, '/') . '\b\s*:/i';

                if (preg_match($pattern, $value) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    private function extractValue(string $text, string $label, array $nextLabels = []): ?string
    {
        $boundaries = ['\n+'];

        if ($nextLabels !== []) {
            $escapedLabels = array_map(static fn (string $nextLabel): string => preg_quote($nextLabel, '/'), $nextLabels);
            $boundaries[] = '\\b(?:' . implode('|', $escapedLabels) . ')\\b';
        }

        $boundaryPattern = '(?:' . implode('|', $boundaries) . '|$)';
        $labelPattern = preg_quote($label, '/');
        $patterns = [
            '/\\b' . $labelPattern . '\\b\s*[:\-]?\s*(.*?)\s*(?=' . $boundaryPattern . ')/i',
            '/\\b' . $labelPattern . '\\b\s*[:\-]?\s*(.*?)\s*(?=' . str_replace('\\n+', '', $boundaryPattern) . ')/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) !== 1) {
                continue;
            }

            $value = $this->cleanValue($matches[1] ?? '');

            if ($value !== null) {
                return $value;
            }
        }

        $flattenedText = preg_replace('/\s+/', ' ', $text) ?? $text;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $flattenedText, $matches) !== 1) {
                continue;
            }

            $value = $this->cleanValue($matches[1] ?? '');

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function extractArrivalDate(string $text): ?string
    {
        $position = mb_stripos($text, 'Llegada');

        if ($position !== false) {
            $segment = mb_substr($text, $position, 80);

            if (preg_match('/\b\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}\b/', $segment, $matches) === 1) {
                return $matches[0];
            }
        }

        $value = $this->extractValue($text, 'Llegada');

        if ($value === null) {
            return null;
        }

        if (preg_match('/\b\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}\b/', $value, $matches) === 1) {
            return $matches[0];
        }

        return $value;
    }

    private function cleanValue(string $value): ?string
    {
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B:-");

        if ($value === '') {
            return null;
        }

        if ($this->extractLabelsFromLine($value) !== [] || $this->containsKnownLabel($value)) {
            return null;
        }

        return $value;
    }
}