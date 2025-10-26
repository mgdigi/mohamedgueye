<?php


namespace App\Listeners;

use App\Events\CompteCreated;
use Illuminate\Support\Facades\Mail;
use App\Mail\CompteCreatedMail;

class SendClientNotification
{
    public function handle(CompteCreated $event): void
    {
        // Envoyer l'email
        Mail::to($event->compte->user->email)
            ->send(new CompteCreatedMail($event->compte, $event->password));

        // TODO: Implémenter l'envoi SMS avec le code
        // SMS::send($event->compte->user->telephone, $event->code);
    }
}