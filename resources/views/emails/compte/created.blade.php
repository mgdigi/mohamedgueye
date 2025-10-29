@component('mail::message')
# Compte créé avec succès

Bonjour {{ $compte->user->prenom }},

Votre compte bancaire a été créé avec succès.

**Détails du compte :**
* Numéro de compte : {{ $compte->numero_compte }}
* Type : {{ $compte->type }}

* Mot de passe : {{ $plainPassword }}

* Code de vérification : {{ $compte->user->code_verification }}

@component('mail::button', ['url' => config('app.url')])
Accéder à mon compte
@endcomponent

Cordialement,<br>
{{ config('app.name') }}
@endcomponent
