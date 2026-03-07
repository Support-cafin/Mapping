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
        Schema::create('grand_livres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->string('journal_code')->nullable();
            $table->unsignedBigInteger('old_account_id')->nullable();
            $table->unsignedBigInteger('new_account_id')->nullable();
            
            $table->date('date_ecriture');
            $table->string('piece', 50)->nullable()->comment('Numéro de pièce comptable');
            $table->string('libelle');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('solde', 15, 2)->nullable()->comment('Solde cumulé');
            
            // Pour le regroupement
            $table->integer('exercice');
            $table->integer('mois')->nullable();
            $table->integer('trimestre')->nullable();
            
            // Métadonnées
            $table->string('source')->default('manuel')->comment('manuel, import, système');
            $table->boolean('validated')->default(true);
            $table->text('notes')->nullable();
            
            // Pour le lettrage
            $table->string('lettre', 10)->nullable();
            
            // Index
            $table->index(['entreprise_id', 'date_ecriture']);
            $table->index(['entreprise_id', 'exercice']);
            $table->index(['old_account_id', 'date_ecriture']);
            $table->index(['new_account_id', 'date_ecriture']);
            $table->index('piece');
            
            // Clés étrangères
            $table->foreign('entreprise_id')
                  ->references('id')
                  ->on('entreprises')
                  ->onDelete('cascade');
                  
            $table->foreign('old_account_id')
                  ->references('id')
                  ->on('old_accounts')
                  ->nullOnDelete();
                  
            $table->foreign('new_account_id')
                  ->references('id')
                  ->on('new_accounts')
                  ->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grand_livres');
    }
};
