<?php

use App\Http\Controllers\Api\Designs;
use App\Http\Controllers\Api\Qualities;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\Psi\Api\ShapeController;
use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/designs', Designs\Index::class)->name('designs.index');
Route::get('/qualities', Qualities\Index::class)->name('qualities.index');
Route::get('/suppliers', SupplierController::class)->name('suppliers');
Route::get('/shapes', ShapeController::class)->name('psi.shapes');
