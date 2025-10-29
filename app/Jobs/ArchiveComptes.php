<?php

/**
 * @OA\Schema(
 *     schema="ArchiveComptesJob",
 *     type="object",
 *     description="Job d'archivage automatique des comptes bloqués expirés",
 *     @OA\Property(property="queue", type="string", example="default", description="File d'attente"),
 *     @OA\Property(property="attempts", type="integer", example=3, description="Nombre de tentatives"),
 *     @OA\Property(property="timeout", type="integer", example=300, description="Timeout en secondes"),
 *     @OA\Property(property="description", type="string", example="Archive automatiquement les comptes bloqués dont la date de fin de blocage est échue")
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
use Carbon\Carbon;

/**
 * @OA\Info(
 *     title="Jobs d'Archivage Automatique",
 *     version="1.0.0",
 *     description="Système d'archivage automatique des comptes bancaires"
 * )
 */
class ArchiveComptes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            DB::beginTransaction();

            
            $neonConnection = DB::connection('neon');

            $comptesAArchiver = Compte::where('date_blocage', '<=', now())
                                    ->where('archived', operator: false)
                                    ->with('transactions')
                                    ->get();

            foreach ($comptesAArchiver as $compte) {
                $neonConnection->table('comptes_archives')->insert([
                    'id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'user_id' => $compte->user_id,
                    'data' => json_encode($compte->toArray()),
                    'archived_at' => now(),
                ]);

                foreach ($compte->transactions as $transaction) {
                    $neonConnection->table('transactions_archives')->insert([
                        'id' => $transaction->id,
                        'compte_id' => $transaction->compte_id,
                        'data' => json_encode($transaction->toArray()),
                        'archived_at' => now(),
                    ]);
                }

                $compte->update(['archived' => true]);
                
                $compte->transactions()->delete();
                $compte->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur archivage : ' . $e->getMessage());
            throw $e;
        }
    }
}