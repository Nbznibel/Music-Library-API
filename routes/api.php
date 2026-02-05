<?php

use App\Src\music\song\Ajax\SongController;
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
Route::prefix('songs')->group(function () {
    Route::get('/',        [SongController::class, 'fnc_list']);
    Route::get('{id}',     [SongController::class, 'fnc_get']);
    Route::post('/',       [SongController::class, 'fnc_create']);
    Route::put('{id}',     [SongController::class, 'fnc_update']);
    Route::delete('{id}',  [SongController::class, 'fnc_delete']);
});
