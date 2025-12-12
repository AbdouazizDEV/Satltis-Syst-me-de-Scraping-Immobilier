<?php

namespace App\Console\Commands;

use App\Services\Scraper\ScraperInterface;
use App\Services\RentalSource\RentalSourceRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Commande Artisan pour scraper les annonces immobilières
 * 
 * Usage: php artisan app:scrape-rentals --source=example --url=https://example.com/rentals
 */
class ScrapeRentals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-rentals 
                            {--source=example : Nom de la source à scraper}
                            {--url= : URL à scraper (optionnel, peut être défini dans le code)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape les annonces immobilières depuis différentes sources';

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
        parent::__construct();
        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     * 
     * @return int Code de retour (0 = succès, 1 = erreur)
     */
    public function handle(): int
    {
        $source = $this->option('source');
        $url = $this->option('url');

        $this->info("🚀 Démarrage du scraping...");
        $this->newLine();

        try {
            // Résolution du scraper selon la source
            $scraper = $this->resolveScraper($source);

            if (!$url) {
                // URL par défaut selon la source (à adapter)
                $url = $this->getDefaultUrl($source);
            }

            if (!$url) {
                $this->error("❌ Aucune URL fournie. Utilisez --url= ou définissez une URL par défaut.");
                return Command::FAILURE;
            }

            $this->info("📡 Source: {$scraper->getSourceName()}");
            $this->info("🔗 URL: {$url}");
            $this->newLine();

            // Scraping avec barre de progression
            $this->info("⏳ Scraping en cours...");
            
            $results = $scraper->scrape($url);
            $total = count($results);

            if ($total === 0) {
                $this->warn("⚠️  Aucune annonce trouvée.");
                return Command::SUCCESS;
            }

            // Barre de progression pour le traitement
            $bar = $this->output->createProgressBar($total);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $bar->setMessage('Traitement des annonces...');
            $bar->start();

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

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Résumé final avec couleurs
            $this->info("✅ Scraping terminé avec succès!");
            $this->newLine();
            
            $this->table(
                ['Statistique', 'Valeur'],
                [
                    ['Total trouvé', $total],
                    ['Nouveaux', "<fg=green>{$created}</>"],
                    ['Mis à jour', "<fg=yellow>{$updated}</>"],
                    ['Qualifiés', "<fg=cyan>{$qualified}</>"],
                    ['Erreurs', $errors > 0 ? "<fg=red>{$errors}</>" : "<fg=green>{$errors}</>"],
                ]
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("❌ Erreur lors du scraping: " . $e->getMessage());
            Log::error("Erreur dans la commande ScrapeRentals: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
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
        // Injection de dépendances via le service container
        // Pour ajouter une nouvelle source, créez une nouvelle classe et ajoutez-la ici
        return match ($source) {
            'example' => app(\App\Services\Scraper\Implementations\ExampleScraperService::class),
            'ladresse' => app(\App\Services\Scraper\Implementations\LadresseScraperService::class),
            default => throw new Exception("Source inconnue: {$source}. Sources disponibles: example, ladresse"),
        };
    }

    /**
     * Retourne l'URL par défaut pour une source
     * 
     * @param string $source Nom de la source
     * @return string|null URL par défaut ou null
     */
    protected function getDefaultUrl(string $source): ?string
    {
        // URLs par défaut (à adapter selon vos besoins)
        return match ($source) {
            'example' => 'https://example.com/rentals', // URL d'exemple
            'ladresse' => 'https://www.ladresse.com/', // Site l'Adresse
            default => null,
        };
    }
}
