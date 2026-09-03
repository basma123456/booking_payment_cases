<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'price',
        'capacity',
        'allocated_count',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
