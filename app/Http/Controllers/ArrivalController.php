<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArrivalController extends Controller
{
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
        return view('arrivals.create');
    }

    public function show(int $id)
    {
        $expediente = Expediente::findOrFail($id);

        return view('arrivals.show', compact('expediente'));
    }

    public function edit(int $id)
    {
        $expediente = Expediente::findOrFail($id);

        return view('arrivals.edit', compact('expediente'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpediente($request);

        $documentoPath = $request->hasFile('documento')
            ? $request->file('documento')->store('expedientes', 'public')
            : null;

        $identificacionPath = $request->hasFile('identificacion')
            ? $request->file('identificacion')->store('expedientes', 'public')
            : null;

        Expediente::create([
            'nombre' => $validated['nombre'],
            'apellido' => $validated['apellido'],
            'fecha_llegada' => $validated['fecha_llegada'],
            'documento_path' => $documentoPath,
            'identificacion_path' => $identificacionPath,
        ]);

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

        if ($request->hasFile('documento')) {
            if (filled($expediente->documento_path)) {
                Storage::disk('public')->delete($expediente->documento_path);
            }

            $data['documento_path'] = $request->file('documento')->store('expedientes', 'public');
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
        $expediente = Expediente::findOrFail($id);

        if (filled($expediente->documento_path)) {
            Storage::disk('public')->delete($expediente->documento_path);
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
                'documento' => ['nullable', 'file', 'mimes:pdf'],
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
                'documento.file' => 'El documento debe ser un archivo válido.',
                'documento.mimes' => 'El documento debe estar en formato PDF.',
                'identificacion.file' => 'La identificación debe ser un archivo válido.',
                'identificacion.mimes' => 'La identificación debe ser un PDF o una imagen válida.',
            ]
        );
    }
}