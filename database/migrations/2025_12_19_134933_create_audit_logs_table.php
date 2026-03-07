<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('entreprise_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        
            $table->string('action'); // create, update, delete, validate, mapping_sync...
            $table->string('model');  // GrandLivre, AccountMapping, OldAccount...
            $table->unsignedBigInteger('model_id')->nullable();
        
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
        
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
        
            $table->timestamps();
        
            // Index pour les performances
            $table->index(['entreprise_id', 'created_at']);
            $table->index(['model', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};