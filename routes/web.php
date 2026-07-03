<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatController::class, 'index'])->name('chat.index');
Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');

// L'unica rotta POST originaria per l'invio sincrono di form completi
Route::post('/chat/send/{id?}', [ChatController::class, 'send'])->name('chat.send');
