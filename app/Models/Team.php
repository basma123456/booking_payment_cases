<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_id', // Owner ID
        'personal_team',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    /**
     * Get the owner of the team.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all members belonging to the team with their casted role.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps()
            ->using(TeamUserPivot::class); // Strongly recommended for casting pivot columns
    }

    /**
     * Check if a specific user has a specific role in this team.
     */
    public function hasUserRole(User $user, TeamRole $role): bool
    {
        if ($this->user_id === $user->id && $role === TeamRole::OWNER) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();

        return $member && $member->pivot->role === $role;
    }
}
