<?php

/**
 * @OA\Schema(
 *     schema="DearchiveComptesJob",
 *     type="object",
 *     description="Job de désarchivage automatique des comptes dont le blocage est expiré",
 *     @OA\Property(property="queue", type="string", example="default", description="File d'attente"),
 *     @OA\Property(property="attempts", type="integer", example=3, description="Nombre de tentatives"),
 *     @OA\Property(property="timeout", type="integer", example=300, description="Timeout en secondes"),
 *     @OA\Property(property="description", type="string", example="Désarchive automatiquement les comptes dont la date de fin de blocage est échue")
 * )
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;

class DearchiveComptes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            DB::beginTransaction();

            $neonConnection = DB::connection('neon');

            $comptesADesarchiver = $neonConnection->table('comptes_archives')
                ->where('date_fin_blocage', '<=', now())
                ->get();

            foreach ($comptesADesarchiver as $compteArchive) {
                $data = json_decode($compteArchive->data, true);
                
                // Recréer le compte
                $compte = Compte::create(array_merge($data, [
                    'archived' => false,
                    'date_blocage' => null,
                    'date_fin_blocage' => null
                ]));

                // Récupérer et recréer les transactions
                $transactions = $neonConnection->table('transactions_archives')
                    ->where('compte_id', $compteArchive->id)
                    ->get();

                foreach ($transactions as $transaction) {
                    $transactionData = json_decode($transaction->data, true);
                    $compte->transactions()->create($transactionData);
                }

                // Supprimer les archives
                $neonConnection->table('transactions_archives')
                    ->where('compte_id', $compteArchive->id)
                    ->delete();
                    
                $neonConnection->table('comptes_archives')
                    ->where('id', $compteArchive->id)
                    ->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur désarchivage : ' . $e->getMessage());
            throw $e;
        }
    }
}