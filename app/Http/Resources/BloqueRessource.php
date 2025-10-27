<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BloqueRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'motif_blocage' => $this->motif_blocage,
            'date_blocage' => $this->date_blocage,
            'date_fin_blocage' => $this->date_fin_blocage,   
        ];
    }
}
