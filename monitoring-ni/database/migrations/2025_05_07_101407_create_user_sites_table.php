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
        Schema::create('user_sites', function (Blueprint $table) {
            // Clés étrangères
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');

            // Définir une clé primaire composite pour éviter les doublons
            // et assurer l'unicité de la paire user_id/site_id.
            $table->primary(['user_id', 'site_id']);

            // Pas de $table->id(); ni de $table->timestamps(); ici,
            // car c'est une table de liaison simple.
            // Si vous aviez besoin de stocker quand l'association a été faite,
            // vous pourriez ajouter $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sites');
    }
};