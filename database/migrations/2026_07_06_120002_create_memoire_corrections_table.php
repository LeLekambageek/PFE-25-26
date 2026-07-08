<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memoire_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memoire_version_id')->constrained('memoire_versions')->cascadeOnDelete();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->text('commentaire');
            $table->string('type_correction')->nullable(); // annotation, recommandation, remarque_generale
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoire_corrections');
    }
};
