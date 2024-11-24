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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('utilisateurs')->onDelete('cascade'); // Client est un utilisateur
            $table->foreignId('agent_id')->nullable()->constrained('utilisateurs')->onDelete('cascade'); // Agent, peut être null au départ
            $table->text('description');
            $table->enum('priorite', ['faible', 'moyenne', 'haute'])->default('moyenne');
            $table->enum('type_demande', ['technique', 'commercial', 'autre']);
            $table->enum('statut', ['en_cours', 'résolu', 'en_attente'])->default('en_attente'); // Statut par défaut 'en_attente'
            $table->timestamp('dateCreation')->useCurrent(); // Date de création du ticket
            $table->timestamp('dateResolution')->nullable(); // Date de résolution
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
