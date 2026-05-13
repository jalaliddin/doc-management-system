<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\SignatoryController;
use Illuminate\Support\Facades\Route;

// ===== Ochiq endpointlar =====
Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/departments/{department}', [DepartmentController::class, 'show']);

Route::get('/organizations', [OrganizationController::class, 'index']);
Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
Route::get('/organizations/{organization}/leaders', [OrganizationController::class, 'leaders']);

Route::get('/signatories', [SignatoryController::class, 'index']);

Route::post('/documents/generate', [DocumentController::class, 'generate']);

// ===== Admin Auth =====
Route::post('/admin/login', [AuthController::class, 'login']);

// ===== Admin (himoyalangan) =====
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Departments CRUD
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);

    // Organizations CRUD
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);

    // Organization Leaders CRUD
    Route::post('/organizations/{organization}/leaders', [OrganizationController::class, 'storeLeader']);
    Route::put('/organizations/{organization}/leaders/{leader}', [OrganizationController::class, 'updateLeader']);
    Route::delete('/organizations/{organization}/leaders/{leader}', [OrganizationController::class, 'destroyLeader']);

    // Signatories CRUD
    Route::post('/signatories', [SignatoryController::class, 'store']);
    Route::put('/signatories/{signatory}', [SignatoryController::class, 'update']);
    Route::delete('/signatories/{signatory}', [SignatoryController::class, 'destroy']);
});
