<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatures_stage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('entreprise_id')->nullable()->constrained('entreprises')->nullOnDelete();
            $table->string('titre_poste');
            $table->text('description')->nullable();
            $table->string('cv_path');
            $table->string('lettre_motivation_path');
            $table->enum('statut', [
                'en_attente', 'retenue', 'rejetee', 'stage_affecte'
            ])->default('en_attente');
            $table->text('commentaire_admin')->nullable();
            $table->timestamp('date_candidature')->default(now());
            $table->timestamp('date_reponse')->nullable();
            $table->foreignId('stage_id')->nullable()->constrained('stages')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures_stage');
    }
};
