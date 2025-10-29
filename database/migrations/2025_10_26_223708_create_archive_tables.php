<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
public function up()
{
    try {
        if(!Schema::connection('neon')->hasTable('comptes_archives')) {
        Schema::connection('neon')->create('comptes_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_compte');
            $table->uuid('user_id');
            $table->json('data');
            $table->timestamp('archived_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if(!Schema::connection('neon')->hasTable('transactions_archives')) {
        Schema::connection('neon')->create('transactions_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compte_id');
            $table->json('data');
            $table->timestamp('archived_at');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('compte_id')
                  ->references('id')
                  ->on('comptes_archives')
                  ->onDelete('cascade');
        });
    }
    } catch (\Exception $e) {
        \Log::error('Erreur migration : ' . $e->getMessage());
        throw $e;
    }
}

    public function down()
    {
        Schema::connection('neon')->dropIfExists('transactions_archives');
        Schema::connection('neon')->dropIfExists('comptes_archives');
    }
};