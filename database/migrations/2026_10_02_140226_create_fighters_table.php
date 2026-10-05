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
        Schema::create('fighters', function (Blueprint $table) {
            $table->uuid('id')->primary()->defaultRaw('gen_random_uuid()');
            $table->string('nom');
            $table->string('prenom');
            $table->string('surnom')->nullable();
            $table->string('weight_category')->nullable();
            $table->enum('niveau', ['debutant', 'intermediaire', 'avance', 'professionnel'])->nullable();
            $table->string('reseaux')->nullable();
            $table->integer('combat')->default(0);
            $table->integer('victoire')->default(0);
            $table->integer('defaite')->default(0);
            $table->integer('nul')->default(0);
            $table->integer('ko')->default(0);
            $table->integer('soumission')->default(0);
            $table->text('apropos')->nullable();
            $table->text('style_de_combat')->nullable();
            $table->text('palmares')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->defaultRaw('now()');
            $table->timestampTz('updated_at')->defaultRaw('now()');

            // Index
            $table->index('is_active', 'idx_fighter_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fighters');
    }
};
