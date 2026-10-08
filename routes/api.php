<?php

use App\Http\Controllers\Api\Internal\AirbnbIntegrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('internal/airbnb')
    ->middleware(['auth:sanctum', 'abilities:airbnb'])
    ->group(function (): void {
        Route::get('/properties', [AirbnbIntegrationController::class, 'availableProperties']);
        Route::post('/properties/{property:uuid}/claim', [AirbnbIntegrationController::class, 'claimProperty']);
        Route::post('/properties/{property:uuid}/release', [AirbnbIntegrationController::class, 'releaseProperty']);
        Route::post('/financial-events', [AirbnbIntegrationController::class, 'storeFinancialEvent']);
    });
