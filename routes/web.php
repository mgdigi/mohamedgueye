<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



Route::get('/test-mail', function () {
    try {
        \Mail::raw('Test email de Laravel', function($message) {
            $message->to('gueyemohamed287@gmail.com')
                   ->subject('Test Laravel Mail');
        });
        return 'Email envoyé avec succès !';
    } catch (\Exception $e) {
        \Log::error('Erreur mail : ' . $e->getMessage());
        return 'Erreur : ' . $e->getMessage();
    }
});