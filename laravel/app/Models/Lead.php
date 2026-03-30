<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'departure_city',
        'destination_country',
        'hotel_category',
        'departure_from',
        'departure_to',
        'nights_from',
        'nights_to',
        'adults',
        'children',
        'preferences',
        'status'
    ];

    protected $casts = [
        'preferences' => 'array',
        'departure_from' => 'date',
        'departure_to' => 'date',
    ];

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function getSearchCriteria(): array
    {
        return [
            'departure_city' => $this->departure_city,
            'destination_country' => $this->destination_country,
            'hotel_category' => $this->hotel_category,
            'departure_from' => $this->departure_from->format('Y-m-d'),
            'departure_to' => $this->departure_to->format('Y-m-d'),
            'nights_from' => $this->nights_from,
            'nights_to' => $this->nights_to,
            'adults' => $this->adults,
            'children' => $this->children,
            'preferences' => $this->preferences,
        ];
    }
}
