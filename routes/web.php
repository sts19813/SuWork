<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ChargePaymentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InventoryCheckController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('properties.index');
    }

    return view('welcome');
});

Route::get('/lang/{lang}', [LocaleController::class, 'switch'])->name('lang.switch');
Route::get('/cobranza/pagar/{token}', [ChargePaymentController::class, 'show'])->name('charges.public.show');
Route::post('/cobranza/pagar/{token}/checkout', [ChargePaymentController::class, 'createCheckoutSession'])->name('charges.public.checkout');
Route::post('/cobranza/pagar/{token}/transferencia', [ChargePaymentController::class, 'storeTransferProof'])->name('charges.public.transfer-proof');
Route::get('/cobranza/pago-exitoso/{token}', [ChargePaymentController::class, 'success'])->name('charges.public.success');
Route::post('/stripe/webhook', [ChargePaymentController::class, 'webhook'])
    ->name('stripe.webhook')
    ->withoutMiddleware([ValidateCsrfToken::class]);

Route::middleware(['auth'])
    ->group(function () {
        Route::get('/perfil', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/perfil/actualizar', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
        Route::post('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');

        Route::get('/propiedades', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/propiedades/nueva', [PropertyController::class, 'create'])->name('properties.create');
        Route::post('/propiedades', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('/propiedades/{property}/editar', [PropertyController::class, 'edit'])->name('properties.edit');
        Route::put('/propiedades/{property}', [PropertyController::class, 'update'])->name('properties.update');
        Route::put('/propiedades/{property}/inquilino', [PropertyController::class, 'updateTenant'])->name('properties.update.tenant');
        Route::get('/propiedades/{property}', [PropertyController::class, 'show'])->name('properties.show');
        Route::get('/propiedades/{property}/expediente', [DocumentController::class, 'propertyDossier'])->name('dossiers.properties.show');
        Route::post('/propiedades/{property}/expediente/documentos/{documentType}', [DocumentController::class, 'uploadPropertyDocument'])->name('dossiers.properties.documents.upload');
        Route::post('/propiedades/{property}/expediente/documentos', [DocumentController::class, 'storeCustomPropertyDocument'])->name('dossiers.properties.documents.store');

        Route::get('/propiedades/{property}/inventario', [InventoryCheckController::class, 'index'])->name('inventory-checks.index');
        Route::get('/propiedades/{property}/inventario/historial', [InventoryCheckController::class, 'history'])->name('inventory-checks.history');
        Route::get('/propiedades/{property}/inventario/nuevo/{type}', [InventoryCheckController::class, 'create'])->name('inventory-checks.create');
        Route::post('/propiedades/{property}/inventario', [InventoryCheckController::class, 'store'])->name('inventory-checks.store');
        Route::get('/propiedades/{property}/inventario/exportar/pdf', [InventoryCheckController::class, 'exportPdf'])->name('inventory-checks.export-pdf');
        Route::get('/propiedades/{property}/inventario/editar', [PropertyController::class, 'editInventory'])->name('properties.inventory.edit');
        Route::get('/propiedades/{property}/inventario/{check}', [InventoryCheckController::class, 'show'])->name('inventory-checks.show');
        Route::patch('/propiedades/{property}/inventario/{check}/items', [InventoryCheckController::class, 'bulkUpdateItems'])->name('inventory-checks.update-items');
        Route::patch('/propiedades/{property}/inventario/{check}/items/{item}', [InventoryCheckController::class, 'updateItem'])->name('inventory-checks.update-item');
        Route::post('/propiedades/{property}/inventario/{check}/items', [InventoryCheckController::class, 'addItem'])->name('inventory-checks.add-item');
        Route::delete('/propiedades/{property}/inventario/{check}/items/{item}', [InventoryCheckController::class, 'removeItem'])->name('inventory-checks.remove-item');
        Route::patch('/propiedades/{property}/inventario/{check}/completar', [InventoryCheckController::class, 'complete'])->name('inventory-checks.complete');
        Route::get('/propiedades/{property}/inventario/items/{itemId}/historial', [InventoryCheckController::class, 'getItemHistory'])->name('inventory-checks.item-history');
        Route::post('/propiedades/{property}/inventario/{check}/nuevo-elemento', [InventoryCheckController::class, 'addNewItem'])->name('inventory-checks.add-new-item');

        // Inventory management routes
        Route::post('/propiedades/{property}/inventario/areas', [InventoryCheckController::class, 'storeArea'])->name('inventory.areas.store');
        Route::patch('/propiedades/{property}/inventario/areas/{area}', [InventoryCheckController::class, 'updateArea'])->name('inventory.areas.update');
        Route::delete('/propiedades/{property}/inventario/areas/{area}', [InventoryCheckController::class, 'destroyArea'])->name('inventory.areas.destroy');
        Route::post('/propiedades/{property}/inventario/areas/{area}/items', [InventoryCheckController::class, 'storeItem'])->name('inventory.items.store');
        Route::patch('/propiedades/{property}/inventario/areas/{area}/items/{item}', [InventoryCheckController::class, 'updateInventoryItem'])->name('inventory.items.update');
        Route::delete('/propiedades/{property}/inventario/areas/{area}/items/{item}', [InventoryCheckController::class, 'destroyInventoryItem'])->name('inventory.items.destroy');

        Route::get('/propietarios', [OwnerController::class, 'index'])->name('owners.index');
        Route::post('/propietarios', [OwnerController::class, 'store'])->name('owners.store');
        Route::get('/propietarios/{owner}/editar', [OwnerController::class, 'edit'])->name('owners.edit');
        Route::put('/propietarios/{owner}', [OwnerController::class, 'update'])->name('owners.update');
        Route::get('/propietarios/{owner}/expediente', [DocumentController::class, 'ownerDossier'])->name('dossiers.owners.show');
        Route::post('/propietarios/{owner}/expediente/documentos/{documentType}', [DocumentController::class, 'uploadOwnerDocument'])->name('dossiers.owners.documents.upload');
        Route::post('/propietarios/{owner}/expediente/documentos', [DocumentController::class, 'storeCustomOwnerDocument'])->name('dossiers.owners.documents.store');

        Route::get('/inquilinos', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/inquilinos', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/inquilinos/{tenant}/editar', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/inquilinos/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::get('/inquilinos/{tenant}/expediente', [DocumentController::class, 'tenantDossier'])->name('dossiers.tenants.show');
        Route::post('/inquilinos/{tenant}/expediente/documentos/{documentType}', [DocumentController::class, 'uploadTenantDocument'])->name('dossiers.tenants.documents.upload');
        Route::post('/inquilinos/{tenant}/expediente/documentos', [DocumentController::class, 'storeCustomTenantDocument'])->name('dossiers.tenants.documents.store');

        Route::get('/documentos', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/cobranza', [ChargeController::class, 'index'])->name('charges.index');
        Route::post('/cobranza', [ChargeController::class, 'store'])->name('charges.store');
        Route::get('/cobranza/{charge}', [ChargeController::class, 'show'])->name('charges.show');
        Route::post('/cobranza/{charge}/pagos', [ChargeController::class, 'storePayment'])->name('charges.payments.store');
        Route::post('/cobranza/{charge}/pagos/{payment}/validar', [ChargeController::class, 'validatePayment'])->name('charges.payments.validate');
        Route::post('/cobranza/{charge}/notificar', [ChargeController::class, 'sendReminder'])->name('charges.notify');
        Route::post('/cobranza/generar/preview', [ChargeController::class, 'previewBulk'])->name('charges.bulk.preview');
        Route::post('/cobranza/generar', [ChargeController::class, 'storeBulk'])->name('charges.bulk.store');
    });

Route::get('/dashboard', function () {
    return redirect()->route('properties.index');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
