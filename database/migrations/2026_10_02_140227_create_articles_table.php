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
        Schema::create('articles', function (Blueprint $table) {
            $table->uuid('id')->primary()->defaultRaw('gen_random_uuid()');
            $table->string('titre');
            $table->text('contenu');
            $table->timestampTz('date_publication')->nullable();
            $table->enum('status', ['draft', 'publie'])->default('draft');
            $table->timestampTz('created_at')->defaultRaw('now()');
            $table->timestampTz('updated_at')->defaultRaw('now()');

            // Index composé (Status + Date de publication décroissante)
            $table->index(['status', 'date_publication'], 'idx_article_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
