<?php

namespace App\Services\DataExtractor;

/**
 * Interface DataExtractorInterface
 * 
 * Contrat pour l'extraction et la normalisation des données
 */
interface DataExtractorInterface
{
    /**
     * Extrait un numéro de téléphone depuis un texte
     * 
     * @param string|null $text Texte à analyser
     * @return string|null Numéro de téléphone normalisé ou null
     */
    public function extractPhoneNumber(?string $text): ?string;

    /**
     * Extrait une adresse email depuis un texte
     * 
     * @param string|null $text Texte à analyser
     * @return string|null Email extrait ou null
     */
    public function extractEmail(?string $text): ?string;

    /**
     * Extrait et normalise le nom d'une ville depuis un texte
     * 
     * @param string|null $text Texte à analyser
     * @return string|null Nom de la ville normalisé ou null
     */
    public function extractCity(?string $text): ?string;

    /**
     * Extrait le nom d'un quartier depuis un texte
     * 
     * @param string|null $text Texte à analyser
     * @param string|null $city Ville pour contexte (optionnel)
     * @return string|null Nom du quartier ou null
     */
    public function extractDistrict(?string $text, ?string $city = null): ?string;

    /**
     * Normalise les données extraites
     * 
     * @param array $data Données brutes à normaliser
     * @return array Données normalisées
     */
    public function normalize(array $data): array;
}

