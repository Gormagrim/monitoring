<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Pour la validation
use App\Models\Site;
use App\Models\SiteUpdateItem;
use App\Models\Backup;
use App\Models\SiteSummary;
use Carbon\Carbon; // Pour la gestion des dates

class WordpressUpdateController extends Controller
{

    public function store(Request $request)
    {
        Log::info('API /site-updates: Données reçues -> ' . $request->getContent());

        // 1. Vérifier la clé API
        $expectedApiKey = env('WORDPRESS_UPDATE_API_KEY');
        if (empty($expectedApiKey)) {
            Log::warning('WORDPRESS_UPDATE_API_KEY environment variable not set.');
        }

        $receivedApiKey = $request->input('api_key');
        if ($receivedApiKey !== $expectedApiKey) {
            Log::warning('API /site-updates: Clé API invalide ou manquante.');
            return response()->json(['error' => 'Unauthorized - Invalid API Key'], 401);
        }

        // 2. Validation des données
        $validator = Validator::make($request->all(), [
            'site_url' => 'required|url',
            'items' => 'present|array', // 'present' signifie que la clé doit exister, même si le tableau est vide
            'items.*.name' => 'required_with:items.*.version,items.*.type|string|max:255', // Requis si d'autres champs item sont là
            'items.*.version' => 'required_with:items.*.name,items.*.type|string|max:255',
            'items.*.type' => 'required_with:items.*.name,items.*.version|string|in:plugin,theme,core',
            'last_backup' => 'nullable|date_format:Y-m-d H:i:s', // Format de date MySQL
        ]);

        if ($validator->fails()) {
            Log::warning('API /site-updates: Validation échouée -> ', $validator->errors()->toArray());
            return response()->json(['error' => 'Validation Failed', 'messages' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $siteUrl = rtrim($validatedData['site_url'], '/');

        // 3. Trouver le site correspondant dans la base de données
        // Il faut s'assurer que les URLs sont stockées de manière cohérente (avec ou sans www, http/https)
        // Une approche simple est de chercher l'URL avec et sans 'www.' et en http/https
        $site = Site::where('url', $siteUrl)
                    ->orWhere('url', str_replace('www.', '', $siteUrl))
                    ->orWhere('url', 'http://' . ltrim(str_replace(['http://', 'https://'], '', $siteUrl), '/'))
                    ->orWhere('url', 'https://' . ltrim(str_replace(['http://', 'https://'], '', $siteUrl), '/'))
                    ->first();

        if (!$site) {
            Log::warning('API /site-updates: Site non trouvé en base pour URL -> ' . $siteUrl);
            return response()->json(['error' => 'Site not found for URL: ' . $siteUrl], 404);
        }

        $now = Carbon::now();
        $updateItems = $validatedData['items'] ?? [];
        $lastBackupDate = isset($validatedData['last_backup']) ? Carbon::parse($validatedData['last_backup']) : null;

        // 4. Enregistrer/Mettre à jour les items de mise à jour (table `site_update_items`)
        // Pour simplifier, on pourrait supprimer les anciennes MAJ pour ce site et insérer les nouvelles.
        // Ou une logique plus complexe de mise à jour si un item existe déjà.
        // Ici, on supprime les anciens et on ajoute les nouveaux pour ce site.
        SiteUpdateItem::where('site_id', $site->id)->delete();
        foreach ($updateItems as $item) {
            SiteUpdateItem::create([
                'site_id' => $site->id,
                'item_name' => $item['name'],
                'version' => $item['version'],
                'type' => $item['type'],
                'item_detected_at' => $now, // Date de détection actuelle
            ]);
        }
        Log::info("API /site-updates: {$site->name} - ".count($updateItems)." items de MAJ traités.");

        // 5. Enregistrer la nouvelle sauvegarde si elle est différente de la dernière enregistrée (table `backups`)
        // On récupère la dernière sauvegarde enregistrée pour ce site pour éviter les doublons si la date n'a pas changé.
        $latestStoredBackup = Backup::where('site_id', $site->id)
                                    ->orderBy('backup_time', 'desc')
                                    ->first();

        if ($lastBackupDate) {
            // Si une nouvelle date de backup est fournie ET
            // (aucune sauvegarde n'est stockée OU la nouvelle date est plus récente que la dernière stockée)
            if (!$latestStoredBackup || $lastBackupDate->gt($latestStoredBackup->backup_time)) {
                Backup::create([
                    'site_id' => $site->id,
                    'backup_time' => $lastBackupDate,
                    'source' => 'ai1wm', // Ou à déterminer dynamiquement si vous avez d'autres sources
                ]);
                Log::info("API /site-updates: {$site->name} - Nouvelle sauvegarde enregistrée: " . $lastBackupDate->toDateTimeString());
            }
        }

        // 6. Mettre à jour la table de résumé `site_summaries`
        SiteSummary::updateOrCreate(
            ['site_id' => $site->id], // Conditions pour trouver l'enregistrement
            [                         // Valeurs à mettre à jour ou à créer
                'pending_update_count' => count($updateItems),
                'last_backup_at' => $lastBackupDate, // Sera null si $lastBackupDate est null
                // 'updated_at' sera géré automatiquement par Eloquent
            ]
        );
        Log::info("API /site-updates: {$site->name} - Résumé mis à jour.");

        return response()->json(['success' => true, 'message' => 'Data received and processed for site: ' . $site->name]);
    }
}