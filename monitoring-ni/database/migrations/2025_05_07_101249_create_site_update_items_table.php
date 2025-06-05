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
        Schema::create('site_update_items', function (Blueprint $table) {
            $table->id(); // Clé primaire
            $table->foreignId('site_id')->constrained()->onDelete('cascade'); // Lien vers la table 'sites'
            $table->string('item_name'); // Nom du plugin, thème, ou "WordPress Core"
            $table->string('version');   // Version de l'item
            $table->string('type');      // 'plugin', 'theme', 'wp_core', etc.
            $table->timestamp('item_detected_at')->nullable(); // Quand cette information de MAJ a été reçue/détectée
            // $table->timestamps(); // Optionnel: si vous voulez aussi savoir quand cet enregistrement a été créé/modifié dans la DB
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_update_items');
    }
};