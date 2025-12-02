<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'MonBeauPays.com API',
        'version' => '1.0.0',
    ]);
});

