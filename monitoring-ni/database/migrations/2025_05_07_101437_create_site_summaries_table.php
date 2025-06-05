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
        Schema::create('site_summaries', function (Blueprint $table) {
            $table->id(); // Clé primaire simple pour cette table de résumé

            // Chaque site ne devrait avoir qu'un seul enregistrement de résumé.
            // La contrainte 'unique' assure cela.
            $table->foreignId('site_id')->unique()->constrained()->onDelete('cascade');

            $table->integer('pending_update_count')->default(0); // Nombre de MAJ en attente
            $table->timestamp('last_backup_at')->nullable();     // Date de la dernière sauvegarde connue

            $table->timestamps(); // Pour savoir quand cet enregistrement de résumé a été créé/mis à jour
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_summaries');
    }
};