<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('entreprise_id')->nullable()->constrained('entreprises')->nullOnDelete();
            $table->foreignId('encadreur_id')->nullable()->constrained('enseignants')->nullOnDelete();

            $table->string('titre');
            $table->text('description')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');

            $table->enum('statut', [
                'en_attente', 'valide', 'rejete', 'en_cours', 'termine', 'evalue',
            ])->default('en_attente');

            $table->string('convention_path')->nullable();
            $table->string('attestation_path')->nullable();
            $table->timestamps();
        });

        Schema::create('stage_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->text('contenu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_journal_entries');
        Schema::dropIfExists('stages');
    }
};