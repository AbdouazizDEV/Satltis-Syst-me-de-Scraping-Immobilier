<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Scraper\ScraperInterface;
use App\Services\Scraper\Implementations\ExampleScraperService;
use App\Services\RentalSource\RentalSourceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Contrôleur API pour le scraping
 * 
 * Gère les requêtes API pour démarrer et suivre les opérations de scraping
 */
class ScrapingController extends Controller
{
    /**
     * Repository pour la gestion des sources
     * 
     * @var RentalSourceRepositoryInterface
     */
    protected RentalSourceRepositoryInterface $repository;

    /**
     * Constructeur
     * 
     * @param RentalSourceRepositoryInterface $repository
     */
    public function __construct(RentalSourceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Démarre un scraping
     * 
     * POST /api/scraping/start
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source' => 'required|string|in:example,ladresse',
            'url' => 'required|url',
        ], [
            'source.required' => 'Le nom de la source est requis',
            'source.in' => 'Source non supportée. Sources disponibles: example, ladresse',
            'url.required' => 'L\'URL est requise',
            'url.url' => 'L\'URL doit être valide',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $source = $request->input('source');
            $url = $request->input('url');

            // Résolution du scraper
            $scraper = $this->resolveScraper($source);

            Log::info("Démarrage du scraping via API", [
                'source' => $source,
                'url' => $url,
            ]);

            // Exécution du scraping (synchrone pour l'API, peut être mis en queue)
            $results = $scraper->scrape($url);
            $total = count($results);

            if ($total === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucune annonce trouvée',
                    'data' => [
                        'total' => 0,
                        'created' => 0,
                        'updated' => 0,
                        'qualified' => 0,
                    ],
                ]);
            }

            // Traitement des résultats
            $created = 0;
            $updated = 0;
            $qualified = 0;
            $errors = 0;

            foreach ($results as $data) {
                try {
                    $existing = $this->repository->findByUrl($data['source_url'] ?? '');
                    $wasExisting = $existing !== null;

                    $rentalSource = $this->repository->createOrUpdate($data);

                    if ($wasExisting) {
                        $updated++;
                    } else {
                        $created++;
                    }

                    if ($rentalSource->is_qualified) {
                        $qualified++;
                    }
                } catch (Exception $e) {
                    $errors++;
                    Log::error("Erreur lors du traitement d'une annonce: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Scraping terminé avec succès',
                'data' => [
                    'total' => $total,
                    'created' => $created,
                    'updated' => $updated,
                    'qualified' => $qualified,
                    'errors' => $errors,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error("Erreur lors du scraping via API: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du scraping',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtient le statut d'un scraping (pour les jobs asynchrones)
     * 
     * GET /api/scraping/status/{jobId}
     * 
     * @param string $jobId
     * @return JsonResponse
     */
    public function status(string $jobId): JsonResponse
    {
        // TODO: Implémenter le suivi des jobs si utilisation de queues
        return response()->json([
            'success' => true,
            'message' => 'Fonctionnalité à implémenter avec les queues',
            'job_id' => $jobId,
        ]);
    }

    /**
     * Liste les sources de scraping disponibles
     * 
     * GET /api/scraping/sources
     * 
     * @return JsonResponse
     */
    public function sources(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'id' => 'example',
                    'name' => 'Example Site',
                    'description' => 'Site d\'exemple pour le scraping',
                ],
                [
                    'id' => 'ladresse',
                    'name' => 'l\'Adresse',
                    'description' => 'Réseau d\'agences immobilières l\'Adresse (https://www.ladresse.com/)',
                ],
            ],
        ]);
    }

    /**
     * Résout le scraper selon le nom de la source
     * 
     * @param string $source Nom de la source
     * @return ScraperInterface Instance du scraper
     * @throws Exception Si la source n'est pas trouvée
     */
    protected function resolveScraper(string $source): ScraperInterface
    {
        return match ($source) {
            'example' => app(ExampleScraperService::class),
            'ladresse' => app(\App\Services\Scraper\Implementations\LadresseScraperService::class),
            default => throw new Exception("Source inconnue: {$source}. Sources disponibles: example, ladresse"),
        };
    }
}
