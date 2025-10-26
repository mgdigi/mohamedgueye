<?php


namespace App\Providers;

use App\Models\Compte;
use App\Observers\CompteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Compte::observe(CompteObserver::class);
    }
}