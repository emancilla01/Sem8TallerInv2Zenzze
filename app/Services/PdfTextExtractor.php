<?php

namespace App\Services;

use RuntimeException;

class PdfTextExtractor
{
    public function extractFromPath(string $path): string
    {
        $pdfContent = @file_get_contents($path);

        if ($pdfContent === false) {
            throw new RuntimeException('No se pudo leer el archivo PDF.');
        }

        $decodedStreams = [];

        if (preg_match_all('/<<(.*?)>>\s*stream\r?\n(.*?)\r?\nendstream/s', $pdfContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $decodedStream = $this->decodeStream($match[2], $match[1]);

                if ($decodedStream === null) {
                    continue;
                }

                $decodedStreams[] = $decodedStream;
            }
        }

        $characterMaps = $this->extractCharacterMaps($decodedStreams);
        $textChunks = [];

        foreach ($decodedStreams as $decodedStream) {
            $text = $this->extractTextFromStream($decodedStream, $characterMaps);

            if ($text !== '') {
                $textChunks[] = $text;
            }
        }

        $formFieldLines = $this->extractFormFieldLines($pdfContent);

        if ($formFieldLines !== []) {
            $textChunks[] = implode("\n", $formFieldLines);
        }

        $text = trim(implode("\n", $textChunks));
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function decodeStream(string $stream, string $dictionary): ?string
    {
        $decoded = $stream;

        if (stripos($dictionary, '/FlateDecode') !== false) {
            $decoded = $this->inflateStream($stream);
        }

        if ($decoded === null) {
            return null;
        }

        return is_string($decoded) ? $decoded : null;
    }

    private function inflateStream(string $stream): ?string
    {
        $variants = [
            $stream,
            ltrim($stream, "\r\n"),
            substr($stream, 2),
        ];

        foreach ($variants as $variant) {
            if (! is_string($variant) || $variant === '') {
                continue;
            }

            foreach (['gzuncompress', 'gzdecode', 'gzinflate'] as $method) {
                try {
                    $decoded = @$method($variant);
                } catch (\Throwable) {
                    $decoded = false;
                }

                if (is_string($decoded) && $decoded !== '') {
                    return $decoded;
                }
            }
        }

        return null;
    }

    private function extractTextFromStream(string $stream, array $characterMaps = []): string
    {
        $blocks = [];

        if (! preg_match_all('/BT(.*?)ET/s', $stream, $matches)) {
            return '';
        }

        foreach ($matches[1] as $block) {
            $segments = [];

            if (preg_match_all('/\((?:\\.|[^\\)])*\)|<[A-Fa-f0-9\s]+>/', $block, $textMatches)) {
                foreach ($textMatches[0] as $token) {
                    $segments[] = $this->decodeToken($token, $characterMaps);
                }
            }

            $line = trim(implode('', array_filter($segments, static fn ($segment) => $segment !== '')));

            if ($line !== '') {
                $blocks[] = $line;
            }
        }

        return implode("\n", $blocks);
    }

    private function decodeToken(string $token, array $characterMaps = []): string
    {
        if (str_starts_with($token, '(')) {
            return $this->decodeLiteralString(substr($token, 1, -1), $characterMaps);
        }

        return $this->decodeHexString(trim($token, '<>'), $characterMaps);
    }

    private function decodeLiteralString(string $value, array $characterMaps = []): string
    {
        $result = '';
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $char = $value[$index];

            if ($char !== '\\') {
                $result .= $char;
                continue;
            }

            $index++;

            if ($index >= $length) {
                break;
            }

            $escape = $value[$index];

            $result .= match ($escape) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b' => "\x08",
                'f' => "\f",
                '\\', '(', ')' => $escape,
                default => $this->decodeOctalEscape($value, $index, $escape),
            };
        }

