<?php


namespace App\Observers;

use App\Models\Compte;
use App\Mail\CompteCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
// use Twilio\Rest\Client as TwilioClient;

class CompteObserver
{
    public function created(Compte $compte): void
{
    try {
        $compte->load('user');

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $plainPassword = $compte->user->password_temporaire ?? null;

        \Log::info('Mot de passe temporaire dans observer:', [
            'plainPassword' => $plainPassword,
            'user_id' => $compte->user->id
        ]);

        try {
            $twilioClient = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilioClient->messages->create(
                '+221' . ltrim($compte->user->telephone, '0'),
                [
                    'from' => config('services.twilio.phone'),
                    'body' => "Votre code de vérification est : {$code}"
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Erreur envoi SMS:', ['error' => $e->getMessage()]);
        }

        try {
            \Mail::to($compte->user->email)
                ->send(new CompteCreated($compte, $plainPassword));

            \Log::info('Email envoyé avec succès');
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email:', ['error' => $e->getMessage()]);
        }

        $compte->update([
            'code_verification' => $code,
            'code_expire_at' => now()->addHours(24)
        ]);

    } catch (\Exception $e) {
        \Log::error('Erreur générale dans l\'Observer:', [
            'error' => $e->getMessage(),
            'compte_id' => $compte->id
        ]);
    }
}

}