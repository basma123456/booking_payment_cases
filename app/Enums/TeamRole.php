<?php

namespace App\Enums;

enum TeamRole: string
{
    // Change OWNER to Owner
case Owner = 'owner';
case Admin = 'admin';
case Editor = 'editor';
case Member = 'member';

    public function label(): string
{
    return match ($this) {
    self::Owner => 'Owner',
            self::Admin => 'Administrator',
            self::Editor => 'Editor',
            self::Member => 'Team Member',
        };
    }
}
