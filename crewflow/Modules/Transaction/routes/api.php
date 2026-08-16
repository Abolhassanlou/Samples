<?php

use Illuminate\Support\Facades\Route;
use Modules\Transaction\Http\Controllers\Api\RecurringBillingProfileController;
use Modules\Transaction\Http\Controllers\Api\TransactionController;

Route::middleware(['auth:sanctum', 'permission:clients.manage'])->group(function () {
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::post('transactions/{transaction}/mark-paid', [TransactionController::class, 'markPaid']);

    Route::get('recurring-billing-profiles', [RecurringBillingProfileController::class, 'index']);
    Route::post('recurring-billing-profiles', [RecurringBillingProfileController::class, 'store']);
    Route::put('recurring-billing-profiles/{recurringBillingProfile}', [RecurringBillingProfileController::class, 'update']);
    Route::delete('recurring-billing-profiles/{recurringBillingProfile}', [RecurringBillingProfileController::class, 'destroy']);
});
