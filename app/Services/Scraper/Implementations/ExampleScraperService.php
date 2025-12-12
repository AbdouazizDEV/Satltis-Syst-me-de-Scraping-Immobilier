<?php

namespace App\Services\Scraper\Implementations;

use App\Services\Scraper\BaseScraperService;
use App\Services\DataExtractor\DataExtractorInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

/**
 * Classe ExampleScraperService
 * 
 * Implémentation concrète d'un scraper pour un site d'annonces immobilières
 * 
 * NOTE: Ce scraper est un exemple. Pour un site réel, vous devrez :
 * 1. Analyser la structure HTML du site cible
 * 2. Adapter les sélecteurs CSS
 * 3. Tester avec différents types d'annonces
 * 4. Respecter le robots.txt du site
 * 
 * Exemple de site à scraper (à adapter selon le site réel) :
 * - Structure attendue : liste d'annonces avec titre, prix, localisation, contact
 */
class ExampleScraperService extends BaseScraperService
{
    /**
     * Instance de l'extracteur de données
     * 
     * @var DataExtractorInterface
     */
    protected DataExtractorInterface $dataExtractor;

    /**
     * Constructeur
     * 
     * @param DataExtractorInterface $dataExtractor Extracteur de données
     */
    public function __construct(DataExtractorInterface $dataExtractor)
    {
        $this->dataExtractor = $dataExtractor;
    }

    /**
     * Scrape une URL et retourne un tableau de données extraites
     * 
     * @param string $url URL à scraper
     * @return array<int, array<string, mixed>> Tableau de données extraites
     * @throws Exception En cas d'erreur lors du scraping
     */
    public function scrape(string $url): array
    {
        try {
            Log::info("Début du scraping pour: {$url}");

            // Récupération du contenu HTML
            $html = $this->fetchUrl($url);
            $crawler = $this->createCrawler($html);

            // Extraction des annonces
            // NOTE: Adaptez ces sélecteurs selon la structure HTML du site cible
            $listings = $crawler->filter('.listing-item, .property-card, .ad-item, article.property');
            
            $results = [];

            foreach ($listings as $listingNode) {
                $listingCrawler = new Crawler($listingNode);
                
                try {
                    $data = $this->extractListingData($listingCrawler, $url);
                    
                    if (!empty($data)) {
                        // Normalisation des données
                        $normalized = $this->dataExtractor->normalize($data);
                        
                        // Détection automatique du type (AGENCY vs PRIVATE)
                        if (!isset($normalized['source_type']) || $normalized['source_type'] === 'PRIVATE') {
                            $normalized['source_type'] = $this->detectSourceType($normalized);
                        }
                        
                        $results[] = $normalized;
                    }
                } catch (Exception $e) {
                    Log::warning("Erreur lors de l'extraction d'une annonce: " . $e->getMessage());
                    continue;
                }
            }

            Log::info("Scraping terminé: " . count($results) . " annonces extraites");

            return $results;
        } catch (Exception $e) {
            Log::error("Erreur lors du scraping de {$url}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extrait les données d'une annonce individuelle
     * 
     * @param Crawler $listingCrawler Crawler pointant sur l'élément de l'annonce
     * @param string $baseUrl URL de base pour construire les URLs absolues
     * @return array<string, mixed> Données extraites
     */
    protected function extractListingData(Crawler $listingCrawler, string $baseUrl): array
    {
        $data = [];

        // Extraction du titre/nom
        // Adaptez ces sélecteurs selon le site cible
        $data['name_or_title'] = $this->extractText($listingCrawler, 
            '.title, .listing-title, h2, h3, .property-title, .ad-title'
        );

        // Extraction de l'URL de l'annonce
        $relativeUrl = $this->extractAttribute($listingCrawler, 
            'a, .listing-link, .property-link', 'href'
        );
        
        if ($relativeUrl) {
            // Construction de l'URL absolue
            $data['source_url'] = $this->buildAbsoluteUrl($relativeUrl, $baseUrl);
        } else {
            // Si pas d'URL trouvée, on utilise l'URL de base avec un identifiant
            $data['source_url'] = $baseUrl . '#' . uniqid();
        }

        // Extraction du type de bien
        $data['property_type'] = $this->extractText($listingCrawler, 
            '.property-type, .type, .category, .bien-type'
        );

        // Extraction de la localisation (ville, quartier)
        $locationText = $this->extractText($listingCrawler, 
            '.location, .address, .ville, .city, .localisation'
        );
        
        if ($locationText) {
            $data['city'] = $this->dataExtractor->extractCity($locationText);
            $data['district'] = $this->dataExtractor->extractDistrict($locationText, $data['city'] ?? null);
        }

        // Extraction du contact (téléphone, email)
        // On cherche dans tout le texte de l'annonce
        $fullText = $listingCrawler->text();
        
        $data['phone_number'] = $this->dataExtractor->extractPhoneNumber($fullText);
        $data['email'] = $this->dataExtractor->extractEmail($fullText);

        // Si pas trouvé dans le texte, on cherche dans des éléments spécifiques
        if (empty($data['phone_number'])) {
            $phoneText = $this->extractText($listingCrawler, 
                '.phone, .tel, .telephone, .contact-phone'
            );
            $data['phone_number'] = $this->dataExtractor->extractPhoneNumber($phoneText);
        }

        if (empty($data['email'])) {
            $emailText = $this->extractText($listingCrawler, 
                '.email, .mail, .contact-email'
            );
            $data['email'] = $this->dataExtractor->extractEmail($emailText);
        }

        return $data;
    }

    /**
     * Construit une URL absolue à partir d'une URL relative
     * 
     * @param string $relativeUrl URL relative
     * @param string $baseUrl URL de base
     * @return string URL absolue
     */
    protected function buildAbsoluteUrl(string $relativeUrl, string $baseUrl): string
    {
        // Si l'URL est déjà absolue, on la retourne telle quelle
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }

        // Parse de l'URL de base
        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'http';
        $host = $baseParts['host'] ?? '';

        // Si l'URL relative commence par /, c'est une URL absolue relative au domaine
        if (str_starts_with($relativeUrl, '/')) {
            return $scheme . '://' . $host . $relativeUrl;
        }

        // Sinon, on construit l'URL relative au chemin de base
        $basePath = dirname($baseParts['path'] ?? '/');
        return $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($relativeUrl, '/');
    }

    /**
     * Détecte automatiquement le type de source (AGENCY vs PRIVATE)
     * 
     * @param array<string, mixed> $data Données de l'annonce
     * @return string 'AGENCY' ou 'PRIVATE'
     */
    protected function detectSourceType(array $data): string
    {
        // Mots-clés indiquant une agence
        $agencyKeywords = [
            'agence', 'agency', 'immobilier', 'real estate', 'bureau',
            'cabinet', 'sarl', 'sarlu', 'sa', 'société',
        ];

        $textToAnalyze = strtolower(
            ($data['name_or_title'] ?? '') . ' ' .
            ($data['email'] ?? '')
        );

        foreach ($agencyKeywords as $keyword) {
            if (str_contains($textToAnalyze, $keyword)) {
                return 'AGENCY';
            }
        }

        // Si l'email contient un domaine professionnel (peut être amélioré)
        if (isset($data['email'])) {
            $domain = explode('@', $data['email'])[1] ?? '';
            $professionalDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
            
            if (!in_array(strtolower($domain), $professionalDomains)) {
                return 'AGENCY';
            }
        }

        return 'PRIVATE';
    }

    /**
     * Retourne le nom de la source
     * 
     * @return string Nom de la source
     */
    public function getSourceName(): string
    {
        return 'ExampleSite';
    }
}

