<?php

namespace App\Services\RentalSource;

use App\Models\RentalSource;

/**
 * Interface RentalSourceRepositoryInterface
 * 
 * Contrat pour le repository de RentalSource
 * Pattern Repository pour séparer la logique métier de l'accès aux données
 */
interface RentalSourceRepositoryInterface
{
    /**
     * Crée ou met à jour une source de location
     * 
     * @param array<string, mixed> $data Données de la source
     * @return RentalSource Instance créée ou mise à jour
     */
    public function createOrUpdate(array $data): RentalSource;

    /**
     * Trouve une source par son URL
     * 
     * @param string $url URL de la source
     * @return RentalSource|null Instance trouvée ou null
     */
    public function findByUrl(string $url): ?RentalSource;

    /**
     * Calcule si une source est qualifiée
     * Une source est qualifiée si elle a au minimum : téléphone OU email, et ville
     * 
     * @param array<string, mixed> $data Données de la source
     * @return bool True si qualifiée, false sinon
     */
    public function calculateIsQualified(array $data): bool;
}