        return $this->interpretEncodedString($result, $characterMaps);
    }

    private function decodeOctalEscape(string $value, int &$index, string $firstDigit): string
    {
        if (! ctype_digit($firstDigit)) {
            return $firstDigit;
        }

        $octal = $firstDigit;
        $length = strlen($value);

        for ($step = 0; $step < 2 && ($index + 1) < $length; $step++) {
            $next = $value[$index + 1];

            if ($next < '0' || $next > '7') {
                break;
            }

            $octal .= $next;
            $index++;
        }

        return chr(octdec($octal));
    }

    private function decodeHexString(string $hex, array $characterMaps = []): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? $hex;

        if ($hex === '') {
            return '';
        }

        if (strlen($hex) % 2 !== 0) {
            $hex .= '0';
        }

        $binary = hex2bin($hex);

        if ($binary === false) {
            return '';
        }

        $mappedValue = $this->decodeHexWithCharacterMaps(strtoupper($hex), $characterMaps);
        $normalizedValue = $this->normalizeString($binary);

        if ($mappedValue !== null && $this->readabilityScore($mappedValue) >= $this->readabilityScore($normalizedValue)) {
            return $mappedValue;
        }

        return $normalizedValue;
    }

    private function extractCharacterMaps(array $decodedStreams): array
    {
        $characterMaps = [];

        foreach ($decodedStreams as $decodedStream) {
            if (! str_contains($decodedStream, 'beginbfchar') && ! str_contains($decodedStream, 'beginbfrange')) {
                continue;
            }

            $characterMap = [];

            if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $decodedStream, $matches)) {
                foreach ($matches[1] as $block) {
                    if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $block, $pairs, PREG_SET_ORDER)) {
                        foreach ($pairs as $pair) {
                            $characterMap[strtoupper($pair[1])] = $this->decodeUnicodeHex($pair[2]);
                        }
                    }
                }
            }

            if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $decodedStream, $matches)) {
                foreach ($matches[1] as $block) {
                    $lines = preg_split('/\r?\n/', trim($block)) ?: [];

                    foreach ($lines as $line) {
                        $line = trim($line);

                        if ($line === '') {
                            continue;
                        }

                        if (preg_match('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $line, $rangeMatch) === 1) {
                            $this->appendSequentialRange($characterMap, $rangeMatch[1], $rangeMatch[2], $rangeMatch[3]);
                            continue;
                        }

                        if (preg_match('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*\[(.*?)\]/', $line, $rangeMatch) === 1) {
                            $this->appendExplicitRange($characterMap, $rangeMatch[1], $rangeMatch[2], $rangeMatch[3]);
                        }
                    }
                }
            }

            if ($characterMap !== []) {
                $characterMaps[] = $characterMap;
            }
        }

        return $characterMaps;
    }

    private function appendSequentialRange(array &$characterMap, string $fromHex, string $toHex, string $targetHex): void
    {
        $from = hexdec($fromHex);
        $to = hexdec($toHex);
        $target = hexdec($targetHex);
        $width = strlen($fromHex);

        for ($offset = 0; ($from + $offset) <= $to; $offset++) {
            $source = strtoupper(str_pad(dechex($from + $offset), $width, '0', STR_PAD_LEFT));
            $unicode = strtoupper(dechex($target + $offset));

            if (strlen($unicode) % 2 !== 0) {
                $unicode = '0' . $unicode;
            }

            $characterMap[$source] = $this->decodeUnicodeHex($unicode);
        }
    }

    private function appendExplicitRange(array &$characterMap, string $fromHex, string $toHex, string $targetList): void
    {
        $from = hexdec($fromHex);
        $to = hexdec($toHex);
        $width = strlen($fromHex);

        if (preg_match_all('/<([0-9A-Fa-f]+)>/', $targetList, $targets) !== 1) {
            return;
        }

        foreach ($targets[1] as $index => $targetHex) {
            if (($from + $index) > $to) {
                break;
            }

            $source = strtoupper(str_pad(dechex($from + $index), $width, '0', STR_PAD_LEFT));
            $characterMap[$source] = $this->decodeUnicodeHex($targetHex);
        }
    }

    private function decodeUnicodeHex(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? $hex;

        if ($hex === '') {
            return '';
        }

        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        $binary = hex2bin($hex);

        if ($binary === false) {
            return '';
        }

        return $this->normalizeString($binary, false);
    }

    private function interpretEncodedString(string $value, array $characterMaps = []): string
    {
        if ($value === '') {
            return '';
        }

        $mappedValue = $this->decodeHexWithCharacterMaps(strtoupper(bin2hex($value)), $characterMaps);
        $normalizedValue = $this->normalizeString($value);

        if ($mappedValue !== null && $this->readabilityScore($mappedValue) >= $this->readabilityScore($normalizedValue)) {
            return $mappedValue;
        }

        return $normalizedValue;
    }

    private function decodeHexWithCharacterMaps(string $hex, array $characterMaps): ?string
    {
        $bestValue = null;
        $bestScore = -1;

        foreach ($characterMaps as $characterMap) {
            $decoded = $this->decodeHexWithCharacterMap($hex, $characterMap);

            if ($decoded === null || $decoded === '') {
                continue;
            }

            $score = $this->readabilityScore($decoded);

            if ($score > $bestScore) {
                $bestValue = $decoded;
                $bestScore = $score;
            }
        }

        return $bestValue;
    }

    private function decodeHexWithCharacterMap(string $hex, array $characterMap): ?string
    {
        if ($characterMap === []) {
            return null;
        }

        $lengths = array_values(array_unique(array_map('strlen', array_keys($characterMap))));
        rsort($lengths);

        $decoded = '';
        $usedMapping = false;
        $offset = 0;
        $hexLength = strlen($hex);

        while ($offset < $hexLength) {
            $matched = false;

            foreach ($lengths as $length) {
                if (($offset + $length) > $hexLength) {
                    continue;
                }

                $chunk = substr($hex, $offset, $length);

                if (! array_key_exists($chunk, $characterMap)) {
                    continue;
                }

                $decoded .= $characterMap[$chunk];
                $offset += $length;
                $matched = true;
                $usedMapping = true;
                break;
            }

            if ($matched) {
                continue;
            }

            if (($offset + 2) > $hexLength) {
                break;
            }

            $fallback = hex2bin(substr($hex, $offset, 2));
            $decoded .= $fallback === false ? '' : $this->normalizeString($fallback, false);
            $offset += 2;
        }

        if (! $usedMapping) {
            return null;
        }

        return trim($decoded);
    }

    private function extractFormFieldLines(string $pdfContent): array
    {
        $lines = [];
        $seen = [];

        if (preg_match_all('/\d+\s+\d+\s+obj(.*?)endobj/s', $pdfContent, $matches) < 1) {
            return [];
        }

        foreach ($matches[1] as $objectBody) {
            $valueToken = $this->extractDictionaryToken($objectBody, ['V']);

            if ($valueToken === null) {
                continue;
            }

            $value = $this->decodeDictionaryToken($valueToken);

            if ($value === '' || in_array($value, ['Off', 'Yes'], true)) {
                continue;
            }

            $labelToken = $this->extractDictionaryToken($objectBody, ['TU', 'T', 'TM']);
            $label = $labelToken !== null ? $this->decodeDictionaryToken($labelToken) : 'Campo';
            $line = trim($label) . ': ' . trim($value);
            $fingerprint = mb_strtolower($line);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $lines[] = $line;
        }

        return $lines;
    }

    private function extractDictionaryToken(string $objectBody, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (preg_match('/\/' . preg_quote($key, '/') . '\s*(\((?:\\.|[^\\)])*\)|<[A-Fa-f0-9\s]+>)/s', $objectBody, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    private function decodeDictionaryToken(string $token): string
    {
        if (str_starts_with($token, '(')) {
            return $this->decodeLiteralString(substr($token, 1, -1));
        }

        return $this->decodeHexString(trim($token, '<>'));
    }

    private function readabilityScore(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        preg_match_all('/[A-Za-z0-9\s:\/\-.]/u', $value, $matches);

        return count($matches[0]);
    }

    private function normalizeString(string $value, bool $trim = true): string
    {
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, "\xFE\xFF") || str_starts_with($value, "\xFF\xFE")) {
            $converted = (string) mb_convert_encoding($value, 'UTF-8', 'UTF-16');

            return $trim ? trim($converted) : $converted;
        }

        if (preg_match('/^(?:\x00[\x09\x0A\x0D\x20-\x7E])+$/', $value) === 1) {
            $converted = (string) mb_convert_encoding($value, 'UTF-8', 'UTF-16BE');

            return $trim ? trim($converted) : $converted;
        }

        if (preg_match('/^(?:[\x09\x0A\x0D\x20-\x7E]\x00)+$/', $value) === 1) {
            $converted = (string) mb_convert_encoding($value, 'UTF-8', 'UTF-16LE');

            return $trim ? trim($converted) : $converted;
        }

        return $trim ? trim($value) : $value;
    }
}