<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect('/chat');
});

Route::get('/chat', function (Request $request) {
    $html = file_get_contents(base_path('public/chat.html'));
    return response($html, 200, ['Content-Type' => 'text/html']);
});

Route::get('/admin', function (Request $request) {
    $html = file_get_contents(base_path('public/admin.html'));
    return response($html, 200, ['Content-Type' => 'text/html']);
});