<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacturesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('utilisateurs')->onDelete('cascade'); // Ajouter cette ligne
            $table->decimal('montant', 8, 2);
            $table->enum('etat_paiement', ['payée', 'en attente', 'en retard'])->default('en attente');
            $table->timestamp('dateEmission')->useCurrent();
            $table->timestamp('dateLimite');
            $table->timestamp('datePaiement')->nullable();
            $table->timestamps();

            // Un ticket peut avoir qu'une seule facture
            $table->unique('ticket_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
}
