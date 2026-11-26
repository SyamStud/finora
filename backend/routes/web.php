<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Middleware auth butuh named route "login" saat menolak request yang tidak
// terautentikasi. API punya handler JSON sendiri, jadi cukup kembalikan 401.
Route::get('/login', function () {
    return response()->json(['message' => 'Autentikasi diperlukan.'], 401);
})->name('login');
