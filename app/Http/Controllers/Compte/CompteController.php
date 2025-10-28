<?php

namespace App\Http\Controllers\Compte;

use App\Exceptions\DatabaseQueryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BloqueCompteRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Resources\CompteRessource;
use App\Http\Resources\DebloqueRessource;
use App\Http\Resources\MetaRessource;
use Illuminate\Http\Request;
use App\Models\Compte;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;


use App\Exceptions\CreateFailedException;
use App\Exceptions\CompteNotFoundException;
use App\Http\Resources\BloqueRessource;
use App\Models\User;
use App\Http\Requests\UpdateCompteRequest;


/**
 * @OA\Info(
 *     title="API de Gestion des Comptes Bancaires",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires avec authentification Passport et archivage automatique.

## 🏦 Fonctionnalités Principales

### Gestion des Comptes
- Création de comptes bancaires avec validation stricte
- Authentification via Passport (tokens JWT)
- Gestion des rôles (Admin/Client)
- Validation CNI sénégalaise personnalisée

### Archivage Automatique
Le système inclut un mécanisme d'archivage automatique des comptes via des Jobs Laravel :

#### 🔄 Job ArchiveComptes
- **Déclenchement** : Automatique via scheduler Laravel
- **Condition** : Comptes bloqués dont la date de fin de blocage est échue
- **Action** : Archive le compte et ses transactions dans la base Neon
- **Processus** :
  1. Recherche des comptes avec `date_blocage <= now()` et `archived = false`
  2. Sauvegarde des données JSON dans `comptes_archives` et `transactions_archives`
  3. Marquage du compte comme archivé (`archived = true`)
  4. Suppression des données de la base principale

#### 🔄 Job DearchiveComptes
- **Déclenchement** : Automatique via scheduler Laravel
- **Condition** : Comptes archivés dont la date de fin de blocage est échue
- **Action** : Restaure le compte et ses transactions depuis la base Neon
- **Processus** :
  1. Recherche dans `comptes_archives` avec `date_fin_blocage <= now()`
  2. Recréation du compte et des transactions dans la base principale
  3. Suppression des archives

#### 📊 Base de Données d'Archivage
- **Connexion** : Base de données Neon (PostgreSQL)
- **Tables** :
  - `comptes_archives` : Stockage JSON des comptes
  - `transactions_archives` : Stockage JSON des transactions
- **Format** : Données sérialisées en JSON pour préservation complète

#### ⚙️ Configuration
Les jobs peuvent être configurés dans `app/Console/Kernel.php` :
```php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new ArchiveComptes)->daily();
    $schedule->job(new DearchiveComptes)->daily();
}
```

#### 📱 Notifications Automatiques
Lors de la création d'un compte :
- Envoi de SMS via Twilio avec code de vérification
- Envoi d'email avec détails du compte
- Génération automatique de numéro de compte unique

### Sécurité
- Authentification Bearer obligatoire pour tous les endpoints
- Autorisation basée sur les rôles utilisateur
- Validation stricte des données d'entrée
- Logs détaillés des opérations"
 *     description="API pour la gestion des comptes bancaires avec authentification Passport"

/**
 * @OA\Info(
 *     title="API de Gestion des Comptes",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 * @OA\Server(
 *     url="https://ges-compte-laravel.onrender.com/api/v1",
 *     description="Serveur de production"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",

 *     bearerFormat="JWT",
 *     description="Token d'accès Bearer généré par Passport"
 *     bearerFormat="JWT"
 * )
 * @OA\PathItem(
 *     path="/api/v1/comptes"
 * )
 */

