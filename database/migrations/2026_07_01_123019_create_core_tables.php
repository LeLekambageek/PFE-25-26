<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('matricule')->unique();
            $table->string('filiere');
            $table->string('niveau');
            $table->timestamps();
        });

        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('specialite')->nullable();
            $table->integer('capacite_encadrement')->default(5);
            $table->timestamps();
        });

        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->string('raison_sociale');
            $table->string('secteur_activite')->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_telephone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprises');
        Schema::dropIfExists('enseignants');
        Schema::dropIfExists('etudiants');
    }
};