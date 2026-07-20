<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidatures_stage', function (Blueprint $table) {
            $table->foreignId('offre_id')->nullable()->after('etudiant_id')
                ->constrained('offres_stage')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidatures_stage', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offre_id');
        });
    }
};