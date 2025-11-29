<?php

use App\Http\Controllers\Api\UserSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/api/users/search', [UserSearchController::class, 'search'])->name('api.users.search');
});
