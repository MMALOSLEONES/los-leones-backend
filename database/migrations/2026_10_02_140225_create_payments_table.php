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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->defaultRaw('gen_random_uuid()');
            $table->enum('statut', ['initie', 'reussi', 'echoue'])->default('initie');
            $table->integer('montant'); // Stocké en FCFA
            $table->enum('fournisseur', ['wave', 'orange_money', 'carte'])->nullable();
            $table->string('ref_transaction')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('created_at')->defaultRaw('now()');
            $table->timestampTz('updated_at')->defaultRaw('now()');
            
            $table->foreignUuid('demande_id')
                ->constrained('demandes')
                ->onDelete('cascade'); // Si la demande est supprimée, on supprime l'historique des paiements

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
