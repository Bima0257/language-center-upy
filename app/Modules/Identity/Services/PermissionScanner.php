<?php

namespace App\Modules\Identity\Services;

use App\Models\Permission;
use App\Models\PermissionCategory;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionScanner
{
    private const PREFIXES = ['admin.', 'ujian.', 'pengawas.', 'pustaka-soal.'];

    public function __construct(
        private Router $router,
    ) {}

    /**
     * @return array<string, int>
     */
    public function scan(): array
    {
        return DB::transaction(function (): array {
            $routeNames = collect($this->router->getRoutes()->getRoutes())
                ->map(fn ($route): ?string => $route->getName())
                ->filter(fn (?string $name): bool => filled($name))
                ->filter(fn (string $name): bool => ! str_ends_with($name, '.'))
                ->filter(fn (string $name): bool => collect(self::PREFIXES)->contains(
                    fn (string $prefix): bool => str_starts_with($name, $prefix),
                ))
                ->unique()
                ->values();

            $scannedNames = [];

            foreach ($routeNames as $routeName) {
                $categoryData = $this->categoryData($routeName);
                $category = PermissionCategory::firstOrCreate(
                    ['code' => $categoryData['code']],
                    [
                        'name' => $categoryData['name'],
                        'module' => $categoryData['module'],
                        'is_system' => true,
                    ],
                );

                $permission = Permission::firstOrNew([
                    'name' => $routeName,
                    'guard_name' => 'web',
                ]);

                if (! $permission->exists || $permission->category_assignment_source !== 'manual') {
                    $permission->permission_category_id = $category->id;
                    $permission->category_assignment_source = 'scanner';
                }

                $permission->route_name = $routeName;
                $permission->action = $this->actionFromRoute($routeName);
                $permission->is_system = true;
                $permission->last_scanned_at = now();
                $permission->save();

                $scannedNames[] = $routeName;
            }

            Permission::query()
                ->whereNotNull('route_name')
                ->when($scannedNames !== [], fn ($query) => $query->whereNotIn('route_name', $scannedNames))
                ->update(['last_scanned_at' => null]);

            PermissionCategory::query()
                ->where('code', '!=', 'uncategorized')
                ->whereDoesntHave('permissions')
                ->delete();

            return [
                'routes' => $routeNames->count(),
                'permissions' => count($scannedNames),
                'categories' => PermissionCategory::query()->count(),
            ];
        });
    }

    /**
     * @return array{code: string, name: string, module: string}
     */
    public function categoryData(string $routeName): array
    {
        $parts = explode('.', $routeName);
        $prefix = $parts[0];

        if ($prefix === 'admin') {
            if (count($parts) >= 3) {
                $moduleSlug = $parts[1];
                $nameSlug = $parts[2];

                return [
                    'code' => $moduleSlug.'.'.$nameSlug,
                    'name' => Str::headline($nameSlug),
                    'module' => $this->moduleLabel($moduleSlug),
                ];
            }

            return [
                'code' => 'admin.'.($parts[1] ?? 'general'),
                'name' => Str::headline($parts[1] ?? 'General'),
                'module' => 'Admin',
            ];
        }

        if ($prefix === 'ujian') {
            return [
                'code' => 'ujian.'.($parts[1] ?? 'general'),
                'name' => Str::headline($parts[1] ?? 'General'),
                'module' => 'Pelaksanaan Ujian',
            ];
        }

        if ($prefix === 'pengawas') {
            return [
                'code' => 'pengawas.'.($parts[1] ?? 'general'),
                'name' => Str::headline($parts[1] ?? 'General'),
                'module' => 'Pengawasan',
            ];
        }

        return [
            'code' => 'uncategorized',
            'name' => 'Uncategorized',
            'module' => 'System',
        ];
    }

    private function moduleLabel(string $slug): string
    {
        return match ($slug) {
            'identity' => 'Identity',
            'data-induk' => 'Data Induk',
            'ujian' => 'Ujian',
            'periode-ujian' => 'Penjadwalan',
            'pendaftaran-ujian' => 'Pendaftaran Ujian',
            'laporan' => 'Laporan',
            'catatan-aktivitas' => 'Audit',
            'sertifikat' => 'Sertifikat',
            'pengaturan' => 'Pengaturan',
            'analitik' => 'Analitik',
            'komponen' => 'Komponen',
            default => Str::headline($slug),
        };
    }

    private function actionFromRoute(string $routeName): string
    {
        $parts = explode('.', $routeName);

        return count($parts) > 3 ? (string) end($parts) : 'access';
    }
}