/**
 * @OA\Tag(
 *     name="Archivage",
 *     description="Système d'archivage automatique des comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister les comptes",
     *     description="Récupère la liste des comptes avec pagination et filtres",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type de compte (epargne, cheque)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Statut du compte",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro de compte",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"}, default="dateCreation")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Compte")
     *                 ),
     *                 @OA\Property(property="meta", ref="#/components/schemas/Meta")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur interne du serveur")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            \Log::info('Début de la méthode index comptes', [
                'user_id' => Auth::id(),
                'request_params' => $request->all()
            ]);

            $user = Auth::user();

            if (!$user) {
                \Log::error('Utilisateur non authentifié');
                return $this->errorResponse('Non autorisé', 401);
            }

            $cacheKey =  'comptes_' . md5(json_encode($request->all()));

            $cacheData = Cache::get($cacheKey);

            // if ($cacheData) {
            //     return $this->successResponse($cacheData);
            // }

            \Log::info('Tentative de récupération des comptes filtrés');

            $comptes = Compte::filtrerComptes($request->all(), $user)
                ->paginate(min($request->get('limit', 10), 100))
                ->appends($request->all());

            \Log::info('Comptes récupérés', ['count' => $comptes->count()]);

            $data = [
                'data' => CompteRessource::collection($comptes),
                'meta' => new MetaRessource($comptes)
            ];

            Cache::put($cacheKey, CompteRessource::collection($comptes), now()->addMinutes(10));

            \Log::info('Réponse préparée avec succès');

            return $this->successResponse($data, 'comptes recuperer avec succes ! ',$comptes->total(), $user->id, $user->isAdmin());

        } catch (\Exception $e) {
            \Log::error('Erreur index comptes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_params' => $request->all()
            ]);
            return $this->errorResponse('Erreur lors de la récupération des comptes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes",
     *     summary="Créer un nouveau compte bancaire",
     *     description="Crée un nouveau compte bancaire avec un utilisateur client. Réservé aux administrateurs.",
     *     operationId="createCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","solde","devise","user"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne", description="Type de compte"),
     *             @OA\Property(property="solde", type="number", format="float", minimum=10000, example=50000, description="Solde initial (minimum 10 000)"),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA", description="Devise du compte"),
     *             @OA\Property(property="user", type="object", description="Informations de l'utilisateur",
     *                 required={"nom","prenom","password","email","telephone","nci","adresse"},
     *                 @OA\Property(property="nom", type="string", maxLength=255, example="Doe", description="Nom de l'utilisateur"),
     *                 @OA\Property(property="prenom", type="string", maxLength=255, example="John", description="Prénom de l'utilisateur"),
     *                 @OA\Property(property="password", type="string", minLength=6, example="password123", description="Mot de passe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Adresse email unique"),
     *                 @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone unique (format sénégalais)"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123", description="Numéro CNI valide (13 chiffres)"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal", description="Adresse de l'utilisateur")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
     *             @OA\Property(property="total", type="integer", example=1),
     *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="is_admin", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé - Réservé aux administrateurs",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="solde", type="array",
     *                     @OA\Items(type="string", example="Le solde initial doit être supérieur ou égal à 10 000 FCFA.")
     *                 ),
     *                 @OA\Property(property="user.email", type="array",
     *                     @OA\Items(type="string", example="Cet email est déjà utilisé.")
     *                 ),
     *                 @OA\Property(property="user.nci", type="array",
     *                     @OA\Items(type="string", example="Le numéro CNI doit être valide (13 chiffres, commence par 1 ou 2, et contient une date valide).")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la création du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création du compte: Erreur de base de données")
     *         )
     *     )
     * )
     */
    public function store(StoreCompteRequest $request)
{
    try {
        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            return $this->errorResponse("Non autorisé reserve aux admins ", 401);
        }

        $validated = $request->validated();
        
        $compte = Compte::createCompteWithUser(
            $validated['user'],
            $validated
        );

        return $this->successResponse(
            new CompteRessource($compte),
            'Compte créé avec succès',
            1,
            $user->id,
            $user->isAdmin(),
            201
        );

    } catch (CreateFailedException $e) {
        return $this->errorResponse(
            "Erreur lors de la création du compte: " . $e->getMessage(),
            500
        );
    }
}

   /**
    * @OA\Get(
    *     path="/comptes/{compte}",
    *     summary="Récupérer un compte spécifique",
    *     description="Récupère les détails d'un compte bancaire spécifique. Les clients ne peuvent voir que leurs propres comptes, les administrateurs peuvent voir tous les comptes.",
    *     operationId="getCompte",
    *     tags={"Comptes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="compte",
    *         in="path",
    *         required=true,
    *         description="ID du compte à récupérer",
    *         @OA\Schema(type="string", format="uuid")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Compte récupéré avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
    *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
    *             @OA\Property(property="total", type="integer", example=1),
    *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
    *             @OA\Property(property="is_admin", type="boolean", example=false)
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Non autorisé",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Non autorisé")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Compte introuvable",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Compte introuvable.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Erreur serveur",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Erreur interne du serveur")
    *         )
    *     )
    * )
    */
   public function show(Request $request, $id)
   {
       $user = Auth::user();

       if($user->isAdmin()){
           $compte = Compte::find($id);
       }else{
           $compte = Compte::where('id', $id)->where('user_id', $user->id)->first();
       }

       if(!$compte) {
               $compte = Compte::on('neon')->find($id);
               if(!$compte) {
                   throw new CompteNotFoundException("Compte introuvable.");
               }
           }


       return $this->successResponse(new CompteRessource($compte), 'Compte récupéré avec succès', 1, $user->id, $user->isAdmin());
   }

   /**
    * @OA\Post(
    *     path="/comptes/{compte}/bloquer",
    *     summary="Bloquer un compte bancaire",
    *     description="Bloque un compte bancaire avec une durée et un motif spécifiés. Réservé aux administrateurs.",
    *     operationId="bloquerCompte",
    *     tags={"Comptes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="compte",
    *         in="path",
    *         required=true,
    *         description="ID du compte à bloquer",
    *         @OA\Schema(type="string", format="uuid")
    *     ),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"jours_blocage","date_blocage","motif_blocage"},
    *             @OA\Property(property="jours_blocage", type="integer", minimum=1, maximum=365, example=30, description="Nombre de jours de blocage"),
    *             @OA\Property(property="date_blocage", type="string", format="date", example="2025-11-01", description="Date de début du blocage"),
    *             @OA\Property(property="motif_blocage", type="string", maxLength=255, example="Suspicion de fraude", description="Motif du blocage")
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Compte bloqué avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
    *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
    *             @OA\Property(property="total", type="integer", example=1),
    *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
    *             @OA\Property(property="is_admin", type="boolean", example=true)
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Le compte ne peut pas être bloqué dans son état actuel",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Le compte ne peut pas être bloqué dans son état actuel.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Non autorisé - Réservé aux administrateurs",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Non autorisé")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Compte introuvable",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Compte introuvable.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=422,
    *         description="Erreur de validation",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="The given data was invalid."),
    *             @OA\Property(property="errors", type="object",
    *                 @OA\Property(property="jours_blocage", type="array",
    *                     @OA\Items(type="string", example="Le nombre de jours de blocage doit être au moins de 1 jour.")
    *                 ),
    *                 @OA\Property(property="motif_blocage", type="array",
    *                     @OA\Items(type="string", example="Le motif de blocage est obligatoire.")
    *                 )
    *             )
    *         )
    *     )
    * )
    */
   public function bloquer(BloqueCompteRequest $request, $id)
   {
       $user = Auth::user();
       $validated = $request->validated();

       if(!$user->isAdmin()) {
           return $this->errorResponse("Non autorisé reserve aux admins ", 401);
       }

       $compte = Compte::find($id);

       if(!$compte) {
           throw new CompteNotFoundException("Compte introuvable.");
       }

       if($compte->statut !== 'actif' && $compte->statut !== 'epargne') {
           return $this->errorResponse("Le compte ne peut pas être bloqué il est deja en etat bloqué .", 400);
       }

       $compte->statut = 'bloque';
       $compte->motif_blocage = $validated['motif_blocage'];
       $compte->date_blocage = $validated['date_blocage'];
       $compte->date_fin_blocage = \Carbon\Carbon::parse($validated['date_blocage'])->addDays($validated['jours_blocage']);
       $compte->save();

       return $this->successResponse(new BloqueRessource($compte), 'Compte bloqué avec succès', 1, $user->id, $user->isAdmin());
   }


   /**
    * @OA\Post(
    *     path="/comptes/{compte}/debloquer",
    *     summary="Débloquer un compte bancaire",
    *     description="Débloque un compte bancaire précédemment bloqué. Réservé aux administrateurs.",
    *     operationId="debloquerCompte",
    *     tags={"Comptes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="compte",
    *         in="path",
    *         required=true,
    *         description="ID du compte à débloquer",
    *         @OA\Schema(type="string", format="uuid")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Compte débloqué avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
    *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
    *             @OA\Property(property="total", type="integer", example=1),
    *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
    *             @OA\Property(property="is_admin", type="boolean", example=true)
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Le compte n'est pas bloqué",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Le compte n'est pas bloqué.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Non autorisé - Réservé aux administrateurs",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Non autorisé")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Compte introuvable",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Compte introuvable.")
    *         )
    *     )
    * )
    */
   public function debloquer(Request $request, $id)
   {
       $user = Auth::user();

       if(!$user->isAdmin()) {
           return $this->errorResponse("Non autorisé reserve aux admins ", 401);
       }

       $compte = Compte::find($id);

       if(!$compte) {
           throw new CompteNotFoundException("Compte introuvable.");
       }

       if($compte->statut !== 'bloque') {
           return $this->errorResponse("Le compte n'est pas bloqué.", 400);
       }

       $compte->statut = 'actif';
       $compte->date_fin_blocage = now();
       $compte->save();

       return $this->successResponse(new DebloqueRessource($compte), 'Compte débloqué avec succès', 1, $user->id, $user->isAdmin());
   }

   /**
    * @OA\Patch(
    *     path="/comptes/{compte}",
    *     summary="Mettre à jour les informations d'un compte bancaire",
    *     description="Met à jour les informations d'un compte bancaire et de son client. Tous les champs sont optionnels mais au moins un champ doit être modifié. Réservé aux administrateurs.",
    *     operationId="updateCompte",
    *     tags={"Comptes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="compte",
    *         in="path",
    *         required=true,
    *         description="ID du compte à mettre à jour",
    *         @OA\Schema(type="string", format="uuid")
    *     ),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             @OA\Property(property="titulaire", type="string", maxLength=150, example="John Doe", description="Nom du titulaire du compte (optionnel)"),
    *             @OA\Property(property="informationsClient", type="object", description="Informations du client à mettre à jour (optionnel)",
    *                 @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone unique (format sénégalais)"),
    *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Adresse email unique"),
    *                 @OA\Property(property="password", type="string", minLength=6, example="newpassword123", description="Nouveau mot de passe"),
    *                 @OA\Property(property="nci", type="string", example="1234567890123", description="Numéro CNI valide (13 chiffres)")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Compte mis à jour avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
    *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
    *             @OA\Property(property="total", type="integer", example=1),
    *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
    *             @OA\Property(property="is_admin", type="boolean", example=true)
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Non autorisé - Réservé aux administrateurs",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Non autorisé, réservé aux admins")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Compte introuvable",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Le compte demandé est introuvable")
    *         )
    *     ),
    *     @OA\Response(
    *         response=422,
    *         description="Erreur de validation",
    *         @OA\JsonContent(
    *             @OA\Property(property="message", type="string", example="The given data was invalid."),
    *             @OA\Property(property="errors", type="object",
    *                 @OA\Property(property="empty", type="array",
    *                     @OA\Items(type="string", example="Vous devez fournir au moins un champ à modifier.")
    *                 ),
    *                 @OA\Property(property="informationsClient.email", type="array",
    *                     @OA\Items(type="string", example="Cet email est déjà utilisé.")
    *                 ),
    *                 @OA\Property(property="informationsClient.telephone", type="array",
    *                     @OA\Items(type="string", example="Ce numéro de téléphone est déjà utilisé.")
    *                 ),
    *                 @OA\Property(property="informationsClient.nci", type="array",
    *                     @OA\Items(type="string", example="Ce NCI est déjà utilisé.")
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Erreur serveur",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Erreur interne du serveur")
    *         )
    *     )
    * )
    */
   public function update(UpdateCompteRequest $request, $id)
   {
       $user = Auth::user();

       $compte = Compte::find($id);
       if (!$compte) {
           return $this->errorResponse("Le compte demandé est introuvable.", 404);
       }

       $client = $compte->user;

       if (!$user->isAdmin() && $user->id !== $client->id) {
           return $this->errorResponse("Non autorisé.", 401);
       }

       $validated = $request->validated();

       if (empty($validated)) {
           return $this->errorResponse("Aucun champ à mettre à jour.", 422);
       }

       $client->update($validated);

       return $this->successResponse(
           new CompteRessource($compte),
           "Client mis à jour avec succès",
           1,
           $user->id,
           $user->isAdmin()
       );
   }

   /**
    * @OA\Delete(
    *     path="/comptes/{id}",
    *     summary="Supprimer un compte bancaire (Soft Delete)",
    *     description="Supprime un compte bancaire avec soft delete. Les administrateurs peuvent supprimer tous les comptes, les clients ne peuvent supprimer que leurs propres comptes.",
    *     operationId="deleteCompte",
    *     tags={"Comptes"},
    *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         required=true,
    *         description="ID du compte à supprimer",
    *         @OA\Schema(type="string", format="uuid")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Compte supprimé avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
    *             @OA\Property(property="data", type="null"),
    *             @OA\Property(property="total", type="integer", example=1),
    *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
    *             @OA\Property(property="is_admin", type="boolean", example=false)
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Non autorisé",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Non autorisé.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Compte introuvable",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Compte introuvable.")
    *         )
    *     ),
    *     @OA\Response(
    *         response=500,
    *         description="Erreur serveur",
    *         @OA\JsonContent(
    *             @OA\Property(property="succes", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Erreur interne du serveur")
    *         )
    *     )
    * )
    */
   public function destroy($id){
    $user = Auth::user();

    $compte = Compte::find($id);

    if(!$compte) {
        throw new CompteNotFoundException("Compte introuvable.");
    }

    if (!$user->isAdmin() && $compte->user_id !== $user->id) {
        return $this->errorResponse("Non autorisé.", 401);
    }

    $compte->delete();

    return $this->successResponse(null, "Compte supprimé avec succès", 1, $user->id, $user->isAdmin());
   }

}
