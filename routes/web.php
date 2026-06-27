<?php

use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\RegisterCardOcrController;
use App\Http\Controllers\ExpedienteFirmaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArrivalController::class, 'index'])->name('home');
Route::get('/base-de-datos', [ArrivalController::class, 'database'])->name('database.index');
Route::get('/pruebas/ocr-register-card', [RegisterCardOcrController::class, 'create'])->name('register-card-ocr.create');
Route::post('/pruebas/ocr-register-card', [RegisterCardOcrController::class, 'store'])->name('register-card-ocr.store');

Route::get('/llegadas/nueva', [ArrivalController::class, 'create'])->name('arrivals.create');
Route::get('/carga-masiva', [ArrivalController::class, 'batchCreate'])->name('arrivals.batch.index');
Route::post('/carga-masiva/procesar', [ArrivalController::class, 'processBatchOcr'])->name('arrivals.batch.process');
Route::post('/carga-masiva/guardar-seleccionados', [ArrivalController::class, 'batchStoreSelected'])->name('arrivals.batch.store-selected');
Route::post('/carga-masiva/guardar-validos', [ArrivalController::class, 'batchStoreValid'])->name('arrivals.batch.store-valid');
Route::get('/carga-masiva/{rowId}/revisar', [ArrivalController::class, 'batchEdit'])->name('arrivals.batch.edit');
Route::put('/carga-masiva/{rowId}', [ArrivalController::class, 'batchUpdate'])->name('arrivals.batch.update');
Route::post('/llegadas/nueva/ocr', [ArrivalController::class, 'prefillFromOcr'])->name('arrivals.prefill-ocr');
Route::post('/llegadas', [ArrivalController::class, 'store'])->name('arrivals.store');
Route::get('/expedientes/{id}', [ArrivalController::class, 'show'])->name('expedientes.show');
Route::get('/expedientes/{id}/editar', [ArrivalController::class, 'edit'])->name('expedientes.edit');
Route::put('/expedientes/{id}', [ArrivalController::class, 'update'])->name('expedientes.update');
Route::delete('/expedientes/{id}', [ArrivalController::class, 'destroy'])->name('expedientes.destroy');

Route::post('/expedientes/{expediente}/firma', [ExpedienteFirmaController::class, 'store'])->name('expedientes.firma.store');

Route::redirect('/dashboard', '/')->name('dashboard');

Route::view('/categorias', 'placeholder', ['titulo' => 'Agregar llegada'])->name('categorias');
Route::view('/personal', 'placeholder', ['titulo' => 'Personal'])->name('personal');
Route::view('/periodos', 'placeholder', ['titulo' => 'Periodos'])->name('periodos');
Route::view('/carreras', 'placeholder', ['titulo' => 'Carreras'])->name('carreras');
Route::view('/materias', 'placeholder', ['titulo' => 'Materias'])->name('materias');
Route::view('/grupos', 'placeholder', ['titulo' => 'Grupos'])->name('grupos');
Route::view('/espaciosdetrabajo', 'placeholder', ['titulo' => 'Espacios de Trabajo'])->name('espaciosdetrabajo');
Route::view('/software', 'placeholder', ['titulo' => 'Software'])->name('software');
Route::view('/entradas', 'placeholder', ['titulo' => 'Entradas'])->name('entradas');
Route::view('/salidas', 'placeholder', ['titulo' => 'Salidas'])->name('salidas');
Route::view('/ecm-inventario', 'placeholder', ['titulo' => 'ECM - Inventario'])->name('ecm-inventario');
