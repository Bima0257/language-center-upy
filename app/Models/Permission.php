<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property string|null $permission_category_id
 * @property string|null $route_name
 * @property string|null $action
 * @property bool $is_system
 * @property string $category_assignment_source
 * @property Carbon|null $last_scanned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PermissionCategory|null $category
 */
class Permission extends SpatiePermission
{
    use HasUuids;

    protected $fillable = [
        'name',
        'guard_name',
        'permission_category_id',
        'route_name',
        'action',
        'is_system',
        'category_assignment_source',
        'last_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PermissionCategory::class, 'permission_category_id', 'id');
    }
}
