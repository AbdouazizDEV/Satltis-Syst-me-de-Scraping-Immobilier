<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle RentalSource
 * 
 * Représente une source de location (annonce) scrapée depuis différents sites
 * 
 * @property int $id
 * @property string $source_url URL unique de la source
 * @property string $source_type Type de source (AGENCY ou PRIVATE)
 * @property string|null $name_or_title Nom ou titre de l'annonce
 * @property string|null $phone_number Numéro de téléphone
 * @property string|null $email Adresse email
 * @property string|null $property_type Type de bien (Appartement, Maison, etc.)
 * @property string|null $city Ville
 * @property string|null $district Quartier
 * @property bool $is_qualified Indique si la source est qualifiée (a toutes les infos nécessaires)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RentalSource extends Model
{
    /**
     * Nom de la table associée au modèle
     *
     * @var string
     */
    protected $table = 'rental_sources';

    /**
     * Attributs qui peuvent être assignés en masse
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_url',
        'source_type',
        'name_or_title',
        'phone_number',
        'email',
        'property_type',
        'city',
        'district',
        'is_qualified',
    ];

    /**
     * Attributs qui doivent être castés
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_qualified' => 'boolean',
    ];

    /**
     * Valeurs par défaut pour les attributs
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'source_type' => 'PRIVATE',
        'is_qualified' => false,
    ];
}
