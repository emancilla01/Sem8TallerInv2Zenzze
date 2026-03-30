<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('contenido');
}) ->name('home');

Route::redirect('/dashboard', '/')->name('dashboard');

Route::view('/categorias', 'placeholder', ['titulo' => 'Categorias'])->name('categorias');
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
