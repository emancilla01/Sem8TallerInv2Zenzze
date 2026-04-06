<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Services\PdfFirstPageImageConverter;
use App\Services\RegisterCardTextParser;
use App\Services\TesseractOcrService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ArrivalController extends Controller
{
    private const TEMPORARY_OCR_DIRECTORY = 'private/ocr-tests/register-cards';
    private const OCR_DOCUMENT_SESSION_KEY = 'arrivals.ocr_document';
    private const TEMPORARY_BATCH_OCR_DIRECTORY = 'private/ocr-tests/register-cards/batch';
    private const BATCH_ROWS_SESSION_KEY = 'arrivals.batch_rows';

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

    public function batchCreate()
    {
        return view('arrivals.batch', $this->createBatchViewData());
    }

    public function processBatchOcr(
        Request $request,
        PdfFirstPageImageConverter $pdfFirstPageImageConverter,
        TesseractOcrService $tesseractOcrService,
        RegisterCardTextParser $registerCardTextParser,
    ) {
        $validated = $request->validate(
            [
                'register_card_pdfs' => ['required', 'array', 'min:1'],
                'register_card_pdfs.*' => ['required', 'file', 'mimes:pdf'],
            ],
            [
                'register_card_pdfs.required' => 'Debes seleccionar al menos un register card en PDF.',
                'register_card_pdfs.array' => 'Los register cards deben enviarse como una lista válida.',
                'register_card_pdfs.min' => 'Debes seleccionar al menos un register card en PDF.',
                'register_card_pdfs.*.file' => 'Cada register card debe ser un archivo válido.',
                'register_card_pdfs.*.mimes' => 'Cada register card debe estar en formato PDF.',
            ]
        );

        $this->deleteBatchRows($this->currentBatchRows());
        $rows = [];

        foreach ((array) ($validated['register_card_pdfs'] ?? $request->file('register_card_pdfs', [])) as $uploadedRegisterCard) {
            if (! $uploadedRegisterCard instanceof UploadedFile) {
                continue;
            }

            $temporaryRegisterCard = $this->storeTemporaryBatchDocument($uploadedRegisterCard);
            $imagePath = null;

            $row = [
                'id' => (string) Str::uuid(),
                'temp_path' => $temporaryRegisterCard['path'],
                'original_name' => $temporaryRegisterCard['original_name'],
                'nombre' => '',
                'apellido' => '',
                'fecha_llegada' => '',
                'error_message' => null,
            ];

            try {
                $imagePath = $pdfFirstPageImageConverter->convert(Storage::disk('local')->path($temporaryRegisterCard['path']));
                $rawOcrText = $tesseractOcrService->extractText($imagePath);
                $parsedFields = $registerCardTextParser->parse($rawOcrText);

                $row = array_merge($row, $this->resolveBatchRowValues($parsedFields));
            } catch (Throwable $exception) {
                $row['error_message'] = $exception->getMessage();
            } finally {
                if ($imagePath !== null && is_file($imagePath)) {
                    @unlink($imagePath);
                }
            }

            $rows[] = $this->normalizeBatchRow($row);
        }

        $this->rememberBatchRows($rows);

        return redirect()
            ->route('arrivals.batch.index')
            ->with('success', count($rows) === 1 ? 'Se procesó 1 registro para revisión.' : 'Se procesaron ' . count($rows) . ' registros para revisión.');
    }

    public function batchEdit(string $rowId)
    {
        $row = $this->findCurrentBatchRow($rowId);

        if ($row === null) {
            return redirect()
                ->route('arrivals.batch.index')
                ->withErrors([
                    'batch_actions' => 'El registro solicitado ya no está disponible para revisión.',
                ]);
        }

        return view('arrivals.batch-edit', [
            'row' => $row,
            'formValues' => $this->resolveBatchRowValues($row),
        ]);
    }

    public function batchUpdate(Request $request, string $rowId)
    {
        $rows = $this->currentBatchRows();
        $rowIndex = $this->findBatchRowIndex($rows, $rowId);

        if ($rowIndex === null) {
            return redirect()
                ->route('arrivals.batch.index')
                ->withErrors([
                    'batch_actions' => 'El registro solicitado ya no está disponible para revisión.',
                ]);
        }

        $validated = $request->validate(
            [
                'nombre' => ['nullable', 'string', 'max:255'],
                'apellido' => ['nullable', 'string', 'max:255'],
                'fecha_llegada' => ['nullable', 'date'],
            ],
            [
                'nombre.string' => 'El nombre del huésped debe ser un texto válido.',
                'nombre.max' => 'El nombre del huésped no puede tener más de 255 caracteres.',
                'apellido.string' => 'El apellido del huésped debe ser un texto válido.',
                'apellido.max' => 'El apellido del huésped no puede tener más de 255 caracteres.',
                'fecha_llegada.date' => 'La fecha de llegada debe ser una fecha válida.',
            ]
        );

        $rows[$rowIndex] = $this->normalizeBatchRow(array_merge(
            $rows[$rowIndex],
            $this->resolveBatchRowValues($validated),
            ['error_message' => null]
        ));

        $this->rememberBatchRows($rows);

        return redirect()
            ->route('arrivals.batch.index')
            ->with('success', 'Registro actualizado para revisión.');
    }

    public function batchStoreSelected(Request $request)
    {
        $validated = $request->validate(
            [
                'selected_rows' => ['required', 'array', 'min:1'],
                'selected_rows.*' => ['required', 'string'],
            ],
            [
                'selected_rows.required' => 'Selecciona al menos un registro listo para guardar.',
                'selected_rows.array' => 'La selección de registros no es válida.',
                'selected_rows.min' => 'Selecciona al menos un registro listo para guardar.',
            ]
        );

        $selectedRowIds = array_values(array_unique((array) $validated['selected_rows']));
        $rows = $this->currentBatchRows();
        $readySelectedCount = count(array_filter($rows, function (array $row) use ($selectedRowIds) {
            return in_array($row['id'], $selectedRowIds, true) && $row['status'] === 'ready';
        }));

        if ($readySelectedCount === 0) {
            return redirect()
                ->route('arrivals.batch.index')
                ->withErrors([
                    'batch_actions' => 'Selecciona al menos un registro listo para guardar.',
                ]);
        }

        $result = $this->persistBatchRows($rows, function (array $row) use ($selectedRowIds) {
            return in_array($row['id'], $selectedRowIds, true);
        });

        return $this->redirectAfterBatchSave($result, 'seleccionados');
    }

    public function batchStoreValid()
    {
        $rows = $this->currentBatchRows();
        $readyRowCount = count(array_filter($rows, fn (array $row) => $row['status'] === 'ready'));

        if ($readyRowCount === 0) {
            return redirect()
                ->route('arrivals.batch.index')
                ->withErrors([
                    'batch_actions' => 'No hay registros listos para guardar.',
                ]);
        }

        $result = $this->persistBatchRows($rows, fn (array $row) => $row['status'] === 'ready');

        return $this->redirectAfterBatchSave($result, 'válidos');
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

    private function createBatchViewData(): array
    {
        $batchRows = $this->currentBatchRows();

        return [
            'batchRows' => $batchRows,
            'readyBatchRowCount' => count(array_filter($batchRows, fn (array $row) => $row['status'] === 'ready')),
            'incompleteBatchRowCount' => count(array_filter($batchRows, fn (array $row) => $row['status'] === 'incomplete')),
            'errorBatchRowCount' => count(array_filter($batchRows, fn (array $row) => $row['status'] === 'error')),
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

    private function resolveBatchRowValues(array $values): array
    {
        return [
            'nombre' => trim((string) ($values['nombre'] ?? '')),
            'apellido' => trim((string) ($values['apellido'] ?? '')),
            'fecha_llegada' => $this->normalizeArrivalDate($values['fecha_llegada'] ?? null) ?? '',
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
        return $this->storeTemporaryUploadedDocument($uploadedDocument, self::TEMPORARY_OCR_DIRECTORY);
    }

    private function storeTemporaryBatchDocument(UploadedFile $uploadedDocument): array
    {
        return $this->storeTemporaryUploadedDocument($uploadedDocument, self::TEMPORARY_BATCH_OCR_DIRECTORY);
    }

    private function storeTemporaryUploadedDocument(UploadedFile $uploadedDocument, string $directory): array
    {
        $originalName = $uploadedDocument->getClientOriginalName();
        $storedName = bin2hex(random_bytes(8)) . '-' . $this->sanitizeStoredFileName($originalName);
        $storedPath = $uploadedDocument->storeAs($directory, $storedName, 'local');

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
        return $this->isAllowedTemporaryOcrPath($temporaryPath)
            && Storage::disk('local')->exists($temporaryPath);
    }

    private function isAllowedTemporaryOcrPath(string $temporaryPath): bool
    {
        return str_starts_with($temporaryPath, self::TEMPORARY_OCR_DIRECTORY . '/')
            || str_starts_with($temporaryPath, self::TEMPORARY_BATCH_OCR_DIRECTORY . '/');
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function currentBatchRows(): array
    {
        $rows = session(self::BATCH_ROWS_SESSION_KEY, []);

        if (! is_array($rows) || $rows === []) {
            return [];
        }

        $normalizedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['temp_path'], $row['original_name'])) {
                continue;
            }

            $normalizedRows[] = $this->normalizeBatchRow($row);
        }

        if ($normalizedRows === []) {
            $this->forgetBatchRows();

            return [];
        }

        $this->rememberBatchRows($normalizedRows);

        return $normalizedRows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeBatchRow(array $row): array
    {
        $normalizedRow = [
            'id' => (string) ($row['id'] ?? Str::uuid()),
            'temp_path' => (string) ($row['temp_path'] ?? ''),
            'original_name' => trim((string) ($row['original_name'] ?? '')),
            'nombre' => trim((string) ($row['nombre'] ?? '')),
            'apellido' => trim((string) ($row['apellido'] ?? '')),
            'fecha_llegada' => $this->normalizeArrivalDate($row['fecha_llegada'] ?? null) ?? '',
            'error_message' => filled($row['error_message'] ?? null) ? trim((string) $row['error_message']) : null,
        ];

        if ($normalizedRow['original_name'] === '') {
            $normalizedRow['original_name'] = basename($normalizedRow['temp_path']);
        }

        if (! $this->temporaryOcrDocumentExists($normalizedRow['temp_path'])) {
            $normalizedRow['error_message'] = 'El archivo cargado ya no está disponible. Vuelve a procesarlo para poder guardarlo.';
        }

        $normalizedRow['status'] = $this->determineBatchRowStatus($normalizedRow);

        return $normalizedRow;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function determineBatchRowStatus(array $row): string
    {
        if (filled($row['error_message'] ?? null)) {
            return 'error';
        }

        if (
            trim((string) ($row['nombre'] ?? '')) === ''
            || trim((string) ($row['apellido'] ?? '')) === ''
            || trim((string) ($row['fecha_llegada'] ?? '')) === ''
        ) {
            return 'incomplete';
        }

        return 'ready';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function rememberBatchRows(array $rows): void
    {
        session([self::BATCH_ROWS_SESSION_KEY => array_values($rows)]);
    }

    private function forgetBatchRows(): void
    {
        session()->forget(self::BATCH_ROWS_SESSION_KEY);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function deleteBatchRows(array $rows): void
    {
        foreach ($rows as $row) {
            $temporaryPath = (string) ($row['temp_path'] ?? '');

            if ($temporaryPath !== '') {
                $this->deleteTemporaryOcrDocument($temporaryPath);
            }
        }

        $this->forgetBatchRows();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findCurrentBatchRow(string $rowId): ?array
    {
        foreach ($this->currentBatchRows() as $row) {
            if ($row['id'] === $rowId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function findBatchRowIndex(array $rows, string $rowId): ?int
    {
        foreach ($rows as $index => $row) {
            if (($row['id'] ?? null) === $rowId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): bool  $shouldSave
     * @return array{saved_count:int, skipped_count:int, remaining_rows:array<int, array<string, mixed>>}
     */
    private function persistBatchRows(array $rows, callable $shouldSave): array
    {
        $savedCount = 0;
        $skippedCount = 0;
        $remainingRows = [];

        foreach ($rows as $row) {
            if (! $shouldSave($row)) {
                $remainingRows[] = $row;

                continue;
            }

            if (($row['status'] ?? null) !== 'ready') {
                $skippedCount++;
                $remainingRows[] = $row;

                continue;
            }

            if (! $this->temporaryOcrDocumentExists((string) ($row['temp_path'] ?? ''))) {
                $skippedCount++;
                $remainingRows[] = $this->normalizeBatchRow(array_merge($row, [
                    'error_message' => 'El archivo cargado ya no está disponible. Vuelve a procesarlo para poder guardarlo.',
                ]));

                continue;
            }

            try {
                $expediente = Expediente::create([
                    'nombre' => $row['nombre'],
                    'apellido' => $row['apellido'],
                    'fecha_llegada' => $row['fecha_llegada'],
                    'identificacion_path' => null,
                ]);

                $this->storeTemporaryOcrDocumentAsDocumento(
                    $expediente,
                    (string) $row['temp_path'],
                    (string) ($row['original_name'] ?? null)
                );

                $savedCount++;
            } catch (Throwable $exception) {
                $skippedCount++;
                $remainingRows[] = $this->normalizeBatchRow(array_merge($row, [
                    'error_message' => $exception->getMessage(),
                ]));
            }
        }

        if ($remainingRows === []) {
            $this->forgetBatchRows();
        } else {
            $this->rememberBatchRows($remainingRows);
        }

        return [
            'saved_count' => $savedCount,
            'skipped_count' => $skippedCount,
            'remaining_rows' => $remainingRows,
        ];
    }

    /**
     * @param  array{saved_count:int, skipped_count:int, remaining_rows:array<int, array<string, mixed>>}  $result
     */
    private function redirectAfterBatchSave(array $result, string $scopeLabel)
    {
        if ($result['saved_count'] === 0) {
            return redirect()
                ->route('arrivals.batch.index')
                ->withErrors([
                    'batch_actions' => 'No se pudo guardar ningún registro ' . $scopeLabel . '.',
                ]);
        }

        $message = $result['saved_count'] === 1
            ? 'Se guardó 1 registro.'
            : 'Se guardaron ' . $result['saved_count'] . ' registros.';

        $redirect = redirect()
            ->route('arrivals.batch.index')
            ->with('success', $message);

        if ($result['skipped_count'] > 0) {
            return $redirect->with('warning', 'Algunos registros no se guardaron porque siguen incompletos o presentaron errores.');
        }

        return $redirect;
    }
}