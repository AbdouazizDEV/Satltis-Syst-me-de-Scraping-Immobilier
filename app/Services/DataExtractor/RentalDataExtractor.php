<?php

namespace App\Services\DataExtractor;

/**
 * Classe RentalDataExtractor
 * 
 * Extrait et normalise les données depuis les textes scrapés
 * Gère les formats spécifiques aux pays (Sénégal, Bénin)
 */
class RentalDataExtractor implements DataExtractorInterface
{
    /**
     * Villes connues (Sénégal, Bénin et France)
     * 
     * @var array<int, string>
     */
    protected array $knownCities = [
        // Bénin
        'Cotonou', 'Calavi', 'Porto-Novo', 'Abomey-Calavi', 'Parakou', 'Djougou',
        'Bohicon', 'Kandi', 'Natitingou', 'Ouidah', 'Lokossa', 'Malanville',
        // Sénégal
        'Dakar', 'Thiès', 'Rufisque', 'Pikine', 'Guédiawaye', 'Mbour',
        'Kaolack', 'Ziguinchor', 'Saint-Louis', 'Louga', 'Tambacounda',
        // France (principales villes)
        'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg',
        'Montpellier', 'Bordeaux', 'Lille', 'Rennes', 'Reims', 'Saint-Étienne',
        'Le Havre', 'Toulon', 'Grenoble', 'Dijon', 'Angers', 'Nîmes', 'Villeurbanne',
        'Vincennes', 'Cannes', 'Fort-de-France', 'Le Raincy', 'Fauch', 'Bois-d\'Arcy',
        'La Crau', 'Chelles', 'Le Lamentin', 'La Balme-de-Sillingy', 'Villenave-d\'Ornon',
        'Beaucouzé', 'Nevers',
    ];

    /**
     * Extrait un numéro de téléphone depuis un texte
     * Supporte les formats sénégalais (+221) et béninois (+229)
     * 
     * @param string|null $text Texte à analyser
     * @return string|null Numéro de téléphone normalisé ou null
     */
    public function extractPhoneNumber(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Patterns pour numéros de téléphone
        // Format international: +221 77 123 45 67, +229 97 12 34 56
        // Format local: 77 123 45 67, 97 12 34 56, 0612345678
        $patterns = [
            // Format international avec indicatif
            '/\+221[\s\-]?([0-9]{2}[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2})/i',
            '/\+229[\s\-]?([0-9]{2}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2})/i',
            // Format local (9 chiffres pour Sénégal, 8 pour Bénin)
            '/(?:0|221|229)?[\s\-]?([0-9]{2}[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2})/',
            '/(?:0|221|229)?[\s\-]?([0-9]{2}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2})/',
            // Format simple sans espaces
            '/([0-9]{9,10})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $phone = preg_replace('/[\s\-]/', '', $matches[1] ?? $matches[0]);
                
                // Validation basique : entre 8 et 12 chiffres
                if (strlen($phone) >= 8 && strlen($phone) <= 12) {
                    return $phone;
                }
            }
        }

        return null;
    }

    /**
     * Extrait une adresse email depuis un texte
     * 
     * @param string|null $text Texte à analyser
     * @return string|null Email extrait ou null
     */
    public function extractEmail(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Pattern pour email
        $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

        if (preg_match($pattern, $text, $matches)) {
            $email = strtolower(trim($matches[0]));
            
            // Validation basique
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }

    /**
     * Extrait et normalise le nom d'une ville depuis un texte
     * 
     * @param string|null $text Texte à analyser
     * @return string|null Nom de la ville normalisé ou null
     */
    public function extractCity(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        $text = mb_strtolower(trim($text), 'UTF-8');

        // Recherche exacte dans la liste des villes connues
        foreach ($this->knownCities as $city) {
            $cityLower = mb_strtolower($city, 'UTF-8');
            
            // Recherche exacte
            if ($text === $cityLower) {
                return $city;
            }
            
            // Recherche partielle (la ville est contenue dans le texte)
            if (str_contains($text, $cityLower)) {
                return $city;
            }
        }

        // Si aucune ville connue n'est trouvée, mais qu'on a un texte qui ressemble à une ville
        // (commence par une majuscule, fait entre 3 et 30 caractères), on peut le retourner
        if (preg_match('/^[A-Z][a-z]+([\s\-][A-Z][a-z]+)*$/', $text) && strlen($text) >= 3 && strlen($text) <= 30) {
            return $text;
        }

        // Sinon, retourner null
        return null;
    }

    /**
     * Extrait le nom d'un quartier depuis un texte
     * 
     * @param string|null $text Texte à analyser
     * @param string|null $city Ville pour contexte (optionnel)
     * @return string|null Nom du quartier ou null
     */
    public function extractDistrict(?string $text, ?string $city = null): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Mots-clés communs pour quartiers
        $districtKeywords = [
            'quartier', 'zone', 'secteur', 'arrondissement', 'commune',
            'avenue', 'boulevard', 'rue', 'route', 'village',
        ];

        $text = mb_strtolower(trim($text), 'UTF-8');

        // Si le texte contient des mots-clés de quartier, on extrait
        foreach ($districtKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                // On nettoie et retourne le texte (peut être amélioré avec NLP)
                $cleaned = trim(str_replace($keyword, '', $text));
                if (!empty($cleaned) && strlen($cleaned) > 2) {
                    return ucwords($cleaned);
                }
            }
        }

        // Si aucune indication de quartier, on retourne null
        return null;
    }

    /**
     * Normalise les données extraites
     * 
     * @param array<string, mixed> $data Données brutes à normaliser
     * @return array<string, mixed> Données normalisées
     */
    public function normalize(array $data): array
    {
        $normalized = [];

        // Normalisation des clés (snake_case)
        $keys = [
            'source_url' => 'source_url',
            'source_type' => 'source_type',
            'name_or_title' => 'name_or_title',
            'phone_number' => 'phone_number',
            'email' => 'email',
            'property_type' => 'property_type',
            'city' => 'city',
            'district' => 'district',
        ];

        foreach ($keys as $key => $normalizedKey) {
            if (isset($data[$key])) {
                $value = $data[$key];
                
                // Normalisation spécifique selon le type
                switch ($key) {
                    case 'phone_number':
                        $value = $this->extractPhoneNumber($value);
                        break;
                    case 'email':
                        $value = $this->extractEmail($value);
                        break;
                    case 'city':
                        $value = $this->extractCity($value);
                        break;
                    case 'district':
                        $value = $this->extractDistrict($value, $normalized['city'] ?? null);
                        break;
                    case 'source_type':
                        $value = strtoupper($value);
                        if (!in_array($value, ['AGENCY', 'PRIVATE'])) {
                            $value = 'PRIVATE';
                        }
                        break;
                    default:
                        $value = is_string($value) ? trim($value) : $value;
                        break;
                }

                $normalized[$normalizedKey] = $value;
            }
        }

        return $normalized;
    }
}

