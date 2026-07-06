<?php

use Illuminate\Support\Facades\Route;

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
