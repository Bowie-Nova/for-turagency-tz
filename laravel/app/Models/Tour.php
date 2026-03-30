<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tour extends Model
{
    protected $fillable = [
        'lead_id',
        'operator',
        'title',
        'hotel_name',
        'hotel_category',
        'price',
        'days',
        'departure_date',
        'available_seats',
        'hotel_rating',
        'inclusions',
        'url',
        'popularity_score',
    ];

    protected $casts = [
        'inclusions' => 'array',
        'departure_date' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
