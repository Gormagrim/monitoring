<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Site; // Importer le modèle Site
use App\Models\Ping; // Importer le modèle Ping
use Illuminate\Support\Facades\Http; // Client HTTP de Laravel
use Illuminate\Support\Facades\Log;  // Pour les logs Laravel
use Carbon\Carbon; // Pour la gestion des dates et heures

class PingSitesCommand extends Command
{
    /**
     * The name and signature of the console command.
     * C'est ainsi que vous appellerez la commande : php artisan app:ping-sites
     * @var string
     */
    protected $signature = 'app:ping-sites';

    /**
     * The console command description.
     * Description qui apparaît quand vous faites `php artisan list`
     * @var string
     */
    protected $description = 'Pings all registered sites to check their status and records the results.';

    // Intervalle de ping supposé en secondes (pour le calcul du downtime)
    // Si votre cron tourne toutes les minutes, mettez 60.
    // Si c'est toutes les 5 minutes, mettez 300.
    // Ajustez cela en fonction de la fréquence de votre tâche cron.
    const PING_INTERVAL_SECONDS = 60; // Par exemple, si le cron tourne chaque minute

    /**
     * Execute the console command.
     */
    public function handle(): int // Changé pour retourner un int (0 pour succès, 1 pour erreur)
    {
        Log::info('PingSitesCommand: CRON lancé.'); // Log Laravel
        $this->info('PingSitesCommand: Lancement du ping des sites...');

        $sites = Site::all(); // Récupère tous les sites

        if ($sites->isEmpty()) {
            $this->info('Aucun site à pinger.');
            Log::info('PingSitesCommand: Aucun site à pinger.');
            return Command::SUCCESS; // 0
        }

        foreach ($sites as $site) {
            $this->line("Pinging: {$site->name} ({$site->url})");

            $startTime = microtime(true);
            $httpCode = 0;
            $curlError = 0;
            $isOnline = false;
            $wasOnline = ($site->last_http_code >= 200 && $site->last_http_code < 400 && $site->last_http_code !== null);

            try {
                // Utilisation du client HTTP de Laravel avec un timeout
                $response = Http::timeout(10) // Timeout de 10 secondes
                                ->withoutVerifying() // Similaire à CURLOPT_SSL_VERIFYPEER => false si besoin
                                ->get($site->url);

                $httpCode = $response->status();
                if ($response->successful()) { // Codes 2xx
                    $isOnline = true;
                } elseif ($response->clientError()) { // Codes 4xx
                    $isOnline = false;
                } elseif ($response->serverError()) { // Codes 5xx
                    $isOnline = false;
                }
                // Pour les redirections (3xx), $response->successful() sera false
                // mais le site est techniquement "accessible". Vous pouvez ajuster cette logique.
                // Par exemple, considérer 3xx comme en ligne aussi :
                if ($httpCode >= 200 && $httpCode < 400) {
                    $isOnline = true;
                }


            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Erreur de connexion (timeout, DNS non résolu, etc.)
                $curlError = $e->getCode() ?: CURLE_COULDNT_CONNECT; // Utilise un code d'erreur cURL générique si non dispo
                $httpCode = 0; // Pas de réponse HTTP
                $isOnline = false;
                Log::warning("PingSitesCommand: Erreur de connexion pour {$site->url} - " . $e->getMessage());
            } catch (\Exception $e) {
                // Autre type d'erreur
                $curlError = $e->getCode() ?: 1; // Erreur générique
                $httpCode = 0;
                $isOnline = false;
                Log::error("PingSitesCommand: Erreur inattendue pour {$site->url} - " . $e->getMessage());
            }

            $endTime = microtime(true);
            $pingTimeMs = round(($endTime - $startTime) * 1000);

            // Enregistrement du Ping
            Ping::create([
                'site_id' => $site->id,
                'ping_time' => $pingTimeMs,
                'http_status' => $httpCode,
                'curl_error_code' => $curlError,
                'pinged_at' => now(), // Utilise Carbon pour l'heure actuelle
            ]);

            // Mise à jour des statistiques du site
            $site->last_http_code = $httpCode;
            $site->total_pings += 1;

            if ($isOnline) {
                $site->last_success = now();
                // avg_ping: (avg_ping * (total_pings - 1) + new_ping_time) / total_pings
                // Attention, total_pings vient d'être incrémenté.
                // Si avg_ping est null (premier ping réussi), initialiser.
                if ($site->avg_ping === null || $site->total_pings == 1) {
                    $site->avg_ping = $pingTimeMs;
                } else {
                    // Nous utilisons total_pings -1 car avg_ping stocké correspondait à l'état *avant* ce ping
                    $previous_total_successful_pings = $site->total_pings - 1 - $site->failed_pings;
                    if ($previous_total_successful_pings < 0) $previous_total_successful_pings = 0; // Eviter division par zero ou negatifs
                    // Recalculer la moyenne uniquement sur les pings réussis pourrait être plus pertinent
                    // ou comme dans votre script original, sur tous les pings
                    // Script original : ROUND((avg_ping * (total_pings AVANT incrément) + ping_time_ms) / (total_pings APRES incrément))
                    // Ici, (total_pings -1) est le total_pings avant l'incrément actuel.
                    $site->avg_ping = round((($site->avg_ping ?? 0) * ($site->total_pings -1) + $pingTimeMs) / $site->total_pings);

                }
            } else {
                $site->failed_pings += 1;
                // Accumuler le temps d'indisponibilité (basé sur l'intervalle de ping)
                $site->total_downtime_seconds += self::PING_INTERVAL_SECONDS;
            }
            $site->save();

            Log::info("PingSitesCommand: {$site->url} - Code: {$httpCode}, CurlErr: {$curlError}, Ping: {$pingTimeMs}ms");

            // Notifications Slack
            $nowFormatted = now()->format('Y-m-d H:i:s');
            $link = "<{$site->url}|{$site->name}>"; // Formatage Slack pour lien

            if (!$isOnline && $wasOnline) {
                $this->sendSlackNotification(
                    "🚨 *Site HORS LIGNE* : {$link} (Code : {$httpCode}) à {$nowFormatted}"
                );
            } elseif ($isOnline && !$wasOnline && $site->last_http_code !== null) { // S'assurer que ce n'est pas le tout premier ping
                $this->sendSlackNotification(
                    "✅ *Site EN LIGNE* : {$link} (Code : {$httpCode}) à {$nowFormatted}"
                );
            }
        }

        $this->info('PingSitesCommand: Ping des sites terminé.');
        Log::info('PingSitesCommand: CRON terminé.');
        return Command::SUCCESS; // 0
    }

    /**
     * Send a Slack notification.
     * @param string $message
     */
    private function sendSlackNotification(string $message): void
    {
        $slackWebhookUrl = config('services.slack.webhook_url'); // Récupère depuis config/services.php

        if (!$slackWebhookUrl) {
            Log::warning('PingSitesCommand: Slack Webhook URL non configurée. Notification non envoyée.');
            $this->warn('Slack Webhook URL non configurée. Notification non envoyée: ' . $message);
            return;
        }

        try {
            Http::post($slackWebhookUrl, [
                'text' => $message,
            ]);
            $this->info('Notification Slack envoyée: ' . $message);
        } catch (\Exception $e) {
            Log::error('PingSitesCommand: Erreur envoi Slack - ' . $e->getMessage());
            $this->error('Erreur envoi Slack: ' . $e->getMessage());
        }
    }
}
