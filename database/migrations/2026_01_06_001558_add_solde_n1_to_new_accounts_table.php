<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_accounts', function (Blueprint $table) {
            $table->decimal('solde_debit_n1', 15, 2)
                  ->default(0)
                  ->after('parent_id');

            $table->decimal('solde_credit_n1', 15, 2)
                  ->default(0)
                  ->after('solde_debit_n1');
        });
    }

    public function down(): void
    {
        Schema::table('new_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'solde_debit_n1',
                'solde_credit_n1'
            ]);
        });
    }
};
