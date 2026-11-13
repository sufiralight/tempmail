<?php

namespace App\Auth;

enum WorkspaceRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::User => ['dashboard.view'],
            self::Admin => ['dashboard.view', 'admin.access', 'users.view', 'settings.view'],
            self::SuperAdmin => ['dashboard.view', 'admin.access', 'users.view', 'users.manage', 'roles.manage', 'settings.view', 'settings.manage', 'audit.view'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::User => 'Member',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::User => 'Personal workspace access.',
            self::Admin => 'Read access to the console, users and settings.',
            self::SuperAdmin => 'Administrative changes, settings and audit access.',
        };
    }
}
