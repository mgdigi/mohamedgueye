<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('rating_logs', function (Blueprint $table) {
        $table->uuid('id');
        $table->uuid('user_id')->index(); 

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        $table->integer('limit');
        $table->timestamp('blocked_at')->useCurrent();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating_logs');
    }
};
