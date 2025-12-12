<?php

namespace App\Http\Controllers;

use App\Models\RentalSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Controller RentalSourceController
 * 
 * Gère l'affichage des sources de location avec filtres et pagination
 */
class RentalSourceController extends Controller
{
    /**
     * Affiche la liste des sources de location
     * 
     * @param Request $request Requête HTTP
     * @return \Illuminate\View\View Vue Blade
     */
    public function index(Request $request)
    {
        $query = RentalSource::query();

        // Filtre par ville
        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        // Filtre par type de source
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->input('source_type'));
        }

        // Filtre par qualification
        if ($request->filled('is_qualified')) {
            $query->where('is_qualified', $request->boolean('is_qualified'));
        }

        // Tri par défaut : plus récent en premier
        $query->orderBy('created_at', 'desc');

        // Pagination (15 par page)
        $rentalSources = $query->paginate(15)->withQueryString();

        // Récupération des villes disponibles pour le filtre (avec cache)
        $cities = Cache::remember('rental_sources_cities', 3600, function () {
            return RentalSource::whereNotNull('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->toArray();
        });

        return view('rentals.index', [
            'rentalSources' => $rentalSources,
            'cities' => $cities,
            'filters' => [
                'city' => $request->input('city'),
                'source_type' => $request->input('source_type'),
                'is_qualified' => $request->input('is_qualified'),
            ],
        ]);
    }
}
