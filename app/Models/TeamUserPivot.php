<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUserPivot extends Pivot
{
    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }
}
