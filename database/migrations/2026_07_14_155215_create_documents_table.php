<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->enum('type_document', ['memoire', 'rapport_stage', 'autre']);
            $table->string('titre');
            $table->string('auteur')->nullable();
            $table->year('annee')->nullable();
            $table->string('mention')->nullable();
            $table->string('mots_cles')->nullable();
            $table->string('fichier_path')->nullable();
            $table->foreignId('archive_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('memoire_id')->nullable()->constrained('memoires')->nullOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('stages')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};