<?php

namespace App\Mail;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompteCreated extends Mailable
{
    use Queueable, SerializesModels;
    
    public $plainPassword;

    public function __construct(
        public Compte $compte,
        $plainPassword = null
    ) {
        $this->plainPassword = $plainPassword;

    }

    public function build()
    {
        return $this->markdown('emails.compte.created')
                    ->subject('Votre compte  a été créé avec succes !')
                    ->with([
                        'compte' => $this->compte,
                        'plainPassword' => $this->plainPassword,
                    ]);
                    
    }
}