<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatController::class, 'index'])->name('chat.index');
Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');

// L'unica rotta POST originaria per l'invio sincrono di form completi
Route::post('/chat/send/{id?}', [ChatController::class, 'send'])->name('chat.send');
// cancellazione e rinomina chat
Route::put('/chat/{id}', [ChatController::class, 'update'])->name('chat.update');
Route::delete('/chat/{id}', [ChatController::class, 'destroy'])->name('chat.destroy');

// ROTTA PER SALVARE LA CONFIGURAZIONE DEL MODELLO E DELLE API KEY
Route::post('/chat/config', [ChatController::class, 'saveConfig'])->name('chat.saveConfig');
