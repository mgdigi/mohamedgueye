<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Compte;
use Carbon\Carbon;

class AppliquerBlocages extends Command
{
    protected $signature = 'comptes:appliquer-blocages';
    protected $description = 'Met à jour le statut des comptes dont la date de blocage est atteinte';

    public function handle()
    {
        $aujourdhui = Carbon::now();

        $comptes = Compte::where('statut', '!=', 'bloque')
            ->whereDate('date_blocage', '<=', $aujourdhui)
            ->get();

        foreach ($comptes as $compte) {
            $compte->update(['statut' => 'bloque']);
            $this->info("Compte ID {$compte->id} bloqué automatiquement.");
        }

        return 0;
    }
}

