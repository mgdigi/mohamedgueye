<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     title="Compte",
 *     description="Objet représentant un compte bancaire",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="numeroCompte", type="string", example="ACC-ABC123DEF"),
 *     @OA\Property(property="titulaire", type="string", example="John Doe"),
 *     @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne"),
 *     @OA\Property(property="solde", type="number", format="float", example=1500.50),
 *     @OA\Property(property="devise", type="string", example="XOF"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-10-23T10:00:00Z"),
 *     @OA\Property(property="statut", type="string", example="actif"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, example=null),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-10-23T10:00:00Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 */
class CompteRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // $depot = $this->depot_sum;
        // $retrait = $this->retrait_sum;

        // $solde = $depot  -  $retrait;

        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero_compte,
            'titulaire' => $this->titulaire,
            'type' => $this->type,
            'solde' => (float) $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at,
            'statut' => $this->statut,
            'metadata' => [
                'derniereModification' => $this->updated_at,
                'version' => $this->version,
            ]
        ];
    }
}
