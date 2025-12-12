<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Contrôleur API pour les sources de location
 * 
 * Fournit les endpoints API pour consulter les sources scrapées
 */
class RentalSourceApiController extends Controller
{
    /**
     * Liste des sources avec filtres et pagination
     * 
     * GET /api/rentals
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
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

        // Pagination (15 par page par défaut, configurable)
        $perPage = min($request->input('per_page', 15), 100); // Max 100 par page
        $rentalSources = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rentalSources->items(),
            'meta' => [
                'current_page' => $rentalSources->currentPage(),
                'last_page' => $rentalSources->lastPage(),
                'per_page' => $rentalSources->perPage(),
                'total' => $rentalSources->total(),
                'from' => $rentalSources->firstItem(),
                'to' => $rentalSources->lastItem(),
            ],
            'links' => [
                'first' => $rentalSources->url(1),
                'last' => $rentalSources->url($rentalSources->lastPage()),
                'prev' => $rentalSources->previousPageUrl(),
                'next' => $rentalSources->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Détails d'une source spécifique
     * 
     * GET /api/rentals/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $rentalSource = RentalSource::find($id);

        if (!$rentalSource) {
            return response()->json([
                'success' => false,
                'message' => 'Source non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rentalSource,
        ]);
    }

    /**
     * Statistiques sur les sources
     * 
     * GET /api/rentals/stats/summary
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        // Cache des statistiques pendant 1 heure
        $stats = Cache::remember('rental_sources_stats', 3600, function () {
            $total = RentalSource::count();
            $qualified = RentalSource::where('is_qualified', true)->count();
            $agencies = RentalSource::where('source_type', 'AGENCY')->count();
            $private = RentalSource::where('source_type', 'PRIVATE')->count();

            // Statistiques par ville
            $cities = RentalSource::whereNotNull('city')
                ->selectRaw('city, COUNT(*) as count')
                ->groupBy('city')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'city' => $item->city,
                        'count' => $item->count,
                    ];
                });

            return [
                'total' => $total,
                'qualified' => $qualified,
                'not_qualified' => $total - $qualified,
                'by_type' => [
                    'agencies' => $agencies,
                    'private' => $private,
                ],
                'top_cities' => $cities,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
