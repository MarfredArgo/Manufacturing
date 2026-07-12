<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManufacturingController;

Route::post('/manufacturing/update-order', [ManufacturingController::class, 'updateOrder']);
Route::post('/manufacturing/update-qc', [ManufacturingController::class, 'updateQC']);
Route::post('/manufacturing/update-worker', [ManufacturingController::class, 'updateWorker']);
Route::post('/manufacturing/delete-worker', [ManufacturingController::class, 'deleteWorker']);
Route::post('/workorder/assignment', [ManufacturingController::class, 'addWorker']);
Route::post('/workorder/assign-worker', [ManufacturingController::class, 'assignWorker']);

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/manufacturing', function () {
    return view('Manufacturing');
});

Route::get('/contactus', function () {
    return view('Contactus');
});

Route::get('/signin', function () {
    return view('Signin');
});
