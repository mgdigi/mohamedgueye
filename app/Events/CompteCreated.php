<?php


namespace App\Events;

use App\Models\Compte;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Compte $compte
    ) {}
}