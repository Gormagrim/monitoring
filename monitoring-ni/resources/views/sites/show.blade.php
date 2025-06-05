<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Détails du Site : {{ $site->name ?? 'Site inconnu' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-2">Informations Générales</h3>
                    <p><strong>Nom :</strong> {{ $site->name }}</p>
                    <p><strong>URL :</strong> <a href="{{ $site->url }}" target="_blank" class="text-blue-600 hover:underline">{{ $site->url }}</a></p>
                    @if ($site->adminUrl)
                        <p><strong>URL Admin :</strong> <a href="{{ $site->adminUrl }}" target="_blank" class="text-blue-600 hover:underline">{{ $site->adminUrl }}</a></p>
                    @endif
                    <p><strong>Dernier Code HTTP :</strong> {{ $site->last_http_code ?? 'N/A' }}</p>
                    <p><strong>Dernier Succès :</strong> {{ $site->last_success ? $site->last_success->format('d/m/Y H:i:s') : 'Jamais' }}</p>
                    <p><strong>Ping Moyen :</strong> {{ $site->avg_ping ? $site->avg_ping . ' ms' : 'N/A' }}</p>
                    <p><strong>Pings Totaux :</strong> {{ $site->total_pings }}</p>
                    <p><strong>Pings Échoués :</strong> {{ $site->failed_pings }}</p>
                    <p><strong>Temps d'indisponibilité :</strong> {{ gmdate("H\h i\m s\s", $site->total_downtime_seconds) }}</p>

                    @if ($site->summary)
                        <h3 class="text-lg font-medium mt-6 mb-2">Résumé des Mises à Jour et Sauvegardes</h3>
                        <p><strong>Mises à jour en attente :</strong> {{ $site->summary->pending_update_count }}</p>
                        <p><strong>Dernière sauvegarde :</strong> {{ $site->summary->last_backup_at ? $site->summary->last_backup_at->format('d/m/Y H:i:s') : 'N/A' }}</p>
                    @endif

                    <h3 class="text-lg font-medium mt-6 mb-2">Historique des Pings (5 derniers)</h3>
                    @if ($pings->count() > 0)
                        <div class="overflow-x-auto mt-2">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut HTTP</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Temps (ms)</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Erreur Curl</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($pings as $ping)
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $ping->pinged_at ? $ping->pinged_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                @if ($ping->http_status >= 200 && $ping->http_status < 300)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ $ping->http_status }}</span>
                                                @elseif ($ping->http_status >= 300 && $ping->http_status < 400)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $ping->http_status }}</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ $ping->http_status }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $ping->ping_time ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $ping->curl_error_code ?? '0' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p>Aucun historique de ping disponible pour ce site.</p>
                    @endif

                    <div class="mt-6">
                        <h3 class="text-lg font-medium mb-2">Graphique des Pings</h3>
                        <canvas id="pingChart" class="w-full max-h-[400px]"></canvas>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('sites.index') }}" class="text-blue-600 hover:underline dark:text-blue-400">
                            &laquo; Retour à la liste des sites
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $jsonPings = collect($pings)
            ->map(function($ping) {
                return [
                    'x' => $ping->pinged_at instanceof \Carbon\Carbon ? $ping->pinged_at->toIso8601String() : null,
                    'y' => is_numeric($ping->ping_time) ? (float) $ping->ping_time : null
                ];
            })
            ->filter(fn($p) => $p['x'] !== null && $p['y'] !== null)
            ->values()
            ->toJson();
    @endphp

    <!-- Chart.js et adaptateurs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const pingsRawData = JSON.parse(@json($jsonPings));
        console.log("✅ Données (ISO) :", pingsRawData);
        console.log(Chart.version);
        console.log("🎯 Nombre total de graphiques actifs :", Chart.instances ? Object.keys(Chart.instances).length : 'Chart.instances non défini');

        const ctx = document.getElementById('pingChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Temps de réponse (ms)',
                    data: pingsRawData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    pointRadius: 2
                }]
            },
            options: {
                parsing: {
                    xAxisKey: 'x',
                    yAxisKey: 'y'
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            tooltipFormat: 'dd/LL/yyyy HH:mm',
                            displayFormats: {
                                minute: 'HH:mm',
                                hour: 'dd/LL HH:mm'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Date et Heure'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Temps de réponse (ms)'
                        }
                    }
                },
                responsive: true
            }
        });
    });
    </script>
</x-app-layout>