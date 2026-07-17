<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports_stage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->enum('type_rapport', ['intermediaire', 'final']);
            $table->string('fichier_path');
            $table->string('fichier_nom_original');
            $table->enum('statut', ['soumis', 'corrige', 'valide'])->default('soumis');
            $table->text('commentaire_encadreur')->nullable();
            $table->timestamp('date_soumission')->default(now());
            $table->timestamp('date_correction')->nullable();
            $table->timestamps();

            $table->unique(['stage_id', 'type_rapport']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports_stage');
    }
};
