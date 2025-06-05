<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $sitesQuery = Site::query();

        if ($user->role !== 'admin' && $user->role !== 'nord-image') {
            $sitesQuery = $user->sites();
        }

        $sites = $sitesQuery->with(['summary', 'pings' => function ($query) {
            $query->latest('pinged_at')->limit(1);
        }])->get();

        foreach ($sites as $site) {
            $latestPing = $site->pings->first();
            if ($latestPing) {
                $pingTime = $latestPing->ping_time;
                $site->latest_ping_data = [
                    'time' => $pingTime,
                    'http' => $latestPing->http_status,
                    'at' => $latestPing->pinged_at?->format('H:i'),
                    'width_percent' => min(100, max(5, intval(($pingTime / 1000) * 100))),
                    'color_class' => match (true) {
                        $pingTime <= 200 => 'bg-green-500',
                        $pingTime <= 500 => 'bg-yellow-400',
                        default => 'bg-red-500',
                    },
                ];
            } else {
                $site->latest_ping_data = null;
            }
        }

        return view('sites.index', ['sites' => $sites]);
    }

    public function create()
    {
        return view('sites.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'url' => [
                'required',
                'url',
                'max:255',
                Rule::unique('sites', 'url')
            ],
            'adminUrl' => 'nullable|url|max:255',
        ]);

        $site = Site::create([
            'name' => $validatedData['name'],
            'url' => rtrim($validatedData['url'], '/'),
            'adminUrl' => isset($validatedData['adminUrl']) ? rtrim($validatedData['adminUrl'], '/') : null,
        ]);

        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'nord-image') {
            $user->sites()->attach($site->id);
        }

        return redirect()->route('sites.index')->with('success', 'Site ajouté avec succès !');
    }

    public function show(Site $site)
    {
        $pings = $site->pings()->orderBy('pinged_at', 'desc')->take(5)->get();

        return view('sites.show', [
            'site' => $site,
            'pings' => $pings,
        ]);
    }

    public function edit(Site $site)
    {
        // à implémenter
    }

    public function update(Request $request, Site $site)
    {
        // à implémenter
    }

    public function destroy(Site $site)
    {
        $user = Auth::user();

        $canDelete = false;

        if ($user->role === 'admin' || $user->role === 'nord-image') {
            $canDelete = true;
        } else if ($user->sites->contains($site)) {
            $canDelete = true;
        }

        if (!$canDelete) {
            return redirect()->route('sites.index')->with('error', 'Action non autorisée.');
        }

        $site->delete();

        return redirect()->route('sites.index')->with('success', 'Site supprimé avec succès !');
    }
}
