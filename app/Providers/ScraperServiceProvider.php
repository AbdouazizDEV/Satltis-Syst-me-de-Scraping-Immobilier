<?php

namespace App\Providers;

use App\Services\DataExtractor\DataExtractorInterface;
use App\Services\DataExtractor\RentalDataExtractor;
use App\Services\RentalSource\RentalSourceRepositoryInterface;
use App\Services\RentalSource\RentalSourceRepository;
use App\Services\Scraper\Implementations\ExampleScraperService;
use App\Services\Scraper\ScraperInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider pour l'injection de dépendances
 * 
 * Lie les interfaces aux implémentations concrètes
 */
class ScraperServiceProvider extends ServiceProvider
{
    /**
     * Enregistre les services de l'application
     * 
     * @return void
     */
    public function register(): void
    {
        // Binding du repository
        $this->app->bind(
            RentalSourceRepositoryInterface::class,
            RentalSourceRepository::class
        );

        // Binding de l'extracteur de données
        $this->app->bind(
            DataExtractorInterface::class,
            RentalDataExtractor::class
        );

        // Binding des scrapers (singleton pour éviter de multiples instances)
        $this->app->singleton(ExampleScraperService::class, function ($app) {
            return new ExampleScraperService(
                $app->make(DataExtractorInterface::class)
            );
        });

        $this->app->singleton(\App\Services\Scraper\Implementations\LadresseScraperService::class, function ($app) {
            return new \App\Services\Scraper\Implementations\LadresseScraperService(
                $app->make(DataExtractorInterface::class)
            );
        });
    }

    /**
     * Bootstrap les services de l'application
     * 
     * @return void
     */
    public function boot(): void
    {
        //
    }
}

