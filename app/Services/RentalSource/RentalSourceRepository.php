<?php

namespace App\Services\RentalSource;

use App\Models\RentalSource;
use Illuminate\Support\Facades\Log;

/**
 * Classe RentalSourceRepository
 * 
 * Implémentation du repository pour RentalSource
 * Gère la création, mise à jour et prévention des doublons
 */
class RentalSourceRepository implements RentalSourceRepositoryInterface
{
    /**
     * Crée ou met à jour une source de location
     * 
     * @param array<string, mixed> $data Données de la source
     * @return RentalSource Instance créée ou mise à jour
     */
    public function createOrUpdate(array $data): RentalSource
    {
        // Validation : l'URL est requise
        if (empty($data['source_url'])) {
            throw new \InvalidArgumentException("L'URL source est requise");
        }

        // Calcul automatique de is_qualified
        $data['is_qualified'] = $this->calculateIsQualified($data);

        // Recherche d'une source existante avec la même URL
        $existing = $this->findByUrl($data['source_url']);

        if ($existing) {
            // Mise à jour de la source existante
            Log::info("Mise à jour de la source existante: {$data['source_url']}");
            $existing->update($data);
            return $existing->fresh();
        }

        // Création d'une nouvelle source
        Log::info("Création d'une nouvelle source: {$data['source_url']}");
        return RentalSource::create($data);
    }

    /**
     * Trouve une source par son URL
     * 
     * @param string $url URL de la source
     * @return RentalSource|null Instance trouvée ou null
     */
    public function findByUrl(string $url): ?RentalSource
    {
        return RentalSource::where('source_url', $url)->first();
    }

    /**
     * Calcule si une source est qualifiée
     * Une source est qualifiée si elle a au minimum :
     * - (téléphone OU email) ET
     * - ville
     * 
     * @param array<string, mixed> $data Données de la source
     * @return bool True si qualifiée, false sinon
     */
    public function calculateIsQualified(array $data): bool
    {
        // Vérification de la présence d'un contact (téléphone OU email)
        $hasContact = !empty($data['phone_number']) || !empty($data['email']);

        // Vérification de la présence d'une ville
        $hasCity = !empty($data['city']);

        // Une source est qualifiée si elle a un contact ET une ville
        return $hasContact && $hasCity;
    }
}

