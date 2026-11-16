<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property bool $is_system
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Role extends SpatieRole
{
    use HasUuids;

    protected $fillable = [
        'name',
        'guard_name',
        'is_system',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'role_parents',
            'role_id',
            'parent_role_id',
        );
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'role_parents',
            'parent_role_id',
            'role_id',
        );
    }

    public function deniedPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permission_denials',
            'role_id',
            'permission_id',
        );
    }
}
