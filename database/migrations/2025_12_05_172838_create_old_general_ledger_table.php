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
        Schema::create('old_general_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->unsignedBigInteger('old_account_id');
            $table->unsignedBigInteger('new_account_id')->nullable(); // après mapping
            $table->string('journal')->nullable(); // nouveau
            $table->date('date_operation')->nullable();
            $table->string('piece', 100)->nullable();
            $table->string('libelle')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('solde', 15, 2)->nullable();
            $table->integer('exercice');
            $table->timestamps();

            $table->foreign('entreprise_id')->references('id')->on('entreprises')->cascadeOnDelete();
            $table->foreign('old_account_id')->references('id')->on('old_accounts')->cascadeOnDelete();
            $table->foreign('new_account_id')->references('id')->on('new_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_general_ledger');
    }
};
