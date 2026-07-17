<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soutenance_jury', function (Blueprint $table) {
            $table->timestamp('date_debut_acces')->nullable();
            $table->timestamp('date_fin_acces')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('date_desactivation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('soutenance_jury', function (Blueprint $table) {
            $table->dropColumn([
                'date_debut_acces',
                'date_fin_acces',
                'actif',
                'date_desactivation'
            ]);
        });
    }
};
