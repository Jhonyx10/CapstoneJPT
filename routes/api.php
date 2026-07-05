<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\AssignWorkerController;
use App\Http\Controllers\Api\RepairJobController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\Payment\PayMongoController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/paymongo/webhook', [PayMongoController::class, 'handleWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    Route::get('/reports/repairs', [ReportController::class, 'repairs']);
    Route::get('/reports/inventory', [ReportController::class, 'inventory']);
    Route::get('/reports/vehicles', [ReportController::class, 'vehicles']);
    Route::get('/reports/financial', [ReportController::class, 'financial']);

    Route::resource('/users', UserController::class);
    Route::resource('/vehicles', VehicleController::class);
    Route::resource('/services', ServiceController::class);
    Route::resource('/assign-workers', AssignWorkerController::class);
    Route::resource('/inventories', InventoryController::class);
    Route::resource('/invoices', InvoiceController::class);
    Route::resource('/categories', CategoryController::class);
    Route::resource('/transactions', TransactionController::class);

    Route::get('/workers', [AssignWorkerController::class, 'getWorkers']);
    Route::get('/repair-jobs', [RepairJobController::class, 'index']);
    Route::get('/repair-jobs/repair', [RepairJobController::class, 'getRepairJobs']);
    Route::get('/repair-jobs/history', [RepairJobController::class, 'getRepairHistory']);
    Route::get('/repair-jobs/customer/{id}', [RepairJobController::class, 'getCustomerRepairJobs']);
    Route::get('/repair-jobs/{id}', [RepairJobController::class, 'getRepairJob']);
    Route::get('/inventory/logs', [InventoryController::class, 'getInventoryLogs']);
    Route::get('/worker-types', [UserController::class, 'getWorkerTypes']);
    
    Route::post('/create/worker-type', [UserController::class, 'createWorkerType']);
    Route::post('/paymongo/checkout', [PayMongoController::class, 'createCheckoutSession']);
    Route::post('/paymongo/repair-checkout', [PayMongoController::class, 'createRepairDownPaymentCheckout']);
    Route::post('/paymongo/confirm', [PayMongoController::class, 'confirmCheckout']);
});
