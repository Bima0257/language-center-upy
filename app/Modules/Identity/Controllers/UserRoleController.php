<?php

namespace App\Modules\Identity\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\IndexUserRequest;
use App\Http\Requests\Identity\StoreUserBulkRequest;
use App\Http\Requests\Identity\StoreUserRequest;
use App\Http\Requests\Identity\UpdateUserRequest;
use App\Models\Pengguna;
use App\Modules\Identity\Contracts\IdentityContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    public function __construct(
        private IdentityContract $identity,
    ) {}

    public function index(IndexUserRequest $request): Response
    {
        $params = $request->validated();
        $params['tab'] = $params['tab'] ?? 'admin';

        return Inertia::render('Admin/Identity/User/Index', [
            'users' => $this->identity->listUsersPaginated($params),
            'userCounts' => $this->identity->countUsersByTab($params),
            'roles' => $this->identity->listRoleOptions(),
            'programStudis' => $this->identity->listProgramStudiOptions(),
            'filters' => $params,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Identity/User/Create', [
            'roles' => $this->identity->listRoleOptions(),
            'programStudis' => $this->identity->listProgramStudiOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $pengguna = $this->identity->createUser($request->validated());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->flashUserHighlight($pengguna, 'created', $request);

        return to_route('admin.identity.users.index', $this->redirectQuery($request))
            ->with('success', "Pengguna {$pengguna->name} berhasil dibuat.");
    }

    public function storeBulk(StoreUserBulkRequest $request): RedirectResponse
    {
        try {
            $count = $this->identity->createUserBulk($request->validated()['users']);
        } catch (\RuntimeException $exception) {
            $message = $exception->getMessage();
            $errors = ['users' => $message];

            if (preg_match('/^Baris (\d+): (.+)$/', $message, $matches) === 1) {
                $rowIndex = (int) $matches[1] - 1;
                $rowMessage = $matches[2];
                $field = str_contains($rowMessage, 'program studi')
                    ? 'program_studi_id'
                    : (str_contains($rowMessage, 'role') ? 'role_ids' : '');

                $errors["users.{$rowIndex}".($field !== '' ? ".{$field}" : '')] = $rowMessage;
            }

            throw ValidationException::withMessages($errors);
        }

        return to_route('admin.identity.users.index', $this->redirectQuery($request))
            ->with('success', "{$count} pengguna berhasil dibuat.");
    }

    public function update(UpdateUserRequest $request, Pengguna $pengguna): RedirectResponse
    {
        try {
            $pengguna = $this->identity->updateUser($pengguna, $request->validated());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->flashUserHighlight($pengguna, 'updated', $request);

        return back()->with('success', "Data {$pengguna->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, Pengguna $pengguna): RedirectResponse
    {
        try {
            $this->identity->deleteUser($pengguna);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Pengguna {$pengguna->name} berhasil dihapus.");
    }

    private function flashUserHighlight(Pengguna $pengguna, string $action, Request $request): void
    {
        $tab = $request->input('_query.tab', $request->input('tab', 'admin'));
        $params = array_merge($request->input('_query', []), ['tab' => $tab]);
        $page = $this->identity->findUserPage($pengguna->id, $params);
        $highlight = [
            'tableKey' => "identity-user-table-{$tab}",
            'rowIds' => [$pengguna->id],
            'action' => $action,
            'token' => (string) Str::uuid(),
        ];

        $currentPage = max(1, (int) ($request->input('_query.page', 1)));

        if ($page !== null && $page !== $currentPage) {
            $highlight['page'] = $page;
        }

        Inertia::flash('highlight', $highlight);
    }

    /**
     * @return array<string, mixed>
     */
    private function redirectQuery(Request $request): array
    {
        $query = array_merge($request->input('_query', []), $request->only([
            'q', 'sort', 'dir', 'page', 'per_page', 'tab',
            'f_role_names', 'f_status', 'f_program_studi_id',
        ]));

        return array_filter($query, fn ($value): bool => $value !== null && $value !== '');
    }
}
