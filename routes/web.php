<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index']);
Route::post('/contacts/confirm', [ContactController::class, 'confirm']);
Route::post('/contacts', [ContactController::class, 'store']);
Route::get('/thanks', [ContactController::class, 'thanks']);
Route::get('/admin', [AdminController::class, 'index'])->middleware('auth');
Route::get('/admin/contacts/csv', [AdminController::class, 'exportCsv']);
Route::get('/admin/contacts/{contact}', [AdminController::class, 'show']);
Route::get('/admin/tags/{tag}/edit', [TagController::class, 'edit']);
Route::put('/admin/tags/{tag}', [TagController::class, 'update']);
Route::post('/admin/tags', [TagController::class, 'store']);
Route::delete('/admin/tags/{tag}', [TagController::class, 'destroy']);
Route::delete('/admin/contacts/{contact}', [AdminController::class, 'destroy']);
Route::get('/contacts/export', [ContactController::class, 'export']);
