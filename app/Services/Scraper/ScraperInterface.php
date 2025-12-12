<?php

namespace App\Services\Scraper;

/**
 * Interface ScraperInterface
 * 
 * Contrat que tous les scrapers doivent implémenter
 * Suit le principe d'inversion de dépendance (SOLID)
 */
interface ScraperInterface
{
    /**
     * Scrape une URL et retourne un tableau de données extraites
     * 
     * @param string $url URL à scraper
     * @return array Tableau de données extraites (chaque élément représente une annonce)
     * @throws \Exception En cas d'erreur lors du scraping
     */
    public function scrape(string $url): array;

    /**
     * Retourne le nom de la source (pour identification)
     * 
     * @return string Nom de la source (ex: "ExampleSite", "JumiaDeals", etc.)
     */
    public function getSourceName(): string;
}

