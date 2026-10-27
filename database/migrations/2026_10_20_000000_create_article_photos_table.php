<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Une ligne par photo d'article : un article peut en avoir autant que nécessaire.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('article_photos')) {
            return;
        }

        Schema::create('article_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('chemin');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_photos');
    }
};
