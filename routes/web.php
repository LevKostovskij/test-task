<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/tasks');
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
