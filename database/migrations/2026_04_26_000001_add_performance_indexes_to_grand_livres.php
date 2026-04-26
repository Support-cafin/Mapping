<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grand_livres', function (Blueprint $table) {
            // Couverture du filtre principal : entreprise + exercice + validated + date
            $table->index(['entreprise_id', 'exercice_id', 'validated'], 'gl_entreprise_exercice_validated');
            $table->index(['entreprise_id', 'exercice_id', 'validated', 'date_ecriture'], 'gl_main_filter');
        });
    }

    public function down(): void
    {
        Schema::table('grand_livres', function (Blueprint $table) {
            $table->dropIndex('gl_entreprise_exercice_validated');
            $table->dropIndex('gl_main_filter');
        });
    }
};
