<?php

namespace App\Modules\Identity\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\IndexRoleRequest;
use App\Http\Requests\Identity\StoreRoleRequest;
use App\Http\Requests\Identity\UpdateRoleRequest;
use App\Models\Role;
use App\Modules\Identity\Contracts\IdentityContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        private IdentityContract $identity,
    ) {}

    public function index(IndexRoleRequest $request): Response
    {
        $params = $request->validated();
        $params['tab'] = $params['tab'] ?? 'default';

        return Inertia::render('Admin/Identity/Role/Index', [
            'roles' => $this->identity->listRolesPaginated($params),
            'roleCounts' => $this->identity->countRolesByTab($params),
            'filters' => $params,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Identity/Role/Create', [
            'roles' => $this->identity->listRolesForForm(),
            'permissionCategories' => $this->identity->listPermissionCategoriesForForm(),
        ]);
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Admin/Identity/Role/Edit', $this->identity->getRoleForm($role));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        try {
            $role = $this->identity->createRole($request->validated());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->flashRoleHighlight($role, 'created', $request);

        return to_route('admin.identity.roles.index', $this->redirectQuery($request, 'custom'))
            ->with('success', "Role {$role->name} berhasil dibuat.");
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $role = $this->identity->updateRole($role, $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->flashRoleHighlight($role, 'updated', $request);

        return back()->with('success', "Role {$role->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        try {
            $this->identity->deleteRole($role);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Role {$role->name} berhasil dihapus.");
    }

    private function flashRoleHighlight(Role $role, string $action, Request $request): void
    {
        $tab = $request->input('_query.tab', $role->is_default ? 'default' : ($role->is_system ? 'system' : 'custom'));
        $params = array_merge($request->input('_query', []), ['tab' => $tab]);
        $page = $this->identity->findRolePage($role->id, $params);
        $highlight = [
            'tableKey' => "identity-role-table-{$tab}",
            'rowIds' => [(string) $role->id],
            'action' => $action,
            'token' => (string) Str::uuid(),
        ];

        if ($page !== null && $page !== max(1, (int) ($request->input('_query.page', 1)))) {
            $highlight['page'] = $page;
        }

        Inertia::flash('highlight', $highlight);
    }

    /**
     * @return array<string, mixed>
     */
    private function redirectQuery(Request $request, string $defaultTab): array
    {
        $query = array_merge($request->input('_query', []), $request->only([
            'q', 'sort', 'dir', 'page', 'per_page', 'tab',
        ]));

        $query['tab'] = $query['tab'] ?? $defaultTab;

        return array_filter($query, fn ($value): bool => $value !== null && $value !== '');
    }
}
