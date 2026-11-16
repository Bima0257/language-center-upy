<?php

namespace App\Modules\Identity\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\IndexPermissionCategoryRequest;
use App\Http\Requests\Identity\UpdatePermissionCategoryRequest;
use App\Http\Requests\Identity\UpdatePermissionModuleRequest;
use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Modules\Identity\Contracts\IdentityContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function __construct(
        private IdentityContract $identity,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Identity/Permission/Index', [
            'permissionModules' => $this->identity->listPermissionModules(),
        ]);
    }

    public function showModule(IndexPermissionCategoryRequest $request): Response
    {
        $request->validate(['module' => ['required', 'string', 'max:100']]);
        $module = $this->identity->getPermissionModule((string) $request->query('module'));

        abort_if($module === null, 404);

        return Inertia::render('Admin/Identity/Permission/Module', [
            'module' => $module,
            'permissionCategories' => $this->identity->listPermissionCategoriesPaginated($request->validated()),
            'filters' => $request->validated(),
            'uncategorizedPermissions' => $this->identity->listUncategorizedPermissions(),
        ]);
    }

    public function scan(): RedirectResponse
    {
        $result = $this->identity->scanPermissions();

        return back()->with('success', "Scan selesai: {$result['permissions']} permission dari {$result['routes']} route.");
    }

    public function updateModule(UpdatePermissionModuleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($data['current_module'] === $data['module']) {
            return back()->with('error', 'Nama module baru harus berbeda.');
        }

        $count = $this->identity->renameModule($data['current_module'], $data['module']);

        return back()->with('success', "{$count} kategori berhasil dipindahkan ke module {$data['module']}.");
    }

    public function updateCategory(UpdatePermissionCategoryRequest $request, PermissionCategory $permissionCategory): RedirectResponse
    {
        try {
            $this->identity->updatePermissionCategory($permissionCategory, $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->flashCategoryHighlight($permissionCategory, $request);

        return back()->with('success', 'Kategori permission berhasil diperbarui.');
    }

    public function assign(PermissionCategory $permissionCategory, Permission $permission): RedirectResponse
    {
        try {
            $this->identity->assignPermission($permissionCategory, $permission);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Permission berhasil ditambahkan ke kategori.');
    }

    public function unassign(Permission $permission): RedirectResponse
    {
        $this->identity->unassignPermission($permission);

        return back()->with('success', 'Permission dikembalikan ke uncategorized.');
    }

    public function reset(Permission $permission): RedirectResponse
    {
        $this->identity->resetPermissionCategory($permission);

        return back()->with('success', 'Kategori permission berhasil direset otomatis.');
    }

    private function flashCategoryHighlight(PermissionCategory $category, Request $request): void
    {
        $params = array_merge($request->input('_query', []), $request->only([
            'q', 'sort', 'dir', 'page', 'per_page', 'f_module',
        ]));
        $page = $this->identity->findPermissionCategoryPage($category->id, $params);
        $highlight = [
            'tableKey' => 'identity-permission-category-table',
            'rowIds' => [(string) $category->id],
            'action' => 'updated',
            'token' => (string) Str::uuid(),
        ];

        if ($page !== null && $page !== max(1, (int) ($request->input('_query.page', 1)))) {
            $highlight['page'] = $page;
        }

        Inertia::flash('highlight', $highlight);
    }
}
