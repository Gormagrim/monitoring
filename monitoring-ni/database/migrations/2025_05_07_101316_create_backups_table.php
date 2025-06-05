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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade'); // Lien vers la table 'sites'
            $table->timestamp('backup_time')->nullable(); // Date et heure de la sauvegarde
            $table->string('source')->nullable();      // Source/type de la sauvegarde (ex: 'ai1wm')
            $table->timestamps(); // created_at et updated_at pour cette entrée de log
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};