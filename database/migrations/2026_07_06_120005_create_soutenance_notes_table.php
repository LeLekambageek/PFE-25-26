<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soutenance_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soutenance_id')->constrained('soutenances')->cascadeOnDelete();
            $table->foreignId('jury_id')->constrained('users')->cascadeOnDelete();
            $table->string('critere'); // ex: fond, forme, presentation, soutenance_orale
            $table->decimal('note', 4, 2);
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->unique(['soutenance_id', 'jury_id', 'critere']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soutenance_notes');
    }
};
