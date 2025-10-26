<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ArchiveComptes;
use App\Jobs\DearchiveComptes;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        \App\Console\Commands\TestNeonConnection::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // À minuit
        $schedule->job(new ArchiveComptes)
                ->dailyAt('00:00')
                ->withoutOverlapping()
                ->onSuccess(function () {
                    \Log::info('Archivage des comptes réussi');
                })
                ->onFailure(function () {
                    \Log::error('Échec de l\'archivage des comptes');
                });
        
        // Toutes les heures pour le désarchivage
        $schedule->job(new DearchiveComptes)
                ->hourly()
                ->withoutOverlapping()
                ->onSuccess(function () {
                    \Log::info('Désarchivage des comptes réussi');
                })
                ->onFailure(function () {
                    \Log::error('Échec du désarchivage des comptes');
                });
    }

}