<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('donateurs', function (Blueprint $table) {
            $table->id();
            $table->string('numero_enregistrement')->unique();
            $table->date('date');
            $table->string('nom_prenoms')->nullable();
            $table->string('denomination');
            $table->string('registre_commerce')->nullable();
            $table->string('numero_identification_fiscal')->nullable();
            $table->text('adresse_siege_social')->nullable();
            $table->json('email')->nullable(); // Stocke plusieurs emails
            $table->decimal('montant_don', 15, 2);
            $table->enum('mode_liberation', ['espèces', 'chèque', 'virement', 'nature', 'mixte'])->default('virement');
            $table->string('devise')->default('XOF');
            $table->boolean('signature_representant')->default(false);
            $table->text('notes')->nullable();
            $table->enum('statut', ['enregistré', 'validé', 'comptabilisé', 'annulé'])->default('enregistré');

            // Référence au plan comptable
            $table->foreignId('account_mapping')->nullable()->constrained('old_accounts')->onDelete('set null');

            // Auditing
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('date');
            $table->index('denomination');
            $table->index('statut');
            $table->index(['date', 'statut']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('donateurs');
    }
};
