<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManufacturingController;
<<<<<<< HEAD
=======
use App\Http\Controllers\AuthController;

// Auth
Route::post('/login',  [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
>>>>>>> 0bbec8f2458e19f74de04b6c5913c55f6e74300d

// Work orders
Route::post('/manufacturing/update-order',       [ManufacturingController::class, 'updateOrder']);

// QC benchmark
Route::post('/manufacturing/update-qc',          [ManufacturingController::class, 'updateQC']);

// Rework
Route::post('/manufacturing/update-rework',      [ManufacturingController::class, 'updateRework']);
Route::post('/manufacturing/add-rework-part',    [ManufacturingController::class, 'addReworkPart']);
Route::post('/manufacturing/update-rework-part', [ManufacturingController::class, 'updateReworkPart']);

// Analytics
Route::post('/manufacturing/add-qc-note',        [ManufacturingController::class, 'addQcNote']);

// Workers
Route::post('/manufacturing/update-worker',      [ManufacturingController::class, 'updateWorker']);
Route::post('/manufacturing/delete-worker',      [ManufacturingController::class, 'deleteWorker']);
Route::post('/workorder/assignment',             [ManufacturingController::class, 'addWorker']);
Route::post('/workorder/assign-worker',          [ManufacturingController::class, 'assignWorker']);

// Pages
Route::get('/',              fn() => view('Signin'));
Route::get('/manufacturing', [ManufacturingController::class, 'index']);
Route::get('/welcome',       fn() => view('welcome'));
Route::get('/contactus',     fn() => view('Contactus'));
Route::get('/signin',        fn() => view('Signin'));
