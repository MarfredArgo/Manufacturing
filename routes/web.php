<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManufacturingController;

Route::get('/manufacturing', [ManufacturingController::class, 'index']);
Route::post('/manufacturing/update-order', [ManufacturingController::class, 'updateOrder']);
Route::post('/manufacturing/update-qc', [ManufacturingController::class, 'updateQC']);

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
