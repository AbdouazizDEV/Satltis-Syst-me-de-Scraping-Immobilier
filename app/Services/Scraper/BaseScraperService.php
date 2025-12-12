<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

/**
 * Classe abstraite BaseScraperService
 * 
 * Fournit la logique commune à tous les scrapers :
 * - Gestion des erreurs HTTP
 * - Rate limiting
 * - Rotation des User-Agents
 * - Méthodes utilitaires pour extraction
 */
abstract class BaseScraperService implements ScraperInterface
{
    /**
     * Délai minimum entre les requêtes (en secondes)
     * 
     * @var int
     */
    protected int $rateLimitDelay = 2;

    /**
     * Timeout pour les requêtes HTTP (en secondes)
     * 
     * @var int
     */
    protected int $timeout = 30;

    /**
     * Nombre maximum de tentatives en cas d'erreur
     * 
     * @var int
     */
    protected int $maxRetries = 3;

    /**
     * Liste des User-Agents à utiliser (rotation)
     * 
     * @var array<int, string>
     */
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
    ];

    /**
     * Dernière requête effectuée (timestamp)
     * 
     * @var int|null
     */
    protected ?int $lastRequestTime = null;

    /**
     * Effectue une requête HTTP avec gestion des erreurs et rate limiting
     * 
     * @param string $url URL à requêter
     * @return string Contenu HTML de la réponse
     * @throws Exception En cas d'erreur HTTP ou de timeout
     */
    protected function fetchUrl(string $url): string
    {
        // Rate limiting : attendre le délai minimum entre les requêtes
        $this->enforceRateLimit();

        // Sélection aléatoire d'un User-Agent
        $userAgent = $this->userAgents[array_rand($this->userAgents)];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                Log::info("Tentative de scraping: {$url} (tentative " . ($attempt + 1) . "/{$this->maxRetries})");

                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'User-Agent' => $userAgent,
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                    ])
                    ->get($url);

                if ($response->successful()) {
                    $this->lastRequestTime = time();
                    return $response->body();
                }

                throw new Exception("Erreur HTTP: {$response->status()}");
            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    $waitTime = $attempt * 2; // Délai exponentiel
                    Log::warning("Erreur lors du scraping, nouvelle tentative dans {$waitTime}s: " . $e->getMessage());
                    sleep($waitTime);
                }
            }
        }

        throw new Exception("Échec après {$this->maxRetries} tentatives: " . $lastException->getMessage());
    }

    /**
     * Applique le rate limiting entre les requêtes
     * 
     * @return void
     */
    protected function enforceRateLimit(): void
    {
        if ($this->lastRequestTime !== null) {
            $elapsed = time() - $this->lastRequestTime;
            if ($elapsed < $this->rateLimitDelay) {
                $waitTime = $this->rateLimitDelay - $elapsed;
                sleep($waitTime);
            }
        }
    }

    /**
     * Crée un Crawler depuis du HTML
     * 
     * @param string $html Contenu HTML
     * @return Crawler Instance du crawler
     */
    protected function createCrawler(string $html): Crawler
    {
        return new Crawler($html);
    }

    /**
     * Extrait le texte d'un élément, avec gestion des erreurs
     * 
     * @param Crawler $crawler Instance du crawler
     * @param string $selector Sélecteur CSS
     * @return string|null Texte extrait ou null
     */
    protected function extractText(Crawler $crawler, string $selector): ?string
    {
        try {
            $element = $crawler->filter($selector);
            if ($element->count() > 0) {
                return trim($element->text());
            }
        } catch (Exception $e) {
            Log::debug("Impossible d'extraire le texte avec le sélecteur '{$selector}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extrait un attribut d'un élément
     * 
     * @param Crawler $crawler Instance du crawler
     * @param string $selector Sélecteur CSS
     * @param string $attribute Nom de l'attribut (ex: 'href', 'src')
     * @return string|null Valeur de l'attribut ou null
     */
    protected function extractAttribute(Crawler $crawler, string $selector, string $attribute): ?string
    {
        try {
            $element = $crawler->filter($selector);
            if ($element->count() > 0) {
                return trim($element->attr($attribute) ?? '');
            }
        } catch (Exception $e) {
            Log::debug("Impossible d'extraire l'attribut '{$attribute}' avec le sélecteur '{$selector}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Extrait plusieurs éléments correspondant à un sélecteur
     * 
     * @param Crawler $crawler Instance du crawler
     * @param string $selector Sélecteur CSS
     * @return array<int, string> Tableau de textes extraits
     */
    protected function extractMultiple(Crawler $crawler, string $selector): array
    {
        $results = [];

        try {
            $elements = $crawler->filter($selector);
            foreach ($elements as $element) {
                $text = trim($element->textContent ?? '');
                if (!empty($text)) {
                    $results[] = $text;
                }
            }
        } catch (Exception $e) {
            Log::debug("Impossible d'extraire les éléments avec le sélecteur '{$selector}': " . $e->getMessage());
        }

        return $results;
    }
}

