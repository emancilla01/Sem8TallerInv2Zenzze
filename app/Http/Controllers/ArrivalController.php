<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Services\PdfFirstPageImageConverter;
use App\Services\RegisterCardTextParser;
use App\Services\TesseractOcrService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ArrivalController extends Controller
{
    private const TEMPORARY_OCR_DIRECTORY = 'private/ocr-tests/register-cards';
    private const OCR_DOCUMENT_SESSION_KEY = 'arrivals.ocr_document';

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'asc');
        $allowedSorts = ['nombre', 'apellido'];

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = null;
        }

        $query = Expediente::query()
            ->withCount('documentos')
            ->whereDate('fecha_llegada', now()->toDateString())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%");
                });
            })
            ->when($sort !== null, function ($query) use ($sort, $direction) {
                $query->orderBy($sort, $direction);
            }, function ($query) {
                $query->orderBy('apellido')->orderBy('nombre');
            });

        $expedientes = $query->paginate(10)->withQueryString();

        return view('contenido', compact('expedientes', 'sort', 'direction'));
    }

    public function database(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['nombre', 'apellido', 'fecha_llegada'];

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = null;
        }

        $query = Expediente::query()
            ->withCount('documentos')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%");
                });
            })
            ->when($sort !== null, function ($query) use ($sort, $direction) {
                $query->orderBy($sort, $direction);
            }, function ($query) {
                $query->orderByDesc('fecha_llegada')->orderBy('apellido')->orderBy('nombre');
            });

        $expedientes = $query->paginate(10)->withQueryString();

        return view('arrivals.database', compact('expedientes', 'sort', 'direction'));
    }

    public function create()
    {
        return view('arrivals.create', $this->createViewData());
    }

    public function prefillFromOcr(
        Request $request,
        PdfFirstPageImageConverter $pdfFirstPageImageConverter,
        TesseractOcrService $tesseractOcrService,
        RegisterCardTextParser $registerCardTextParser,
    ) {
        $validated = $request->validate(
            [
                'register_card_pdf' => ['required', 'file', 'mimes:pdf'],
                'ocr_document_temp' => ['nullable', 'string'],
            ],
            [
                'register_card_pdf.required' => 'Debes seleccionar el register card en PDF para aplicar OCR.',
                'register_card_pdf.file' => 'El register card debe ser un archivo válido.',
                'register_card_pdf.mimes' => 'El register card debe estar en formato PDF.',
            ]
        );

        $existingOcrDocument = $this->currentOcrDocument();

        if ($existingOcrDocument !== null) {
            $this->deleteTemporaryOcrDocument($existingOcrDocument['path']);
            $this->forgetCurrentOcrDocument();
        }

        $temporaryRegisterCard = $this->storeTemporaryOcrDocument($validated['register_card_pdf']);
        $this->rememberCurrentOcrDocument($temporaryRegisterCard['path'], $temporaryRegisterCard['original_name']);
        $formValues = $this->resolveCreateFormValues($request->only(['nombre', 'apellido', 'fecha_llegada']));
        $imagePath = null;

        try {
            $imagePath = $pdfFirstPageImageConverter->convert(Storage::disk('local')->path($temporaryRegisterCard['path']));
            $rawOcrText = $tesseractOcrService->extractText($imagePath);
            $parsedFields = $registerCardTextParser->parse($rawOcrText);

            $formValues = $this->resolveCreateFormValues([
                'nombre' => $parsedFields['nombre'] ?? $formValues['nombre'],
                'apellido' => $parsedFields['apellido'] ?? $formValues['apellido'],
                'fecha_llegada' => $this->normalizeArrivalDate($parsedFields['fecha_llegada'] ?? null) ?? $formValues['fecha_llegada'],
            ]);
        } catch (Throwable $exception) {
            return redirect()
                ->route('arrivals.create')
                ->withErrors([
                    'register_card_pdf' => $exception->getMessage(),
                ])
                ->withInput([
                    'nombre' => $formValues['nombre'],
                    'apellido' => $formValues['apellido'],
                    'fecha_llegada' => $formValues['fecha_llegada'],
                ]);
        } finally {
            if ($imagePath !== null && is_file($imagePath)) {
                @unlink($imagePath);
            }
        }

        return redirect()
            ->route('arrivals.create')
            ->withInput([
                'nombre' => $formValues['nombre'],
                'apellido' => $formValues['apellido'],
                'fecha_llegada' => $formValues['fecha_llegada'],
            ]);
    }

    public function show(int $id)
    {
        $expediente = Expediente::with('documentos')->findOrFail($id);

        return view('arrivals.show', compact('expediente'));
    }

    public function edit(int $id)
    {
        $expediente = Expediente::with('documentos')->findOrFail($id);

        return view('arrivals.edit', compact('expediente'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpediente($request);
        $currentOcrDocument = $this->currentOcrDocument();
        $ocrDocumentTempPath = $validated['ocr_document_temp'] ?? $currentOcrDocument['path'] ?? null;
        $ocrDocumentOriginalName = $validated['ocr_document_original_name'] ?? $currentOcrDocument['original_name'] ?? null;

        if (
            filled($ocrDocumentTempPath)
            && ! $this->temporaryOcrDocumentExists($ocrDocumentTempPath)
        ) {
            return back()
                ->withErrors([
                    'register_card_pdf' => 'El register card procesado ya no está disponible. Vuelve a cargarlo para guardar el expediente.',
                ])
                ->withInput();
        }

        $identificacionPath = $request->hasFile('identificacion')
            ? $request->file('identificacion')->store('expedientes', 'public')
            : null;

        $expediente = Expediente::create([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'fecha_llegada' => $validated['fecha_llegada'],
            'identificacion_path' => $identificacionPath,
        ]);

        if (filled($ocrDocumentTempPath)) {
            $this->storeTemporaryOcrDocumentAsDocumento(
                $expediente,
                $ocrDocumentTempPath,
                $ocrDocumentOriginalName,
            );
            $this->forgetCurrentOcrDocument();
        } elseif ($request->hasFile('register_card_pdf')) {
            $this->storeUploadedDocument($expediente, $request->file('register_card_pdf'));
        }

        foreach ($this->collectDocumentUploads($request) as $uploadedDocument) {
            $this->storeUploadedDocument($expediente, $uploadedDocument);
        }

        return redirect()
            ->route('home')
            ->with('success', 'Llegada registrada correctamente');
    }

    public function update(Request $request, int $id)
    {
        $expediente = Expediente::findOrFail($id);
        $validated = $this->validateExpediente($request);

        $data = [
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'fecha_llegada' => $validated['fecha_llegada'],
        ];

        foreach ($this->collectDocumentUploads($request) as $uploadedDocument) {
            $this->storeUploadedDocument($expediente, $uploadedDocument);
        }

        if ($request->hasFile('identificacion')) {
            if (filled($expediente->identificacion_path)) {
                Storage::disk('public')->delete($expediente->identificacion_path);
            }

            $data['identificacion_path'] = $request->file('identificacion')->store('expedientes', 'public');
        }

        $expediente->update($data);

        return redirect()
            ->route('expedientes.show', $expediente->id)
            ->with('success', 'Expediente actualizado correctamente');
    }

    public function destroy(Request $request, int $id)
    {
        $expediente = Expediente::with('documentos')->findOrFail($id);

        foreach ($expediente->documentos as $documento) {
            Storage::disk('public')->delete($documento->path);
        }

        if (filled($expediente->identificacion_path)) {
            Storage::disk('public')->delete($expediente->identificacion_path);
        }

        $expediente->delete();

        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && str_starts_with($redirectTo, config('app.url'))) {
            return redirect()
                ->to($redirectTo)
                ->with('success', 'Expediente eliminado correctamente');
        }

        return redirect()
            ->route('home')
            ->with('success', 'Expediente eliminado correctamente');
    }

    private function validateExpediente(Request $request): array
    {
        return $request->validate(
            [
                'nombre' => ['required', 'string', 'max:255'],
                'apellido' => ['required', 'string', 'max:255'],
                'fecha_llegada' => ['required', 'date'],
                'register_card_pdf' => ['nullable', 'file', 'mimes:pdf'],
                'ocr_document_temp' => ['nullable', 'string'],
                'ocr_document_original_name' => ['nullable', 'string', 'max:255'],
                'documento' => ['nullable', 'file', 'mimes:pdf'],
                'documentos' => ['nullable', 'array'],
                'documentos.*' => ['nullable', 'file', 'mimes:pdf'],
                'identificacion' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],
            ],
            [
                'nombre.required' => 'El nombre del huésped es obligatorio.',
                'nombre.string' => 'El nombre del huésped debe ser un texto válido.',
                'nombre.max' => 'El nombre del huésped no puede tener más de 255 caracteres.',
                'apellido.required' => 'El apellido del huésped es obligatorio.',
                'apellido.string' => 'El apellido del huésped debe ser un texto válido.',
                'apellido.max' => 'El apellido del huésped no puede tener más de 255 caracteres.',
                'fecha_llegada.required' => 'La fecha de llegada es obligatoria.',
                'fecha_llegada.date' => 'La fecha de llegada debe ser una fecha válida.',
                'register_card_pdf.file' => 'El register card debe ser un archivo válido.',
                'register_card_pdf.mimes' => 'El register card debe estar en formato PDF.',
                'documento.file' => 'El documento debe ser un archivo válido.',
                'documento.mimes' => 'El documento debe estar en formato PDF.',
                'documentos.array' => 'Los documentos deben enviarse como una lista válida.',
                'documentos.*.file' => 'Cada documento debe ser un archivo válido.',
                'documentos.*.mimes' => 'Cada documento debe estar en formato PDF.',
                'identificacion.file' => 'La identificación debe ser un archivo válido.',
                'identificacion.mimes' => 'La identificación debe ser un PDF o una imagen válida.',
            ]
        );
    }

    private function createViewData(array $formValues = [], ?string $ocrDocumentTemp = null, ?string $ocrDocumentOriginalName = null): array
    {
        return [
            'formValues' => $this->resolveCreateFormValues($formValues),
            'ocrDocumentTemp' => $ocrDocumentTemp ?? $this->currentOcrDocument()['path'] ?? null,
            'ocrDocumentOriginalName' => $ocrDocumentOriginalName ?? $this->currentOcrDocument()['original_name'] ?? null,
        ];
    }

    private function resolveCreateFormValues(array $values): array
    {
        return [
            'nombre' => trim((string) ($values['nombre'] ?? '')),
            'apellido' => trim((string) ($values['apellido'] ?? '')),
            'fecha_llegada' => $this->normalizeArrivalDate($values['fecha_llegada'] ?? null) ?? now()->format('Y-m-d'),
        ];
    }

    private function normalizeArrivalDate(null|string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) === 1) {
            return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : null;
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})$/', $value, $matches) === 1) {
            // Opera OCR dates are expected as DD-MM-YY or DD-MM-YYYY.
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if ($year < 100) {
                $year += 2000;
            }

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function collectDocumentUploads(Request $request): array
    {
        $documents = [];

        $singleDocument = $request->file('documento');

        if ($singleDocument instanceof UploadedFile) {
            $documents[] = $singleDocument;
        }

        foreach ((array) $request->file('documentos', []) as $uploadedDocument) {
            if ($uploadedDocument instanceof UploadedFile) {
                $documents[] = $uploadedDocument;
            }
        }

        return $documents;
    }

    private function storeUploadedDocument(Expediente $expediente, UploadedFile $uploadedDocument): void
    {
        $originalName = $uploadedDocument->getClientOriginalName();
        $storedPath = $uploadedDocument->storeAs(
            'expedientes',
            basename($this->nextAvailableDocumentPath($originalName)),
            'public'
        );

        $expediente->documentos()->create([
            'path' => $storedPath,
            'original_name' => $originalName,
        ]);
    }

    private function storeTemporaryOcrDocument(UploadedFile $uploadedDocument): array
    {
        $originalName = $uploadedDocument->getClientOriginalName();
        $storedName = bin2hex(random_bytes(8)) . '-' . $this->sanitizeStoredFileName($originalName);
        $storedPath = $uploadedDocument->storeAs(self::TEMPORARY_OCR_DIRECTORY, $storedName, 'local');

        return [
            'path' => $storedPath,
            'original_name' => $originalName,
        ];
    }

    private function storeTemporaryOcrDocumentAsDocumento(Expediente $expediente, string $temporaryPath, ?string $originalName): void
    {
        if (! $this->temporaryOcrDocumentExists($temporaryPath)) {
            return;
        }

        $effectiveOriginalName = filled($originalName) ? $originalName : basename($temporaryPath);
        $finalPath = $this->nextAvailableDocumentPath($effectiveOriginalName);

        Storage::disk('public')->put($finalPath, (string) Storage::disk('local')->get($temporaryPath));

        $expediente->documentos()->create([
            'path' => $finalPath,
            'original_name' => $effectiveOriginalName,
        ]);

        Storage::disk('local')->delete($temporaryPath);
    }

    private function deleteTemporaryOcrDocument(string $temporaryPath): void
    {
        if ($this->temporaryOcrDocumentExists($temporaryPath)) {
            Storage::disk('local')->delete($temporaryPath);
        }
    }

    private function temporaryOcrDocumentExists(string $temporaryPath): bool
    {
        return str_starts_with($temporaryPath, self::TEMPORARY_OCR_DIRECTORY . '/')
            && Storage::disk('local')->exists($temporaryPath);
    }

    private function nextAvailableDocumentPath(?string $originalName): string
    {
        $sanitizedFileName = $this->sanitizeStoredFileName($originalName);
        $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);
        $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
        $candidate = 'expedientes/' . $sanitizedFileName;
        $counter = 1;

        while (Storage::disk('public')->exists($candidate)) {
            $candidate = 'expedientes/' . $baseName . '-' . $counter . '.' . $extension;
            $counter++;
        }

        return $candidate;
    }

    private function sanitizeStoredFileName(?string $originalName): string
    {
        $originalName = trim((string) $originalName);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeBaseName = (string) Str::of($baseName)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '-')
            ->trim('-_.');

        if ($safeBaseName === '') {
            $safeBaseName = 'documento';
        }

        if ($extension === '') {
            $extension = 'pdf';
        }

        return $safeBaseName . '.' . $extension;
    }

    private function currentOcrDocument(): ?array
    {
        $document = session(self::OCR_DOCUMENT_SESSION_KEY);

        if (! is_array($document) || ! isset($document['path'], $document['original_name'])) {
            return null;
        }

        if (! $this->temporaryOcrDocumentExists($document['path'])) {
            $this->forgetCurrentOcrDocument();

            return null;
        }

        return $document;
    }

    private function rememberCurrentOcrDocument(string $path, string $originalName): void
    {
        session([self::OCR_DOCUMENT_SESSION_KEY => [
            'path' => $path,
            'original_name' => $originalName,
        ]]);
    }

    private function forgetCurrentOcrDocument(): void
    {
        session()->forget(self::OCR_DOCUMENT_SESSION_KEY);
    }
}