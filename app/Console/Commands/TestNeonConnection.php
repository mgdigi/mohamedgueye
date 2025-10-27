<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestNeonConnection extends Command
{
    protected $signature = 'db:test-neon';
    protected $description = 'Test la connexion à la base de données Neon';

    public function handle()
    {
        try {
            $result = DB::connection('neon')->select('SELECT 1');
            $this->info('Connexion à Neon réussie !');
            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur de connexion à Neon : ' . $e->getMessage());
            return 1;
        }
    }
}