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
        Schema::create('rendez_vouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id'); // Référence au ticket
            $table->unsignedBigInteger('agent_id'); // Agent qui planifie le rendez-vous
            $table->unsignedBigInteger('client_id'); // Client concerné
            $table->dateTime('date_rendezvous'); // Date et heure du rendez-vous
            $table->string('lieu'); // Lieu du rendez-vous
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('utilisateurs')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('utilisateurs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendez_vouses');
    }
};
