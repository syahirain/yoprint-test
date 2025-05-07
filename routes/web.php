<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\SSEController;
use App\Models\File;

use Illuminate\Support\Facades\Redis;

Route::get('/redis-test', function () {
    Redis::set('test', 'Hello from Docker Redis!');
    return Redis::get('test');
});

Route::post('/upload', [FileUploadController::class, 'uploadChunk']);

Route::get('/data', [SSEController::class, 'streamFileData']);

Route::get('/', function () {
    $files = File::all();
    return view('home', compact('files'));
});
