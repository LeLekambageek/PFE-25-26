<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encadrements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('enseignants')->cascadeOnDelete();
            $table->string('type')->default('stage'); // 'stage' ou 'memoire'
            $table->enum('statut', ['actif', 'termine'])->default('actif');
            $table->timestamps();
        });

        Schema::create('encadrement_rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encadrement_id')->constrained('encadrements')->cascadeOnDelete();
            $table->dateTime('date_prevue');
            $table->text('sujet')->nullable();
            $table->enum('statut', ['planifie', 'realise', 'annule'])->default('planifie');
            $table->timestamps();
        });

        Schema::create('encadrement_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encadrement_id')->constrained('encadrements')->cascadeOnDelete();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->text('contenu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encadrement_entries');
        Schema::dropIfExists('encadrement_rendez_vous');
        Schema::dropIfExists('encadrements');
    }
};