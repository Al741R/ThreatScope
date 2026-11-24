<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\ScannerController;
use Illuminate\Support\Facades\Route;

Route::apiResource('assets', AssetController::class);
Route::get('assets/{asset}/scan', [ScannerController::class, 'scanAsset']);
Route::get('scan', [ScannerController::class, 'lookup']);
