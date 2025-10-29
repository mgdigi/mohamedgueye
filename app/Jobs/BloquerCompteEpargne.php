<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BloquerCompteEpargne implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $compte;
    protected $jours;

    public function __construct(Compte $compte, int $jours)
    {
        $this->compte = $compte;
        $this->jours = $jours;
    }

    public function handle(): void
    {
        if($this->compte->status === 'actif' && $this->compte->type_compte === 'epargne') {
            $this->compte->blocage_debut = now();
            $this->compte->date_fin_blocage = now()->addDays($this->jours);
            $this->compte->is_bloqued = true;
            $this->compte->save();
        }
    }
}
