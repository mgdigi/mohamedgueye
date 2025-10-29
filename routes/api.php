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
    Route::post("/comptes", [CompteController::class, "store"])->middleware('logging');
    Route::post("/comptes/{compte}/bloquer", [CompteController::class, "bloquer"]);

    Route::delete("/comptes/{id}", [CompteController::class, "destroy"]);
    Route::patch('/comptes/{compte}', [CompteController::class, 'update'])->middleware('logging');
    });
 
});


Route::get('/health', function () {
       return response()->json([
           'status' => 'ok',
           'db' => DB::connection()->getPdo() ? 'connected' : 'failed'
       ]);
   });

Route::get('/passport-check', function () {
    try {
        $privateKeyExists = file_exists(storage_path('oauth-private.key'));
        $publicKeyExists = file_exists(storage_path('oauth-public.key'));
        
        return response()->json([
            'passport_installed' => class_exists('Laravel\Passport\Passport'),
            'private_key_exists' => $privateKeyExists,
            'public_key_exists' => $publicKeyExists,
            'storage_writable' => is_writable(storage_path()),
            'clients_count' => DB::table('oauth_clients')->count(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});