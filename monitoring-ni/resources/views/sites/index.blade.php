<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Liste des Sites') }}
        </h2>
    </x-slot>
    <style>
        .ping-bar-container {
    max-width: 120px;
    margin: 8px auto;
}

.ping-bar {
    width: 100%;
    height: 10px;
    background-color: #e5e7eb; /* gris clair */
    border-radius: 999px;
    overflow: hidden;
}

.ping-bar-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
}

    </style>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 border border-green-400 rounded" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error')) {{-- AJOUTEZ CECI --}}
                        <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-400 rounded" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    {{-- Section pour ajouter un site (sera gérée par la méthode `store` et une vue `create` plus tard) --}}
                    {{-- Pour l'instant, nous nous concentrons sur l'affichage --}}
                    <div class="mb-4 text-right">
    <a href="{{ route('sites.create') }}" style="background-color: green; color: white; padding: 10px;">
    {{ __('Ajouter un Site') }}
</a>
</div>
                    
                    <h3 class="text-lg font-medium mb-4">Sites Surveillés</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Site</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code HTTP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">MAJ</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sauvegarde</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="sitesTableBody">
                                @forelse ($sites as $site)
                                    <tr id="site-row-{{ $site->id }}">
                                        <td class="px-6 py-4 whitespace-nowrap flex items-center gap-2">
                                            <a href="{{ route('sites.show', $site->id) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $site->name }}
                                            </a>
                                            @if ($site->adminUrl)
                                                <a style="margin-left: 0.5rem;" href="{{ $site->adminUrl }}" target="_blank" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300" title="Admin WordPress">
                                                    ⚙️
                                                </a>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($site->last_http_code >= 200 && $site->last_http_code < 300)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    En ligne
                                                </span>
                                            @elseif ($site->last_http_code === null)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Inconnu
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Hors ligne
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($site->latest_ping_data)
    <div class="text-center text-xs font-semibold text-gray-900 dark:text-white">
        {{ $site->latest_ping_data['time'] }} ms
    </div>
    <div class="w-28 h-2.5 bg-gray-200 rounded-full mx-auto my-1">
        <div class="h-2.5 rounded-full {{ $site->latest_ping_data['color_class'] }}" style="width: {{ $site->latest_ping_data['width_percent'] }}%"></div>
    </div>
    <div class="text-center text-xs text-gray-500 dark:text-gray-400">
        {{ $site->latest_ping_data['http'] }} | {{ $site->latest_ping_data['at'] }}
    </div>
@else
    <div class="text-gray-400 italic text-sm">Aucun ping</div>
@endif

                                        </td>

                                        @php
                                            $now = now();
                                            $updateDate = $site->summary?->updated_at;
                                            $backupDate = $site->summary?->last_backup_at;
                                        @endphp

                                        <td class="px-6 py-4 whitespace-nowrap text-sm flex items-center gap-2">
                                            @if ($site->summary && $site->summary->pending_update_count > 0)
                                                <span class="bg-red-600 text-white rounded-full px-2 py-0.5 text-xs font-bold">
                                                    {{ $site->summary->pending_update_count }}
                                                </span>
                                                @if ($updateDate)
                                                    <span  style="margin-left: 0.5rem;" class="{{ $updateDate->gt($now->subDays(15)) ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $updateDate->format('d/m/Y') }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-green-600 dark:text-green-400">OK</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($backupDate)
                                                <span class="{{ $backupDate->lt($now->subDays(15)) ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ $backupDate->format('d/m/Y H:i') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{-- Le bouton supprimer utilisera un formulaire pour envoyer une requête DELETE --}}
                                            <form action="{{ route('sites.destroy', $site->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce site ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Supprimer</button>
                                            </form>
                                            {{-- Nous ajouterons d'autres actions comme "Voir détails" plus tard --}}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                            Aucun site à afficher pour le moment.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>