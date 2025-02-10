<?php

use App\Http\Controllers\TesteCapitaniController;
use Illuminate\Support\Facades\Route;

Route::get('/consulta-demanda', [TesteCapitaniController::class, 'index'])->name('consulta-demanda.index');
Route::post('/consulta-demanda', [TesteCapitaniController::class, 'store'])->name('consulta-demanda.store');
Route::put('/consulta-demanda/{codigo}', [TesteCapitaniController::class, 'update'])->name('consulta-demanda.update');
Route::delete('/consulta-demanda/{codigo}', [TesteCapitaniController::class, 'destroy'])->name('consulta-demanda.destroy');
