<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ChargeController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\ContractDocumentController;
use App\Http\Controllers\Admin\ContractInspectionPhotoController;
use App\Http\Controllers\Admin\ContractOccurrenceController;
use App\Http\Controllers\Admin\ContractTemplateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\OwnerController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\RateioController;
use App\Http\Controllers\Admin\ReceiverController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\ContractShowController;
use App\Http\Controllers\Portal\ReceiverPortalController;
use App\Http\Controllers\Portal\TenantPortalController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = Auth::user();

    return $user instanceof User
        ? redirect()->route($user->homeRouteName())
        : redirect()->route('login');
})->name('home');

Route::post('webhooks/mercadopago', [MercadoPagoWebhookController::class, 'store'])
    ->name('webhooks.mercadopago');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin')
        ->name('dashboard');

    Route::middleware('role:admin')->name('admin.')->group(function () {
        Route::resource('owners', OwnerController::class)->except(['show']);
        Route::resource('properties', PropertyController::class)->except(['show']);
        Route::resource('tenants', TenantController::class)->except(['show']);
        Route::resource('receivers', ReceiverController::class)->except(['show']);
        Route::get('receivers/{receiver}/mercadopago/connect', [ReceiverController::class, 'connectMercadoPago'])
            ->name('receivers.mercadopago.connect');
        Route::get('receivers/mercadopago/callback', [ReceiverController::class, 'mercadoPagoCallback'])
            ->name('receivers.mercadopago.callback');
        Route::post('receivers/{receiver}/mercadopago/disconnect', [ReceiverController::class, 'disconnectMercadoPago'])
            ->name('receivers.mercadopago.disconnect');
        Route::resource('admins', AdminUserController::class)->except(['show']);
        Route::resource('contracts', ContractController::class);
        Route::post('contracts/{contract}/witnesses', [ContractController::class, 'attachWitness'])
            ->name('contracts.witnesses.attach');
        Route::post('contracts/{contract}/owner-sign', [ContractController::class, 'markOwnerSigned'])
            ->name('contracts.owner-sign');
        Route::post('contracts/{contract}/witnesses/{witness}/sign', [ContractController::class, 'markWitnessSigned'])
            ->name('contracts.witnesses.sign');
        Route::resource('templates', ContractTemplateController::class)->except(['show']);
        Route::get('charges', [ChargeController::class, 'index'])->name('charges.index');
        Route::post('contracts/{contract}/charges/generate', [ChargeController::class, 'generate'])
            ->name('charges.generate');
        Route::post('charges/{charge}/reminder', [ChargeController::class, 'sendReminder'])
            ->name('charges.reminder');
        Route::get('deposits', [DepositController::class, 'index'])->name('deposits.index');
        Route::post('deposits', [DepositController::class, 'store'])->name('deposits.store');
        Route::put('deposits/{deposit}', [DepositController::class, 'update'])->name('deposits.update');
        Route::delete('deposits/{deposit}', [DepositController::class, 'destroy'])->name('deposits.destroy');
        Route::post('deposits/{deposit}/refund', [DepositController::class, 'markRefunded'])->name('deposits.refund');
        Route::get('rateios', [RateioController::class, 'index'])->name('rateios.index');
        Route::post('rateios', [RateioController::class, 'store'])->name('rateios.store');
        Route::put('rateios/{rateio}', [RateioController::class, 'update'])->name('rateios.update');
        Route::delete('rateios/{rateio}', [RateioController::class, 'destroy'])->name('rateios.destroy');
        Route::get('rateios/{rateio}/invoice', [RateioController::class, 'invoice'])->name('rateios.invoice');
        Route::get('integracoes', [IntegrationController::class, 'index'])->name('integrations.index');
        Route::get('help/search', [HelpController::class, 'search'])->name('help.search');
        Route::get('occurrences', [ContractOccurrenceController::class, 'index'])->name('occurrences.index');
        Route::patch('occurrences/{occurrence}', [ContractOccurrenceController::class, 'update'])
            ->name('occurrences.update');
        Route::post('contracts/{contract}/inspection-photos', [ContractInspectionPhotoController::class, 'store'])
            ->name('contracts.inspection-photos.store');
        Route::get('contracts/{contract}/inspection-photos/{photo}', [ContractInspectionPhotoController::class, 'show'])
            ->name('contracts.inspection-photos.show');
        Route::delete('contracts/{contract}/inspection-photos/{photo}', [ContractInspectionPhotoController::class, 'destroy'])
            ->name('contracts.inspection-photos.destroy');
        Route::post('contracts/{contract}/document/generate', [ContractDocumentController::class, 'generate'])
            ->name('contracts.document.generate');
        Route::post('contracts/{contract}/document/review', [ContractDocumentController::class, 'review'])
            ->name('contracts.document.review');
        Route::post('contracts/{contract}/document/upload-owner-signed', [ContractDocumentController::class, 'uploadOwnerSigned'])
            ->name('contracts.document.upload-owner-signed');
        Route::get('contracts/{contract}/document/owner-signed', [ContractDocumentController::class, 'downloadOwnerSigned'])
            ->name('contracts.document.owner-signed');
    });

    Route::middleware('role:admin|tenant')->group(function () {
        Route::post('charges/{charge}/pix', [ChargeController::class, 'createPix'])->name('charges.pix');
        Route::post('charges/{charge}/sync', [ChargeController::class, 'syncPayment'])->name('charges.sync');
        Route::post('charges/{charge}/sync-payment', [ChargeController::class, 'syncPayment'])
            ->name('charges.sync-payment');
        Route::post('deposits/{deposit}/pix', [DepositController::class, 'createPix'])->name('deposits.pix');
        Route::post('deposits/{deposit}/sync', [DepositController::class, 'syncPayment'])->name('deposits.sync');
        Route::post('contracts/{contract}/document/upload-signed', [ContractDocumentController::class, 'uploadSigned'])
            ->name('contracts.document.upload-signed');
        Route::get('contracts/{contract}/document/generated', [ContractDocumentController::class, 'downloadGenerated'])
            ->name('contracts.document.generated');
        Route::get('contracts/{contract}/document/signed', [ContractDocumentController::class, 'downloadSigned'])
            ->name('contracts.document.signed');
        Route::get('occurrences/photos/{photo}', [ContractOccurrenceController::class, 'showPhoto'])
            ->name('occurrences.photos.show');
    });

    Route::middleware('role:admin|tenant|receiver')->group(function () {
        Route::get('charges/{charge}/receipt', [ChargeController::class, 'receipt'])->name('charges.receipt');
    });

    Route::middleware('role:tenant')->group(function () {
        Route::get('inquilino', [TenantPortalController::class, 'index'])->name('tenant.portal');
        Route::post('occurrences', [ContractOccurrenceController::class, 'store'])->name('occurrences.store');
    });

    Route::middleware('role:receiver')->group(function () {
        Route::get('recebedor', [ReceiverPortalController::class, 'index'])->name('receiver.portal');
    });

    Route::get('contrato/{contract}', [ContractShowController::class, 'show'])->name('contracts.show');
});

require __DIR__.'/settings.php';
