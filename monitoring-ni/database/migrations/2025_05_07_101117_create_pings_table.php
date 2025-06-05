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
        Schema::create('pings', function (Blueprint $table) {
            $table->id(); // Clé primaire auto-incrémentée

            // Clé étrangère vers la table 'sites'
            // Si un site est supprimé, ses pings associés seront aussi supprimés (onDelete('cascade'))
            $table->foreignId('site_id')->constrained()->onDelete('cascade');

            $table->integer('ping_time')->nullable(); // Temps de réponse du ping en ms (anciennement 'ping')
            $table->integer('http_status');          // Code de statut HTTP (anciennement 'status')
            $table->integer('curl_error_code')->nullable(); // Code d'erreur cURL, si applicable (anciennement 'error')

            // Timestamp de quand le ping a été effectué. Nous utilisons un nom spécifique
            // plutôt que les `timestamps()` habituels car `updated_at` n'est pas pertinent ici.
            // L'index permet des recherches plus rapides par date.
            $table->timestamp('pinged_at')->nullable()->index();

            // Note: Pas de $table->timestamps(); ici, car un enregistrement de ping n'est généralement pas modifié.
            // 'pinged_at' sert de timestamp de création.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pings');
    }
};