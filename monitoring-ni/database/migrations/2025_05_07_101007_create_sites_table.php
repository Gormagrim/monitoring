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
        Schema::create('sites', function (Blueprint $table) {
            $table->id(); // Équivalent à bigIncrements('id') - clé primaire auto-incrémentée
            $table->string('name');
            $table->string('url')->unique(); // L'URL de chaque site devrait être unique
            $table->string('adminUrl')->nullable(); // URL d'administration, peut être nulle
            $table->integer('last_http_code')->nullable(); // Dernier code HTTP reçu
            $table->unsignedInteger('total_pings')->default(0);
            $table->unsignedInteger('failed_pings')->default(0);
            $table->unsignedInteger('total_downtime_seconds')->default(0);
            $table->integer('avg_ping')->nullable(); // Temps de ping moyen en ms
            $table->timestamp('last_success')->nullable(); // Date et heure du dernier ping réussi
            // $table->string('slack_webhook_url')->nullable(); // Optionnel : si vous voulez un webhook Slack par site
            $table->timestamps(); // Ajoute les colonnes created_at et updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};