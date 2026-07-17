<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memoire_versions', function (Blueprint $table) {
            $table->integer('pourcentage_avancement')->default(0);
            $table->boolean('verrouille')->default(false);
            $table->timestamp('date_verrouillage')->nullable();
            $table->text('annotations')->nullable();
            $table->text('commentaires_encadreur')->nullable();
            $table->text('recommandations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('memoire_versions', function (Blueprint $table) {
            $table->dropColumn([
                'pourcentage_avancement',
                'verrouille',
                'date_verrouillage',
                'annotations',
                'commentaires_encadreur',
                'recommandations'
            ]);
        });
    }
};
