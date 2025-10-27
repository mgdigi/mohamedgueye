<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Compte\CompteController;

use App\Http\Controllers\AuthController;

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



Route::prefix("v1")->group(function () {

    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    Route::post('auth/logout', [AuthController::class, 'logout']);


  Route::middleware('auth:api')->group(function () {
    Route::get("/comptes", [CompteController::class, "index"]);
    Route::get("/comptes/{compte}", [CompteController::class, "show"]);
    Route::post("/comptes", [CompteController::class, "store"]);
    Route::post("/comptes/{compte}/bloquer", [CompteController::class, "bloquer"]);
    Route::post("/comptes/{compte}/debloquer", [CompteController::class, "debloquer"]);

    Route::delete("/comptes/{id}", [CompteController::class, "destroy"]);
    });
 
});


