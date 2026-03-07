<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journaux', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->string('code', 10);
            $table->string('intitule');
            $table->string('type')->nullable()->comment('Achat, Vente, Banque, Caisse, Divers');
            $table->boolean('actif')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('entreprise_id')
                  ->references('id')
                  ->on('entreprises')
                  ->onDelete('cascade');
            
            $table->unique(['entreprise_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journaux');
    }
};
