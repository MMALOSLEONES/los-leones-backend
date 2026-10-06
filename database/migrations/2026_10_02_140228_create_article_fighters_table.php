<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('article_fighter', function (Blueprint $table) {
        
            
            $table->timestampTz('created_at')->defaultRaw('now()');
            $table->timestampTz('updated_at')->defaultRaw('now()');

            // Dans la fonction up() de la migration article_fighter :
            $table->foreignUuid('article_id')->constrained('articles')->onDelete('cascade');
            $table->foreignUuid('fighter_id')->constrained('fighters')->onDelete('cascade');

            // On définit une clé primaire composée pour éviter les doublons (un fighter lié 2 fois au même article)
            $table->primary(['article_id', 'fighter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_fighters');
    }
};
