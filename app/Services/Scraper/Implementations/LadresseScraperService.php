<?php

namespace App\Services\Scraper\Implementations;

use App\Services\Scraper\BaseScraperService;
use App\Services\DataExtractor\DataExtractorInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

/**
 * Classe LadresseScraperService
 * 
 * Scraper spécifique pour le site l'Adresse (https://www.ladresse.com/)
 * 
 * Structure du site :
 * - Page d'accueil avec liste d'annonces
 * - Annonces de location et de vente
 * - Chaque annonce a un lien "Voir le bien" vers la page détaillée
 */
class LadresseScraperService extends BaseScraperService
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
            Log::info("Début du scraping l'Adresse: {$url}");

            // Récupération du contenu HTML
            $html = $this->fetchUrl($url);
            $crawler = $this->createCrawler($html);

            $results = [];

            // Extraction des annonces de location
            $locationResults = $this->extractListings($crawler, $url, 'location');
            $results = array_merge($results, $locationResults);

            // Extraction des annonces de vente
            $saleResults = $this->extractListings($crawler, $url, 'vente');
            $results = array_merge($results, $saleResults);

            // Si aucune annonce trouvée dans la page principale, chercher dans les sections spécifiques
            if (empty($results)) {
                // Chercher dans la section "Nos dernières annonces Location"
                $locationSection = $crawler->filter('section, div')->reduce(function (Crawler $node) {
                    $text = $node->text();
                    return str_contains($text, 'Nos dernières annonces Location') || 
                           str_contains($text, 'Location immobilière');
                });

                if ($locationSection->count() > 0) {
                    $locationResults = $this->extractListingsFromSection($locationSection->first(), $url, 'location');
                    $results = array_merge($results, $locationResults);
                }

                // Chercher dans la section "Nos dernières annonces Vente"
                $saleSection = $crawler->filter('section, div')->reduce(function (Crawler $node) {
                    $text = $node->text();
                    return str_contains($text, 'Nos dernières annonces Vente') || 
                           str_contains($text, 'Achat immobilier');
                });

                if ($saleSection->count() > 0) {
                    $saleResults = $this->extractListingsFromSection($saleSection->first(), $url, 'vente');
                    $results = array_merge($results, $saleResults);
                }
            }

            // Si toujours rien, essayer une extraction générique
            if (empty($results)) {
                $results = $this->extractGenericListings($crawler, $url);
            }

            Log::info("Scraping l'Adresse terminé: " . count($results) . " annonces extraites");

            return $results;
        } catch (Exception $e) {
            Log::error("Erreur lors du scraping de l'Adresse {$url}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extrait les annonces depuis la page principale
     * 
     * @param Crawler $crawler Crawler de la page
     * @param string $baseUrl URL de base
     * @param string $type Type d'annonce (location ou vente)
     * @return array<int, array<string, mixed>>
     */
    protected function extractListings(Crawler $crawler, string $baseUrl, string $type): array
    {
        $results = [];

        // Sélecteurs possibles pour les annonces
        $selectors = [
            'article.property',
            'article.listing',
            '.property-card',
            '.listing-item',
            '.annonce',
            'a[href*="/bien/"]',
            'a[href*="/location/"]',
            'a[href*="/vente/"]',
        ];

        foreach ($selectors as $selector) {
            try {
                $listings = $crawler->filter($selector);
                
                if ($listings->count() > 0) {
                    foreach ($listings as $listingNode) {
                        $listingCrawler = new Crawler($listingNode);
                        $data = $this->extractListingData($listingCrawler, $baseUrl, $type);
                        
                        if (!empty($data['source_url'])) {
                            $normalized = $this->dataExtractor->normalize($data);
                            $normalized['source_type'] = 'AGENCY'; // l'Adresse est un réseau d'agences
                            $results[] = $normalized;
                        }
                    }
                    
                    if (!empty($results)) {
                        break; // Si on a trouvé des résultats, on arrête
                    }
                }
            } catch (Exception $e) {
                Log::debug("Erreur avec le sélecteur '{$selector}': " . $e->getMessage());
                continue;
            }
        }

        return $results;
    }

    /**
     * Extrait les annonces depuis une section spécifique
     * 
     * @param Crawler $sectionCrawler Crawler de la section
     * @param string $baseUrl URL de base
     * @param string $type Type d'annonce
     * @return array<int, array<string, mixed>>
     */
    protected function extractListingsFromSection(Crawler $sectionCrawler, string $baseUrl, string $type): array
    {
        $results = [];

        // Chercher tous les liens "Voir le bien" ou liens vers des biens
        $links = $sectionCrawler->filter('a[href*="bien"], a[href*="location"], a[href*="vente"], a:contains("Voir le bien")');

        foreach ($links as $linkNode) {
            $linkCrawler = new Crawler($linkNode);
            $href = $linkCrawler->attr('href');
            
            if ($href) {
                $absoluteUrl = $this->buildAbsoluteUrl($href, $baseUrl);
                
                // Extraire les données depuis le texte du lien et son contexte
                $data = $this->extractDataFromLinkContext($linkCrawler, $absoluteUrl, $type);
                
                if (!empty($data)) {
                    $normalized = $this->dataExtractor->normalize($data);
                    $normalized['source_type'] = 'AGENCY';
                    $results[] = $normalized;
                }
            }
        }

        return $results;
    }

    /**
     * Extraction générique des annonces
     * 
     * @param Crawler $crawler Crawler de la page
     * @param string $baseUrl URL de base
     * @return array<int, array<string, mixed>>
     */
    protected function extractGenericListings(Crawler $crawler, string $baseUrl): array
    {
        $results = [];

        // Chercher tous les liens qui pourraient pointer vers des annonces
        $allLinks = $crawler->filter('a[href]');

        foreach ($allLinks as $linkNode) {
            $linkCrawler = new Crawler($linkNode);
            $href = $linkCrawler->attr('href');
            $text = trim($linkCrawler->text());

            // Filtrer les liens qui semblent être des annonces
            if ($href && (
                str_contains($href, '/bien/') ||
                str_contains($href, '/location/') ||
                str_contains($href, '/vente/') ||
                str_contains($text, 'Voir le bien') ||
                (str_contains($text, '€') && (str_contains($text, 'pièces') || str_contains($text, 'm²')))
            )) {
                $absoluteUrl = $this->buildAbsoluteUrl($href, $baseUrl);
                
                // Extraire les données depuis le contexte
                $data = $this->extractDataFromLinkContext($linkCrawler, $absoluteUrl, 'location');
                
                if (!empty($data)) {
                    $normalized = $this->dataExtractor->normalize($data);
                    $normalized['source_type'] = 'AGENCY';
                    $results[] = $normalized;
                }
            }
        }

        return $results;
    }

    /**
     * Extrait les données d'une annonce depuis son contexte
     * 
     * @param Crawler $linkCrawler Crawler du lien
     * @param string $url URL de l'annonce
     * @param string $type Type d'annonce
     * @return array<string, mixed>
     */
    protected function extractDataFromLinkContext(Crawler $linkCrawler, string $url, string $type): array
    {
        $data = [
            'source_url' => $url,
            'source_type' => 'AGENCY',
        ];

        // Récupérer le texte du lien et de son parent
        $text = $linkCrawler->text();
        $parent = $linkCrawler->parents()->first();
        $parentText = $parent->count() > 0 ? $parent->text() : '';

        $fullText = $text . ' ' . $parentText;

        // Extraire le type de bien (Appartement, Maison, etc.)
        $propertyTypes = ['Appartement', 'Maison', 'Parking', 'Entrepôt', 'Local industriel', 'Terrain', 'Villa'];
        foreach ($propertyTypes as $propertyType) {
            if (str_contains($fullText, $propertyType)) {
                $data['property_type'] = $propertyType;
                break;
            }
        }

        // Extraire le titre/nom
        $data['name_or_title'] = $this->extractTitle($linkCrawler, $fullText);

        // Extraire la localisation (ville)
        $city = $this->extractCityFromText($fullText);
        if ($city) {
            $data['city'] = $city;
        }

        // Extraire le prix (pour contexte, mais pas stocké dans notre modèle)
        // Le prix peut aider à identifier l'annonce

        // Extraire les informations supplémentaires depuis le parent
        $this->extractAdditionalInfo($linkCrawler, $data);

        return $data;
    }

    /**
     * Extrait les données d'une annonce individuelle
     * 
     * @param Crawler $listingCrawler Crawler pointant sur l'élément de l'annonce
     * @param string $baseUrl URL de base
     * @param string $type Type d'annonce
     * @return array<string, mixed>
     */
    protected function extractListingData(Crawler $listingCrawler, string $baseUrl, string $type): array
    {
        $data = [];

        // Extraction de l'URL
        $relativeUrl = $this->extractAttribute($listingCrawler, 'a', 'href');
        
        if ($relativeUrl) {
            $data['source_url'] = $this->buildAbsoluteUrl($relativeUrl, $baseUrl);
        } else {
            $data['source_url'] = $baseUrl . '#' . uniqid();
        }

        // Extraction du titre
        $data['name_or_title'] = $this->extractText($listingCrawler, 
            '.title, .property-title, h2, h3, .listing-title'
        ) ?? $this->extractTitle($listingCrawler, $listingCrawler->text());

        // Extraction du type de bien
        $fullText = $listingCrawler->text();
        $propertyTypes = ['Appartement', 'Maison', 'Parking', 'Entrepôt', 'Local industriel', 'Terrain', 'Villa'];
        foreach ($propertyTypes as $propertyType) {
            if (str_contains($fullText, $propertyType)) {
                $data['property_type'] = $propertyType;
                break;
            }
        }

        // Extraction de la localisation
        $city = $this->extractCityFromText($fullText);
        if ($city) {
            $data['city'] = $city;
        }

        // Extraction du contact (téléphone, email)
        $data['phone_number'] = $this->dataExtractor->extractPhoneNumber($fullText);
        $data['email'] = $this->dataExtractor->extractEmail($fullText);

        // Si pas trouvé, chercher dans des éléments spécifiques
        if (empty($data['phone_number'])) {
            $phoneText = $this->extractText($listingCrawler, '.phone, .tel, .contact-phone');
            $data['phone_number'] = $this->dataExtractor->extractPhoneNumber($phoneText);
        }

        if (empty($data['email'])) {
            $emailText = $this->extractText($listingCrawler, '.email, .mail, .contact-email');
            $data['email'] = $this->dataExtractor->extractEmail($emailText);
        }

        return $data;
    }

    /**
     * Extrait le titre depuis le contexte
     * 
     * @param Crawler $crawler Crawler
     * @param string $text Texte complet
     * @return string|null
     */
    protected function extractTitle(Crawler $crawler, string $text): ?string
    {
        // Chercher un titre dans les balises h1-h6
        for ($i = 1; $i <= 6; $i++) {
            $title = $this->extractText($crawler, "h{$i}");
            if ($title && strlen($title) > 5) {
                return $title;
            }
        }

        // Sinon, prendre les premiers mots significatifs
        $words = explode(' ', trim($text));
        $meaningfulWords = array_filter($words, function($word) {
            return strlen($word) > 2 && !in_array(strtolower($word), ['voir', 'bien', 'le', 'la', 'les', 'de', 'du', 'des']);
        });

        if (count($meaningfulWords) > 0) {
            return implode(' ', array_slice($meaningfulWords, 0, 10));
        }

        return null;
    }

    /**
     * Extrait la ville depuis un texte
     * 
     * @param string $text Texte à analyser
     * @return string|null
     */
    protected function extractCityFromText(string $text): ?string
    {
        // Format typique sur l'Adresse : "VINCENNES (94300)" ou "Paris 10 (75)" ou "Fauch (81)"
        // Pattern 1: "VILLE (CODE)" ou "Ville (CODE)"
        if (preg_match('/([A-Z][A-Za-z\s\-]+?)\s*\((\d{2,3})\)/', $text, $matches)) {
            $cityName = trim($matches[1]);
            // Nettoyer le nom de ville (enlever les numéros d'arrondissement, etc.)
            $cityName = preg_replace('/\s+\d+\s*$/', '', $cityName); // Enlever "10" de "Paris 10"
            $cityName = trim($cityName);
            
            // Si c'est une ville connue, la retourner directement
            $extracted = $this->dataExtractor->extractCity($cityName);
            if ($extracted) {
                return $extracted;
            }
            
            // Sinon, retourner le nom tel quel (peut être une ville française non dans notre liste)
            return ucwords(strtolower($cityName));
        }

        // Pattern 2: Chercher des noms de villes françaises connues (avec et sans apostrophe)
        $frenchCities = [
            'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg',
            'Montpellier', 'Bordeaux', 'Lille', 'Rennes', 'Reims', 'Saint-Étienne',
            'Le Havre', 'Toulon', 'Grenoble', 'Dijon', 'Angers', 'Nîmes', 'Villeurbanne',
            'Vincennes', 'Cannes', 'Fort-de-France', 'Le Raincy', 'Fauch', 'Bois-d\'Arcy',
            'Bois-d\'Arcy', 'Bois d\'Arcy', 'La Crau', 'Chelles', 'Le Lamentin', 
            'La Balme-de-Sillingy', 'Bois-d\'Arcy',
        ];

        foreach ($frenchCities as $city) {
            $cityPattern = str_replace(['\'', '-'], ['[\'\-]?', '[\s\-]?'], preg_quote($city, '/'));
            if (preg_match('/' . $cityPattern . '/i', $text)) {
                return $city;
            }
        }

        // Pattern 3: Format "VILLE (CODE)" - extraire même si pas dans la liste
        if (preg_match('/([A-Z][A-Za-z\s\-\']+?)\s*\((\d{2,3})\)/', $text, $matches)) {
            $cityName = trim($matches[1]);
            // Nettoyer
            $cityName = preg_replace('/\s+\d+\s*$/', '', $cityName);
            $cityName = trim($cityName);
            
            // Si ça ressemble à une ville (pas trop court, pas trop long)
            if (strlen($cityName) >= 3 && strlen($cityName) <= 40) {
                return ucwords(strtolower($cityName));
            }
        }

        // Sinon, utiliser l'extracteur standard
        return $this->dataExtractor->extractCity($text);
    }

    /**
     * Extrait des informations supplémentaires depuis le contexte
     * 
     * @param Crawler $crawler Crawler
     * @param array<string, mixed> $data Données à compléter
     * @return void
     */
    protected function extractAdditionalInfo(Crawler $crawler, array &$data): void
    {
        $parent = $crawler->parents()->first();
        if ($parent->count() > 0) {
            $parentText = $parent->text();
            
            // Extraire le quartier si présent
            if (empty($data['district'])) {
                $data['district'] = $this->dataExtractor->extractDistrict($parentText, $data['city'] ?? null);
            }
        }
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
        // Si l'URL est déjà absolue
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }

        // Parse de l'URL de base
        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? 'www.ladresse.com';

        // Si l'URL relative commence par /, c'est une URL absolue relative au domaine
        if (str_starts_with($relativeUrl, '/')) {
            return $scheme . '://' . $host . $relativeUrl;
        }

        // Sinon, on construit l'URL relative au chemin de base
        $basePath = dirname($baseParts['path'] ?? '/');
        return $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($relativeUrl, '/');
    }

    /**
     * Retourne le nom de la source
     * 
     * @return string Nom de la source
     */
    public function getSourceName(): string
    {
        return "l'Adresse";
    }
}

