# Pola Fitur Identity: User, Role & Permission

Blueprint untuk menyalin **logic backend, flow, layout, dan UI** fitur Identity ke project lain dengan stack modular monolith + Spatie Permission + Inertia. Palet warna mengikuti design system project target.

- Sumber acuan: `resources/js/Pages/Admin/Identity/`, `app/Modules/Identity/`, `bootstrap/app.php`, `routes/admin.php`.
- Stack acuan: Laravel + Inertia v2 + Vue 3 `<script setup>` + Spatie Permission + TanStack Table v9 + Tailwind CSS v4 + Ziggy.
- Status: blueprint pola, bukan source code siap pakai.
- Nuansa UI: layout/struktur disalin; warna, shadow, dan token visual memakai design system project target.

> **Aturan warna:** dokumen ini tidak menetapkan nilai warna. Kolom referensi pada tabel mapping hanya menunjuk elemen; isi kolom token target dengan token design system project target.

## 1. Ruang lingkup

### Termasuk

1. **User management**
    - Daftar pengguna per tab `admin`, `dosen`, `mahasiswa`.
    - Server-side table: search, filter role/status/program studi, sort, pagination.
    - Edit pengguna via modal.
    - Modal daftar role (read-only).
    - Hapus pengguna dengan guard.
    - Bulk create maksimal 10 pengguna.
    - Flash highlight baris setelah create/update.
2. **Role management**
    - Tab `default`, `system`, `custom`.
    - CRUD role custom.
    - Inheritance role (`role_parents`).
    - Grant/deny permission per role.
    - Permission group table + modal per kategori.
    - Guard default/system role.
3. **Permission management**
    - Scan permission dari route bernama.
    - Daftar modul permission.
    - Detail modul + kategori.
    - Edit kategori permission dan assign/unassign permission.
    - Reset kategori otomatis.
    - Rename module.
4. **RBAC runtime**
    - Effective permission dengan inheritance dan denial.
    - Middleware route permission.
    - Share permission ke Inertia + helper `can()` frontend.

### Dikecualikan

- **Sinkronisasi Role↔Jabatan**: `provisionRoleForJabatan`, `renameRoleForJabatan`, `deleteRoleForJabatan`, `syncJabatanRoles`, `attachJabatanRoleToDosen`, `detachJabatanRoleFromDosen`, `detachStaleJabatanRoles`, dan seluruh menu Jabatan.
- Kolom `roles.jabatan_id` dan `roles.role_source`, badge "Jabatan", serta guard nama role jabatan hanya dicatat sebagai **integration boundary opsional**. Jika target tidak punya fitur Jabatan, hapus kolom, badge, dan guard tersebut.
- Autentikasi/login, profil pengguna, dashboard, dan portal non-admin tidak dibahas.

## 2. Stack dan dependensi

| Lapisan       | Kebutuhan                            | Dipakai untuk                                             |
| ------------- | ------------------------------------ | --------------------------------------------------------- |
| Backend       | Laravel + modular monolith           | Contract → Service, Controller tipis                      |
| Auth/RBAC     | Spatie Permission + model kustom     | roles, permissions, model_has_roles, role_has_permissions |
| Inertia       | Inertia v2                           | render props, partial reload, `Inertia::flash`            |
| Validasi      | FormRequest turunan BaseFormRequest  | aturan user/role/permission                               |
| UUID          | BaseModel + HasUuid                  | users, permission_categories, user_program_studi          |
| Tabel         | AppDataTable + TanStack Table v9     | server-side table, filter, sort, pagination               |
| Tabs          | AppTabs varian `curved` + `attached` | tab user/role/assignment                                  |
| Bulk          | AppBulkRows + `useBulkRows`          | create pengguna massal                                    |
| Permission UI | `usePermission`                      | `can()` dan `canAny()`                                    |
| Konfirmasi    | `useConfirm`                         | hapus user/role, clear permission                         |
| Notifikasi    | `useNotification`                    | feedback aksi di role form                                |
| State tabel   | `tableStateQuery`                    | URL kembali ke index dengan state tabel                   |

Dependensi visual minimum: `PageHeader`, `AppButton`, `AppModal`, `AppInput`, `AppSelect`, `AppCheckbox`, `AppCheckboxGroup`, `AppSwitch`, `AppPasswordChecklist`, `AppInfoModal`, `AppInfoField`, `AppIcon`, `floating-vue` (`v-tooltip`).

## 3. Arsitektur backend

### 3.1 Struktur modul

```text
app/Modules/Identity/
|-- Contracts/
|   `-- IdentityContract.php
|-- Controllers/
|   |-- UserRoleController.php
|   |-- RoleController.php
|   `-- PermissionController.php
|-- Models/
|   |-- User.php
|   |-- Role.php
|   |-- Permission.php
|   |-- PermissionCategory.php
|   `-- UserProgramStudi.php
|-- Requests/
|-- Routes/
|   `-- admin.php
|-- Services/
|   `-- IdentityService.php
`-- Providers/
    `-- IdentityServiceProvider.php
```

Jika service menjadi sangat besar (acuan ±1.850 baris), pecah per tanggung jawab: `UserService`, `RoleService`, `PermissionService`, `PermissionScanner`, dengan satu contract fasad. Jangan mengubah kontrak publik tanpa kebutuhan.

### 3.2 Binding contract

```php
$this->app->bind(IdentityContract::class, IdentityService::class);
```

Controller bergantung pada `IdentityContract`, bukan pada service konkret.

### 3.3 Route group portal

```php
Route::middleware(['auth', PortalGuard::class.':admin', 'route.permission'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        (new ModuleServiceProvider(app()))->loadPortalRoutes('admin');
    });
```

Group Identity menambahkan middleware `superadmin` dan prefix `/identity`:

```php
Route::middleware('superadmin')->prefix('/identity')->group(function (): void {
    // users, roles, permissions
});
```

### 3.4 Pola controller tipis

```php
public function index(Request $request): Response
{
    $tableParams = $request->only([
        'q', 'sort', 'dir', 'page', 'per_page', 'tab',
        'f_role_names', 'f_status', 'f_program_studi_id',
    ]);

    $tableParams['tab'] = in_array($tableParams['tab'] ?? null, ['admin', 'dosen', 'mahasiswa'], true)
        ? $tableParams['tab']
        : 'admin';

    return Inertia::render('Admin/Identity/User/Index', [
        'users' => $this->identity->listUsersPaginated($tableParams),
        'userCounts' => $this->identity->countUsersByTab($tableParams),
        'roles' => $this->identity->listRoles(),
        'programStudis' => $this->masterData->getProgramStudiOptions(),
    ]);
}
```

Aturan:

- Controller hanya menangani HTTP: whitelist query, render props, redirect, flash.
- Validasi di FormRequest.
- Business rule, transaksi, dan query kompleks di service.
- Jangan query Eloquent langsung dari controller.
- Gunakan `ModuleException` untuk aturan bisnis dan `ValidationException` untuk error per field.

### 3.5 Middleware

| Middleware         | Fungsi                             |
| ------------------ | ---------------------------------- |
| `auth`             | Wajib login                        |
| `portal`           | Guard portal admin/dosen/mahasiswa |
| `route.permission` | Cek nama route sebagai permission  |
| `superadmin`       | Hanya role superadmin              |

```php
class EnforceRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Actor) {
            abort(403);
        }

        if ($this->identity->userHasRole($user, 'superadmin')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        abort_if(blank($routeName), 403);
        abort_unless($this->identity->userHasPermission($user, $routeName), 403);

        return $next($request);
    }
}
```

Permission = nama route (`admin.identity.users.index`, dst). Tidak ada pemetaan permission terpisah.

## 4. Data model

### 4.1 Tabel utama

| Tabel                     | Kolom kunci                                                                                                                                                          | Catatan                                                              |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| `users`                   | `id` UUID, `name`, `username` unique, `email` nullable unique, `password`, `status` bool default true, `last_login` nullable, `dosen_id`, `mahasiswa_id`, timestamps | Gunakan BaseModel/UUID. Kolom `type` legacy tidak dipakai fitur ini. |
| `user_program_studi`      | `id` UUID, `user_id`, `program_studi_id`, unique pasangan, timestamps                                                                                                | Satu user satu prodi pada model saat ini                             |
| `roles`                   | `id`, `name`, `guard_name`, `is_system`, `is_default`, timestamps                                                                                                    | Model kustom extends Spatie Role                                     |
| `permissions`             | `id`, `name`, `guard_name`, `permission_category_id` UUID nullable, `route_name`, `action`, `is_system`, `category_assignment_source`, `last_scanned_at`, timestamps | Model kustom extends Spatie Permission                               |
| `permission_categories`   | `id` UUID, `code` unique, `name`, `module`, `is_system`, timestamps                                                                                                  | `uncategorized` adalah kategori sistem                               |
| `role_parents`            | `role_id`, `parent_role_id`, PK komposit, FK cascade                                                                                                                 | Inheritance                                                          |
| `role_permission_denials` | `role_id`, `permission_id`, PK komposit, FK cascade                                                                                                                  | Deny menimpa grant                                                   |

### 4.2 Tabel Spatie

`model_has_roles`, `role_has_permissions`, dan tabel permission dasar tetap dipakai. Tambahan metadata berada di tabel `permissions` dan `roles` melalui model kustom.

### 4.3 Index yang disarankan

- `users(username)` unique, `users(email)` unique nullable, `users(status)`.
- `permissions(route_name)`, `permissions(permission_category_id)`.
- `permission_categories(code)` unique, `permission_categories(module)`.
- `role_parents(role_id, parent_role_id)` PK, `role_permission_denials(role_id, permission_id)` PK.
- `user_program_studi(user_id, program_studi_id)` unique.

### 4.4 Catatan tipe ID

Pada sumber, `users` dan `permission_categories` memakai UUID, sedangkan `roles`/`permissions` Spatie memakai bigint. `role_parents` dan `role_permission_denials` memakai `unsignedBigInteger`. Jika target memakai UUID untuk semua tabel, sesuaikan tipe kolom pivot dan foreign key secara konsisten.

## 5. RBAC dan authorization

### 5.1 Inheritance dan denial

- Permission efektif = gabungan permission seluruh ancestor role + permission langsung, dikurangi permission yang masuk `role_permission_denials`.
- Deny selalu menang atas grant, termasuk grant warisan.
- `superadmin` bypass seluruh pengecekan.
- Inheritance tidak boleh membentuk siklus, tidak boleh menjadikan role sebagai parent dirinya sendiri, dan tidak boleh melibatkan `superadmin`.

```php
public function effectivePermissions(Role $role): Collection
{
    if ($role->name === 'superadmin') {
        return Permission::query()->get();
    }

    $roles = $this->ancestorRoles($role);
    $roles = Role::query()->with('permissions')->whereIn('id', $roles->pluck('id'))->get();

    $permissionIds = $roles
        ->flatMap(fn (Role $currentRole): Collection => $currentRole->permissions->pluck('id'))
        ->unique()
        ->values();

    $permissions = Permission::query()->whereIn('id', $permissionIds)->get()->keyBy('id');
    $denied = DB::table('role_permission_denials')
        ->whereIn('role_id', $roles->pluck('id'))
        ->pluck('permission_id');

    return $permissions->except($denied->all())->values();
}
```

`ancestorRoles()` melakukan BFS/DFS dengan visited set untuk mencegah loop.

### 5.2 Share ke frontend

`HandleInertiaRequests::share()` mengirim:

```php
'auth' => [
    'user' => $actor,
    'effective_role_names' => $effectiveRoleNames,
    'permissions' => $effectivePermissionNames,
],
```

Frontend:

```js
export function usePermission() {
    const page = usePage();
    const userRoles = computed(
        () => page.props.auth?.user?.roles?.map((role) => role.name) ?? [],
    );
    const permissions = computed(() => page.props.auth?.permissions ?? []);

    function can(permission) {
        return (
            userRoles.value.includes("superadmin") ||
            permissions.value.includes(permission)
        );
    }

    function canAny(...permissionNames) {
        return permissionNames.some((permission) => can(permission));
    }

    return { can, canAny };
}
```

`can()` hanya untuk UX (sembunyikan tombol/aksi). Enforcement tetap di middleware backend. Jangan menampilkan data sensitif hanya karena frontend menyembunyikan tombol.

### 5.3 Route sebagai permission

- Nama permission = nama route.
- Scanner hanya mengambil route bernama dengan prefix `admin.`, `dosen.`, `mahasiswa.`, dan tidak berakhiran titik.
- Route yang dikecualikan dari middleware (login, media, profil) harus eksplisit `withoutMiddleware('route.permission')`.

## 6. Backend User management

### 6.1 Index dan query

Parameter yang diizinkan: `q`, `sort`, `dir`, `page`, `per_page`, `tab`, `f_role_names`, `f_status`, `f_program_studi_id`.

Aturan:

- `tab` hanya `admin`, `dosen`, `mahasiswa`; default `admin`.
- Exclude user yang punya role `superadmin` dari daftar.
- Search ke `name`, `username`, `email`.
- Tab memfilter user yang memiliki role turunan dari role default tab tersebut.
- `f_role_names` memfilter satu nama role.
- `f_status` dikirim sebagai string `'true'`/`'false'` dari query string.
- `f_program_studi_id` memfilter lewat relasi `programStudis`.
- Sort whitelist: `name`, `username`, `email`, `status`, `last_login`; tie-breaker `name`, `id`.
- `per_page` whitelist: `10`, `25`, `50`, `100`; fallback `10`.

```php
User::query()
    ->whereDoesntHave('roles', fn (Builder $query) => $query->where('name', 'superadmin'))
    ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
        $inner->where('users.name', 'like', "%{$search}%")
            ->orWhere('users.username', 'like', "%{$search}%")
            ->orWhere('users.email', 'like', "%{$search}%");
    }))
    ->when(isset($params['tab']), fn (Builder $query) => $query->whereHas(
        'roles',
        fn (Builder $roleQuery) => $roleQuery->whereIn('roles.id', $tabRoleIds[$params['tab']]),
    ))
    ->when(filled($params['f_role_names'] ?? null), fn (Builder $query) => $query->whereHas(
        'roles',
        fn (Builder $roleQuery) => $roleQuery->where('roles.name', $params['f_role_names']),
    ))
    ->when(in_array((string) ($params['f_status'] ?? ''), ['true', 'false'], true), fn (Builder $query) => $query->where(
        'users.status',
        $params['f_status'] === 'true',
    ))
    ->when(filled($params['f_program_studi_id'] ?? null), fn (Builder $query) => $query->whereHas(
        'programStudis',
        fn (Builder $programStudiQuery) => $programStudiQuery->where('program_studi_id', $params['f_program_studi_id']),
    ))
    ->orderBy($sortColumn, $direction)
    ->orderBy('users.name')
    ->orderBy('users.id');
```

Tab role IDs dihitung dari ancestor role yang berisi role default `admin`/`dosen`/`mahasiswa`. Cache hasil per request agar tidak dihitung berulang.

### 6.2 Counts dan halaman highlight

`countUsersByTab()` menjalankan builder yang sama untuk tiga tab. `findUserPage()` menghitung halaman target memakai window function:

```php
$rankedUsers = $this->buildUsersQuery($params)
    ->select('users.id')
    ->selectRaw(
        'ROW_NUMBER() OVER (ORDER BY '.$sortColumn.' '.$direction.', users.name asc, users.id asc) as row_number',
    );

$rowNumber = DB::query()
    ->fromSub($rankedUsers, 'ranked_users')
    ->where('id', $userId)
    ->value('row_number');

return $rowNumber === null ? null : (int) ceil((int) $rowNumber / $perPage);
```

`ROW_NUMBER()` membutuhkan PostgreSQL atau MySQL 8+. Pastikan `$sortColumn` berasal dari whitelist, bukan input mentah.

### 6.3 Bentuk baris user

```php
[
    'id' => $user->id,
    'name' => $user->name,
    'username' => $user->username,
    'email' => $user->email,
    'status' => $user->status,
    'last_login' => $user->last_login?->format('Y-m-d H:i'),
    'roles' => [['id' => 1, 'name' => 'admin'], ...],
    'role_names' => ['admin', ...],
    'program_studi_id' => $user->programStudis->value('program_studi_id'),
]
```

Eager load `roles` dan `programStudis` untuk menghindari N+1.

### 6.4 Create user

Validasi `StoreUserRequest`:

- `name` required, max 100.
- `username` required, max 100, tanpa spasi (`regex:/^\S+$/u`), unique.
- `email` nullable, email, max 100, unique.
- `password` required, `Password::min(8)->letters()->mixedCase()->numbers()->symbols()`, confirmed.
- `status` boolean.
- `role_ids` required array min 1, setiap item exists di roles.
- `program_studi_id` nullable UUID exists.
- Setelah validasi: role dosen/mahasiswa wajib punya program studi.

Service `createUser`:

1. Buka transaksi.
2. `assertAdminAccountRoleSelection()`: role selain `admin`/`dosen`/`mahasiswa` hanya boleh jika berada di cabang admin.
3. Isi dan simpan user (`password` otomatis di-hash oleh cast model).
4. `syncUserRoles()`.
5. `syncUserProgramStudi()`.
6. Commit.

### 6.5 Bulk create user

- `StoreUserBulkRequest`: `users` array min 1 max 10; aturan per baris sama dengan create; `role_ids.*` distinct; unique username/email per baris.
- Validasi tambahan: username/email duplikat di dalam daftar; prodi wajib per baris untuk role dosen/mahasiswa.
- Service `createUserBulk` menjalankan `createUser` per baris di dalam satu transaksi; error dibungkus `Baris N: pesan`.
- Controller menangkap `ModuleException` dan memetakan pesan ke error field:

```php
$errors = ['users' => $message];

if (preg_match('/^Baris (\d+): (.+)$/', $message, $matches) === 1) {
    $rowIndex = (int) $matches[1] - 1;
    $rowMessage = $matches[2];
    $field = str_contains($rowMessage, 'program studi')
        ? 'program_studi_id'
        : (str_contains($rowMessage, 'Role') ? 'role_ids' : '');

    $errors["users.{$rowIndex}".($field !== '' ? ".{$field}" : '')] = $rowMessage;
}

throw ValidationException::withMessages($errors);
```

- Flash success berisi jumlah user yang dibuat.
- Highlight hanya dikirim jika jumlah user baru ≤ 10.
- Redirect ke index dengan query tabel (`q`, `sort`, `dir`, `page`, `per_page`, `tab`, filter) agar state tidak hilang.

UI memakai bulk create untuk akun admin. Akun dosen/mahasiswa umumnya dibuat dari modul Dosen/Mahasiswa masing-masing; route `store` single tetap tersedia sebagai jalur API/internal.

### 6.6 Update user

- Validasi `UpdateUserRequest`; `username`/`email` unique mengabaikan ID saat ini.
- Password nullable; jika kosong, jangan ubah.
- Untuk akun dosen (`dosen_id` tidak null): `role_ids` dan `program_studi_id` **prohibited**; role/prodi dikelola dari data Dosen/Jabatan.
- Service menolak perubahan role/prodi untuk akun dosen dengan `ModuleException`.
- Jika role berubah, jalankan `syncUserRoles()` lalu `syncUserProgramStudi()`.

### 6.7 Hapus user

- Tolak menghapus akun sendiri (`auth()->id() === $user->id`).
- Tolak menghapus user dengan role `superadmin`.
- Detach seluruh role, lalu hapus user.

### 6.8 Guard pemilihan role dan program studi

- `superadmin` tidak bisa diberikan lewat pengelolaan user biasa.
- Akun tidak boleh memiliki role admin general (`admin` default) dan admin prodi (turunan `admin`) sekaligus.
- Role `mahasiswa` tidak boleh digabung dengan role lain.
- Role di luar cabang admin hanya boleh dipilih untuk akun pada cabang admin.
- Program studi wajib untuk admin prodi, dosen, dan mahasiswa; selain itu prodi di-null-kan.
- `syncUserProgramStudi()` menghapus mapping lama lalu membuat satu mapping baru di `user_program_studi`.

### 6.9 Flash highlight user

```php
$highlight = [
    'tableKey' => "identity-user-table-{$tab}",
    'rowIds' => [$userId],
    'action' => 'created', // atau 'updated'
    'token' => (string) Str::uuid(),
];

$page = $this->identity->findUserPage($userId, $request->query());
if ($page !== null && $page !== max(1, (int) $request->query('page', 1))) {
    $highlight['page'] = $page;
}

Inertia::flash('highlight', $highlight);
```

`tableKey` harus sama persis dengan `table-key` komponen `AppDataTable` pada tab terkait.

## 7. Backend Role management

### 7.1 Index dan query

- Tab: `default`, `system`, `custom`; default `default`.
- Exclude role `superadmin`.
- Search nama role.
- Filter tab: `is_default = true`; `is_system = true AND is_default = false`; `is_system = false AND is_default = false`.
- `withCount('users')`.
- Sort whitelist: `name`, `users_count`, `is_system`, `is_default`; tie-breaker `name`, `id`.

### 7.2 Bentuk baris role

```php
[
    'id' => $role->id,
    'name' => $role->name,
    'is_system' => $role->is_system,
    'is_default' => $role->is_default,
    'users_count' => $role->users_count,
    'parents' => $role->parents->pluck('name')->all(),
    'parent_ids' => $role->parents->pluck('id')->all(),
    'grant_permission_ids' => $role->permissions->pluck('id')->all(),
    'deny_permission_ids' => $role->deniedPermissions->pluck('id')->all(),
    'inherited_permission_ids' => $this->inheritedPermissionIds($role),
]
```

`inherited_permission_ids` = permission milik seluruh ancestor (tanpa role itu sendiri). Eager load `parents`, `permissions`, `deniedPermissions`.

### 7.3 Create role

1. Normalisasi nama ke slug: `Str::slug($name, '-')`; tolak jika kosong.
2. Pastikan nama belum dipakai.
3. Simpan role (`guard_name = web`).
4. `syncRoleAccess()`:
    - validasi parent;
    - `parents()->sync($parentRoleIds)`;
    - `syncPermissions($grantPermissionIds)`;
    - `deniedPermissions()->sync($denyPermissionIds)`.
5. Semua dalam transaksi.

`parent_role_ids` wajib minimal satu pada create.

### 7.4 Parent validation

- Role tidak boleh menjadi parent dirinya sendiri.
- `superadmin` tidak boleh menjadi parent atau anak dalam inheritance.
- Tidak boleh membentuk siklus.

```php
if (in_array((int) $role->id, $parentRoleIds, true)) {
    throw new ModuleException('Role tidak boleh menjadi parent bagi dirinya sendiri.');
}

if ($role->name === 'superadmin'
    || in_array('superadmin', Role::query()->whereIn('id', $parentRoleIds)->pluck('name')->all(), true)) {
    throw new ModuleException('Role superadmin tidak dapat digunakan dalam inheritance role.');
}

foreach ($parents as $parent) {
    if ($this->ancestorRoles($parent)->contains(fn (Role $ancestor): bool => $ancestor->id === $role->id)) {
        throw new ModuleException('Inheritance role membentuk siklus yang tidak valid.');
    }
}
```

### 7.5 Update role

- `superadmin` tidak dapat diubah.
- Role default: nama dan parent tidak berubah; permission tetap dapat diubah.
- Role non-default/non-jabatan: nama dapat diubah setelah normalisasi dan cek ketersediaan.
- Role jabatan (opsional, di luar cakupan): nama dikelola menu Jabatan; jika target tidak punya Jabatan, hapus guard ini.
- `syncRoleAccess()` selalu dijalankan.

### 7.6 Delete role

- Role default tidak dapat dihapus.
- Role yang masih dipakai user atau masih menjadi parent (punya children) tidak dapat dihapus.
- Role jabatan (opsional) hanya dapat dihapus dari menu Jabatan.
- Hapus role setelah guard lolos.

### 7.7 Flash highlight role

```php
$highlight = [
    'tableKey' => "identity-role-table-{$tab}",
    'rowIds' => [(string) $role->id],
    'action' => 'created', // atau 'updated'
    'token' => (string) Str::uuid(),
];
```

Halaman target dihitung dengan `findRolePage()` memakai `ROW_NUMBER()` dan query yang sama dengan index.

## 8. Backend Permission management

### 8.1 Scanner permission

Scanner membaca seluruh route bernama dengan prefix `admin.`, `dosen.`, `mahasiswa.`, mengabaikan nama berakhiran titik, lalu:

1. Tentukan kategori dari nama route.
2. `firstOrCreate` kategori berdasarkan `code`.
3. `firstOrNew` permission berdasarkan `name` + `guard_name = web`.
4. Pertahankan `permission_category_id` jika `category_assignment_source = manual`.
5. Isi `route_name`, `action`, `is_system = true`, `category_assignment_source` default `scanner`, `last_scanned_at = now()`.
6. Tandai permission lama yang route-nya tidak ditemukan dengan `last_scanned_at = null` (tidak dihapus).
7. Hapus kategori kosong yang tidak lagi dipakai, kecuali `uncategorized`.

Derivasi kategori:

| Prefix route        | Contoh                       | `code`                 | `name`           | `module`           |
| ------------------- | ---------------------------- | ---------------------- | ---------------- | ------------------ |
| `admin.`            | `admin.identity.users.index` | `identity.users`       | `Users`          | `Identity`         |
| `admin.` (2 segmen) | `admin.dashboard`            | `admin.dashboard`      | `Dashboard`      | `Admin`            |
| `dosen.`            | `dosen.presensi-kelas.index` | `dosen.presensi-kelas` | `Presensi Kelas` | `Dosen`            |
| `mahasiswa.`        | `mahasiswa.krs.index`        | `portal-mahasiswa.krs` | `Krs`            | `Portal Mahasiswa` |
| tidak dikenali      | -                            | `uncategorized`        | `Uncategorized`  | `System`           |

`action` = segmen terakhir jika jumlah segmen > 3; selain itu `access`.

```php
$routes = collect($this->router->getRoutes()->getRoutes())
    ->filter(fn ($route): bool => filled($route->getName()))
    ->filter(fn ($route): bool => ! str_ends_with((string) $route->getName(), '.'))
    ->filter(fn ($route): bool => collect(['admin.', 'dosen.', 'mahasiswa.'])
        ->contains(fn (string $prefix): bool => str_starts_with($route->getName(), $prefix)));
```

Scanner harus idempoten dan dijalankan dalam transaksi. Scanner tidak menghapus permission; hanya menandai stale.

### 8.2 Module list

Kelompokkan kategori yang punya permission berdasarkan `module`:

```php
[
    'module' => 'Identity',
    'categories_count' => 3,
    'permissions_count' => 24,
    'status' => 'System', // System | Custom | Campuran
]
```

`status`:

- `System` jika semua kategori `is_system = true`.
- `Custom` jika semua `is_system = false`.
- `Campuran` jika bercampur.

### 8.3 Module detail

`getPermissionModule($module)`:

- Ambil kategori pada module yang `whereHas('permissions')`.
- Eager load permission terurut nama.
- Kembalikan `module`, `categories_count`, `permissions_count`, dan daftar kategori beserta permission.
- Jika kosong, kembalikan `null` → controller `abort(404)`.

### 8.4 Pagination kategori

- Query hanya kategori yang punya permission.
- `withCount('permissions')`.
- Search ke `module`, `name`, `code`.
- Filter `f_module`.
- Sort whitelist: `module`, `name`, `code`, `permissions_count`, `is_system`.
- `per_page` whitelist.
- Eager load permissions untuk baris yang ditampilkan.
- `findPermissionCategoryPage()` memakai `ROW_NUMBER()` untuk highlight.

### 8.5 Edit kategori

Aturan:

- `name` dan `module` wajib.
- `permission_ids` opsional array; setiap ID harus ada.
- Hanya permission dari kategori `uncategorized` yang boleh ditambahkan.
- Permission yang dikeluarkan dari kategori dipindahkan ke `uncategorized` dengan `category_assignment_source = manual`.
- Permission yang ditambahkan dari `uncategorized` dipindahkan ke kategori dengan source `manual`.
- Kategori `uncategorized` tidak boleh menerima `permission_ids`; edit hanya nama/module.

```php
if ($permissionIds === []) {
    Permission::query()
        ->where('permission_category_id', $category->id)
        ->update([
            'permission_category_id' => $uncategorized->id,
            'category_assignment_source' => 'manual',
        ]);
} else {
    Permission::query()
        ->where('permission_category_id', $category->id)
        ->whereNotIn('id', $permissionIds)
        ->update([
            'permission_category_id' => $uncategorized->id,
            'category_assignment_source' => 'manual',
        ]);

    Permission::query()
        ->whereIn('id', $permissionIds)
        ->where('permission_category_id', $uncategorized->id)
        ->update([
            'permission_category_id' => $category->id,
            'category_assignment_source' => 'manual',
        ]);
}
```

### 8.6 Assign, unassign, reset

- `assign`: hanya permission dari `uncategorized`; set kategori tujuan + source `manual`.
- `unassign`: pindahkan permission ke `uncategorized` + source `manual`.
- `reset`: hitung ulang kategori otomatis dari `route_name` via `categoryData()`; jika tidak dikenali, gunakan `uncategorized`; set source `scanner`.
- Semua dalam transaksi dan kembalikan model terbaru.
- Flash highlight kategori memakai `rowIds` kategori lama dan baru.

### 8.7 Rename module

- `current_module` harus ada di `permission_categories`.
- `module` baru harus berbeda.
- Update seluruh kategori pada module lama.
- Kembalikan jumlah baris terupdate untuk pesan flash.

## 9. Flow utama

### 9.1 User index

1. Route `admin.identity.users.index` merender `User/Index`.
2. Controller memvalidasi `tab`, mengambil paginator user, counts per tab, daftar role, dan opsi program studi.
3. UI merender `PageHeader`, `AppTabs`, dan satu panel tab aktif.
4. `AppDataTable` server-side memuat data tab aktif dengan `table-key` unik.
5. Search/filter/sort/page memicu partial reload `users` (dan query string).
6. Klik Edit membuka modal dengan data baris.
7. Simpan mengirim `PUT` dengan `_query` state tabel.
8. Controller flash success + highlight; tabel reload dan menyorot baris.
9. Klik Hapus memunculkan konfirmasi; jika lolos guard, `DELETE` dan reload.
10. Klik "+N lainnya" membuka modal role read-only.

### 9.2 User bulk create

1. Route `admin.identity.users.create` merender `User/Create`.
2. UI menampilkan `AppBulkRows` kosong dan tombol tambah.
3. Modal add/edit memvalidasi field secara client-side.
4. Baris disimpan ke state lokal; duplikat menandai baris dan scroll ke baris baru.
5. Submit mengirim seluruh `users` ke `admin.identity.users.bulk.store`.
6. Backend memvalidasi seluruh baris; error per baris dipetakan ke `users.N.field`.
7. UI scroll ke baris pertama yang error.
8. Sukses: redirect ke index dengan query tabel + flash highlight (jika ≤ 10).

### 9.3 Role create/edit

1. Index role menampilkan tab default/system/custom.
2. Tambah role hanya tersedia di tab custom.
3. Form role mengisi nama, parent, dan permission.
4. Permission diwarisi dari parent; menghapus checklist permission warisan menambahkan deny.
5. Pilih Semua / Reset / Hapus Semua bekerja pada state lokal sampai disimpan.
6. Simpan memvalidasi parent (siklus, self, superadmin) dan menyinkronkan parent + grant + deny.
7. Redirect ke index tab yang sesuai + flash highlight role.

### 9.4 Permission scan dan kategori

1. Index permission menampilkan daftar modul dan tombol Scan.
2. Scan memanggil route `permissions.scan`; backend memindai route dan mengembalikan ringkasan.
3. Klik detail membuka halaman module.
4. Halaman module menampilkan ringkasan dan tabel kategori.
5. Klik detail kategori membuka modal daftar permission (action, route, sumber).
6. Klik edit membuka modal edit kategori.
7. Tambah permission hanya dari `uncategorized`; hapus permission mengembalikannya ke `uncategorized`.
8. Simpan mengirim `PUT` dan flash highlight kategori.
9. Rename module dari index memperbarui semua kategori pada module tersebut.

## 10. Layout dan UI

### 10.1 Shell halaman admin

- Semua halaman dibungkus `AdminLayout` dengan prop `user`.
- `Head` mengisi title.
- `PageHeader` adalah card dengan:
    - tombol kembali opsional (lingkaran, ikon panah, border aksen);
    - slot `title` untuk eyebrow + judul;
    - subtitle;
    - slot aksi kanan (biasanya tombol "Panduan").
- Konten halaman disusun vertikal dengan jarak konsisten.
- Tombol "Panduan" membuka `AppInfoModal` berisi 3–4 blok ikon + penjelasan.

### 10.2 Tabs

- Varian `curved` + `attached`.
- Setiap tab memiliki `value`, `label`, dan `count`.
- Hanya panel tab aktif yang dirender (`visibleTabKeys = [activeTab]`).
- Ganti tab memperbarui query `tab`; hapus `page` dan seluruh `f_*` agar filter tab sebelumnya tidak bocor.
- Gunakan `router.get(..., { preserveState: true, preserveScroll: true, replace: true })`.
- Panel memakai `role="tabpanel"` dan `aria-labelledby`.

### 10.3 User index

- `PageHeader`: judul "Daftar Pengguna", subtitle, tombol Panduan.
- `AppTabs` dengan count admin/dosen/mahasiswa.
- `UserTable` per tab:
    - `AppDataTable` server-side, page size 10.
    - `table-key` = `identity-user-table-{tab}`.
    - `reload-only` = `['users']` (tambahkan prop filter/count jika berubah).
    - slot `actions` hanya di tab admin: tombol "Tambah Pengguna".
- Kolom:
    - Nama: semibold.
    - Username.
    - Email: teks muted, fallback `-`.
    - Role: tampilkan maksimum N nama (1 untuk dosen, 2 lainnya); jika lebih, tampilkan tombol `+X lainnya` yang membuka modal role.
    - Status: badge pill "Aktif"/"Nonaktif" dengan tint success/error.
    - Terakhir Login: format `id-ID` tanggal + jam; fallback `-`.
    - Aksi: tombol bulat 32×32 Edit dan Hapus dengan tooltip.
- Hapus:
    - Konfirmasi modal (judul, pesan nama, tombol danger).
    - Tombol hapus tetap tampil jika user punya izin, tetapi klik tidak melakukan apa pun untuk akun sendiri/superadmin. Untuk UX lebih baik, nonaktifkan atau sembunyikan tombol tersebut.
- Edit modal:
    - Field: Nama, Username, Email, Password + checklist, Konfirmasi + checklist, Role checkbox group, Program Studi (conditional), Status switch.
    - Untuk akun dosen/mahasiswa: role group dan prodi disembunyikan.
    - Password opsional saat edit; kosong berarti tidak diubah.
    - Tombol simpan disabled jika processing, tidak ada perubahan, username mengandung spasi, atau password tidak valid/tidak cocok.
- Modal role: hanya badge read-only.
- Modal panduan: 3 blok (tambah, edit/role, hapus).

### 10.4 User bulk create

- `PageHeader` dengan back URL yang membawa state tabel (`tableStateQuery('identity-user-table-admin')` + `tab=admin`).
- `AppBulkRows`:
    - Card dengan judul "Daftar Pengguna (n/10)".
    - Tombol tambah disabled saat penuh.
    - Empty state dengan ikon dan ajakan menambah.
    - Tabel scroll horizontal, kolom nomor + kolom data + aksi sticky kanan.
    - Baris duplikat diberi penanda dan highlight sementara.
    - Error per sel di bawah nilai.
    - Footer: Batal/Kembali dan Simpan n Data.
- Modal add/edit memakai `UserFormModal` + `UserFormFields`.
- Validasi client:
    - Nama, username, password, konfirmasi wajib.
    - Username tanpa spasi.
    - Password minimal 8 karakter, huruf besar/kecil, angka, simbol.
    - Password = konfirmasi.
    - Minimal satu role.
    - Prodi wajib untuk role dosen/mahasiswa/admin prodi.
    - Username/email tidak duplikat di daftar.
- `useBulkRows` menangani modal state, edit, duplicate, remove, error lookup, scroll ke error pertama.

### 10.5 Role index

- `PageHeader` + Panduan.
- Tab Default/System/Custom dengan count.
- `RoleTable` server-side per tab, page size 10.
- Kolom:
    - Role: nama semibold + badge Default/System (+ badge Jabatan opsional).
    - Parent: daftar nama atau `-`.
    - Pengguna: jumlah user.
    - Permission: jumlah efektif = grant + inherited - deny.
    - Aksi: Edit (link) dan Hapus (hanya jika bukan default dan bukan role jabatan).
- Tambah Role hanya di tab Custom.
- Hapus memakai konfirmasi.

### 10.6 Role form

- Halaman Create/Edit memakai `PageHeader` dengan back URL yang membawa query saat ini.
- Section 1: kartu informasi role.
    - Input nama + preview slug.
    - Parent role checkbox group (disembunyikan untuk role default).
    - Role default: nama/parent tidak dapat diubah.
    - Role jabatan (opsional): nama disabled dan diarahkan ke menu Jabatan.
- Section 2: `AppTabs` assignment (`semua`, `sudah`, `sebagian`, `belum`, `warisan`) + count.
- Section 3: `AppDataTable` group permission (client-side, page size 10):
    - Filter module.
    - Kolom Group, Modul, Total, Terpilih, Status (Sudah/Sebagian/Belum), Warisan, Aksi.
    - Aksi: Pilih Semua kategori, Reset kategori, Kelola (modal).
    - Toolbar info inheritance.
    - Aksi global: Pilih Semua, Reset, Hapus Semua (dengan konfirmasi).
- Section 4: tombol Batal dan Simpan.
- Modal permission kategori:
    - Tabel Permission, Route, Status (Warisan/Direct), checkbox Pilih.
    - Pilih Semua / Reset per kategori.
- Aturan toggle:
    - Permission warisan tampil checked.
    - Uncheck permission warisan → masuk `deny_permission_ids`.
    - Check permission non-warisan → masuk `grant_permission_ids`.
    - Uncheck permission direct → hapus dari grant.
- `Hapus Semua` hanya menghapus grant langsung; permission warisan tetap.
- Simpan disabled jika processing atau tidak ada perubahan (edit).

### 10.7 Permission index

- `PageHeader` + Panduan.
- `PermissionModuleTable` client-side, page size 15:
    - Kolom Modul (semibold), Kategori, Permission, Status (System/Custom/Campuran), Aksi.
    - Aksi Detail (mata) dan Edit Modul (pensil), dengan tooltip.
    - Tombol Scan Permission di slot actions, loading state.
- Modal edit module:
    - Modul saat ini disabled.
    - Nama modul baru.
    - Info jumlah kategori yang terdampak.
    - Simpan disabled jika tidak ada perubahan/processing.

### 10.8 Permission module detail

- `PageHeader` dengan back ke index.
- Tiga `AppInfoField`: Modul (accent), Jumlah Kategori, Jumlah Permission.
- `PermissionCategoryTable` server-side page size 10:
    - Kolom Kategori (semibold), Kode (monospace), Permission (count), Status (System/Custom), Aksi (Detail, Edit).
    - `table-key` harus konsisten dengan flash highlight (lihat temuan §17).
- Modal detail kategori:
    - Tabel Action (badge), Route Permission (monospace), Sumber (Manual/Otomatis).
    - Empty state.
    - Tombol Edit jika punya izin.
- Modal edit kategori:
    - Nama kategori.
    - Kode kategori disabled.
    - Select permission dari `uncategorized` + tombol tambah.
    - Daftar permission dalam kategori + tombol lepas (kembali ke uncategorized).
    - Tombol reset perubahan.
    - Kategori `uncategorized`: tidak bisa menambah/melepas; hanya info.
    - Simpan disabled jika tidak ada perubahan/processing.

### 10.9 Kontrak komponen UI

| Komponen                           | Kontrak minimum                                                                                                                                                                            |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `PageHeader`                       | `title`, `subtitle`, `backUrl`, `backLabel`, slot default (aksi), slot `title`, slot `leading`                                                                                             |
| `AppTabs`                          | `tabs [{value,label,count?}]`, `modelValue`, `variant`, `attached`, emit `update:modelValue`                                                                                               |
| `AppDataTable`                     | `tableKey`, `columns`, `data`, `filters`, `serverSide`, `serverTotalRows`, `reloadOnly`, slot `actions`, slot `toolbar-start`                                                              |
| `AppButton`                        | `variant` (`primary`, `secondary`, `info`, `info-outline`, `danger`), `size`, `icon`, `loading`, `disabled`                                                                                |
| `AppModal`                         | `show`, `title`, `maxWidth`, emit `close`, slot default, slot `footer`                                                                                                                     |
| `AppInput`                         | `modelValue`, `label`, `icon`, `error`, `type`, `toggleable`, `disabled`, `required`                                                                                                       |
| `AppSelect`                        | `modelValue`, `label`, `placeholder`, `options`, `valueProp`, `optionLabel`, `trackBy`, `error`, `required`                                                                                |
| `AppCheckbox` / `AppCheckboxGroup` | `modelValue`, `label`, `name`, `error`, `required`, `inline`                                                                                                                               |
| `AppSwitch`                        | `modelValue`, `caption`                                                                                                                                                                    |
| `AppPasswordChecklist`             | `value`, `matchValue`                                                                                                                                                                      |
| `AppInfoModal`                     | `show`, `title`, slot default                                                                                                                                                              |
| `AppInfoField`                     | `label`, `value`, `accent`                                                                                                                                                                 |
| `AppBulkRows`                      | `rows`, `columns`, `formKey`, `max`, `title`, `addLabel`, `submitLabel`, `backUrl`, `processing`, `globalError`, `setRowRef`, `getError`, emit `add/edit/duplicate/remove/submit/back`     |
| `useBulkRows`                      | `formKey`, `emptyRow`, `errorFields`, `prepareDuplicate`, mengembalikan `modalForm`, `showModal`, `openCreate`, `openEdit`, `commitRow`, `duplicateRow`, `removeRow`, `getError`, `submit` |
| `usePermission`                    | `can(permission)`, `canAny(...permissions)`                                                                                                                                                |
| `useConfirm`                       | `confirm({ title, message, confirmText, variant })`                                                                                                                                        |
| `useNotification`                  | `success`, `error`, `info`, `warning`                                                                                                                                                      |

### 10.10 Color role mapping

Isi kolom token target. Jangan menyalin nilai warna project sumber.

| #   | Elemen                  | Peran visual                          | Token target |
| --- | ----------------------- | ------------------------------------- | ------------ |
| 1   | Latar halaman           | Background aplikasi                   |              |
| 2   | Card/PageHeader         | Permukaan card                        |              |
| 3   | Border card             | Garis netral                          |              |
| 4   | Shadow card             | Elevasi ringan                        |              |
| 5   | Judul halaman           | Teks utama                            |              |
| 6   | Subtitle/eyebrow        | Teks muted                            |              |
| 7   | Border pemisah          | Divider                               |              |
| 8   | Tab aktif               | Aksen + teks di atas aksen            |              |
| 9   | Tab tidak aktif         | Teks muted + hover surface            |              |
| 10  | Badge Default           | Tint aksen + teks aksen               |              |
| 11  | Badge System            | Tint container + teks on-container    |              |
| 12  | Badge Aktif/Sudah       | Tint success + teks success           |              |
| 13  | Badge Nonaktif/Belum    | Surface high + teks muted             |              |
| 14  | Badge Sebagian/Duplikat | Tint warning/aksen + teks             |              |
| 15  | Tombol primary          | Aksen + teks di atas aksen            |              |
| 16  | Tombol secondary        | Surface + border + teks utama         |              |
| 17  | Tombol danger           | Tint error + teks error               |              |
| 18  | Tombol info             | Tint info + teks info                 |              |
| 19  | Tombol disabled         | Opacity/neutral                       |              |
| 20  | Input surface/border    | Permukaan input + border              |              |
| 21  | Focus ring              | Aksen                                 |              |
| 22  | Header tabel/bulk       | Permukaan header subtle               |              |
| 23  | Baris hover             | Permukaan hover                       |              |
| 24  | Kolom aksi sticky       | Permukaan + border + shadow           |              |
| 25  | Permission warisan      | Tint container + teks on-container    |              |
| 26  | Callout/info box        | Tint container lembut                 |              |
| 27  | Modal overlay/surface   | Overlay + permukaan modal             |              |
| 28  | Tooltip                 | Permukaan + teks tooltip              |              |
| 29  | Dark mode               | Pasangan token dark untuk semua peran |              |

### 10.11 Ukuran dan tipografi

| Elemen                       | Nilai acuan                                  |
| ---------------------------- | -------------------------------------------- |
| Padding PageHeader/card      | 24px                                         |
| Radius card                  | 12px                                         |
| Jarak antar section          | 24px (`space-y-6`)                           |
| Tinggi tombol aksi baris     | 32px (bulat)                                 |
| Tinggi input/select          | 40px                                         |
| Padding sel tabel            | 12–16px                                      |
| Page size user/role/kategori | 10                                           |
| Page size modul permission   | 15                                           |
| Bulk rows maksimum           | 10                                           |
| Min-width tabel bulk         | 1100px                                       |
| Judul halaman                | headline large; mobile headline large mobile |
| Judul section                | headline small                               |
| Body                         | body medium                                  |
| Label/badge                  | label small/caps                             |
| Modal max width              | `md`, `lg`, `2xl`, `4xl` sesuai konten       |
| Durasi transisi              | 150ms                                        |
| Highlight baris              | 800ms                                        |
| Penanda duplikat             | 2500ms                                       |

## 11. Kontrak data Inertia

### 11.1 User Index

```php
[
    'users' => LengthAwarePaginator, // data user ter-map
    'userCounts' => ['admin' => 12, 'dosen' => 30, 'mahasiswa' => 400],
    'roles' => [['id' => 1, 'name' => 'admin', 'is_default' => true, 'parent_ids' => []], ...],
    'programStudis' => [['value' => 'uuid', 'label' => 'Teknik Informatika'], ...],
]
```

### 11.2 User Create

```php
[
    'roles' => [...],
    'programStudis' => [...],
]
```

### 11.3 Role Index / Form

```php
// Index
[
    'roles' => LengthAwarePaginator,
    'roleCounts' => ['default' => 3, 'system' => 5, 'custom' => 7],
]

// Create/Edit
[
    'roles' => [...],
    'role' => [...] | null,
    'permissionCategories' => [
        [
            'id' => 'uuid',
            'code' => 'identity.users',
            'module' => 'Identity',
            'name' => 'Users',
            'is_system' => true,
            'permissions_count' => 6,
            'permissions' => [
                ['id' => 1, 'name' => 'admin.identity.users.index', 'route_name' => '...', 'action' => 'index', 'category_assignment_source' => 'scanner'],
            ],
        ],
    ],
]
```

### 11.4 Permission Index

```php
[
    'permissionModules' => [
        ['module' => 'Identity', 'categories_count' => 3, 'permissions_count' => 24, 'status' => 'System'],
    ],
]
```

### 11.5 Permission Module Show

```php
[
    'module' => [
        'module' => 'Identity',
        'categories_count' => 3,
        'permissions_count' => 24,
        'categories' => [
            [
                'id' => 'uuid',
                'code' => 'identity.users',
                'module' => 'Identity',
                'name' => 'Users',
                'is_system' => true,
                'permissions_count' => 6,
                'permissions' => [
                    ['id' => 1, 'name' => 'admin.identity.users.index', 'route_name' => '...', 'action' => 'index', 'category_assignment_source' => 'scanner'],
                ],
            ],
        ],
    ],
]
```

### 11.6 Flash highlight

```json
{
    "highlight": {
        "tableKey": "identity-user-table-admin",
        "rowIds": ["uuid-atau-id"],
        "action": "created",
        "token": "uuid-unik",
        "page": 2
    }
}
```

Aturan:

- `tableKey` wajib sama dengan `table-key` komponen.
- `rowIds` array string.
- `action`: `created`, `updated`, `imported`.
- `token` unik per payload; mencegah highlight diputar dua kali.
- `page` opsional; hanya dikirim jika target berada di halaman lain.
- Batas N = 10 untuk highlight bulk.

## 12. Route yang termasuk

| Method | URI                                                                                   | Name                                                        | Controller                            |
| ------ | ------------------------------------------------------------------------------------- | ----------------------------------------------------------- | ------------------------------------- |
| GET    | `/admin/identity/users`                                                               | `admin.identity.users.index`                                | `UserRoleController@index`            |
| GET    | `/admin/identity/users/create`                                                        | `admin.identity.users.create`                               | `UserRoleController@create`           |
| POST   | `/admin/identity/users`                                                               | `admin.identity.users.store`                                | `UserRoleController@store`            |
| POST   | `/admin/identity/users/bulk`                                                          | `admin.identity.users.bulk.store`                           | `UserRoleController@storeBulk`        |
| PUT    | `/admin/identity/users/{user}`                                                        | `admin.identity.users.update`                               | `UserRoleController@update`           |
| DELETE | `/admin/identity/users/{user}`                                                        | `admin.identity.users.destroy`                              | `UserRoleController@destroy`          |
| GET    | `/admin/identity/roles`                                                               | `admin.identity.roles.index`                                | `RoleController@index`                |
| GET    | `/admin/identity/roles/create`                                                        | `admin.identity.roles.create`                               | `RoleController@create`               |
| GET    | `/admin/identity/roles/{role}/edit`                                                   | `admin.identity.roles.edit`                                 | `RoleController@edit`                 |
| POST   | `/admin/identity/roles`                                                               | `admin.identity.roles.store`                                | `RoleController@store`                |
| PUT    | `/admin/identity/roles/{role}`                                                        | `admin.identity.roles.update`                               | `RoleController@update`               |
| DELETE | `/admin/identity/roles/{role}`                                                        | `admin.identity.roles.destroy`                              | `RoleController@destroy`              |
| GET    | `/admin/identity/permissions`                                                         | `admin.identity.permissions.index`                          | `PermissionController@index`          |
| GET    | `/admin/identity/permissions/module`                                                  | `admin.identity.permissions.module.show`                    | `PermissionController@showModule`     |
| POST   | `/admin/identity/permissions/scan`                                                    | `admin.identity.permissions.scan`                           | `PermissionController@scan`           |
| PUT    | `/admin/identity/permission-modules`                                                  | `admin.identity.permission-modules.update`                  | `PermissionController@updateModule`   |
| PUT    | `/admin/identity/permission-categories/{permissionCategory}`                          | `admin.identity.permission-categories.update`               | `PermissionController@updateCategory` |
| POST   | `/admin/identity/permission-categories/{permissionCategory}/permissions/{permission}` | `admin.identity.permission-categories.permissions.assign`   | `PermissionController@assign`         |
| DELETE | `/admin/identity/permission-categories/permissions/{permission}`                      | `admin.identity.permission-categories.permissions.unassign` | `PermissionController@unassign`       |
| POST   | `/admin/identity/permissions/{permission}/reset-category`                             | `admin.identity.permissions.reset-category`                 | `PermissionController@reset`          |

Seluruh group memakai middleware `superadmin`. Route Jabatan tidak termasuk.

## 13. Adaptasi ke project target

1. **Tanpa modul Dosen/Mahasiswa/Program Studi**
    - Ganti guard prodi dengan aturan target, atau hapus `program_studi_id` dari form/validasi.
    - Hapus relasi `dosen_id`/`mahasiswa_id` dan guard "akun dosen immutable" jika tidak relevan.
2. **Tanpa Spatie Permission**
    - Pasang Spatie (dengan persetujuan) atau buat tabel/relasi ekuivalen.
    - Pertahankan kontrak `effectivePermissionNames`, `userHasPermission`, dan middleware route permission.
3. **ID bigint vs UUID**
    - Sesuaikan `role_parents`, `role_permission_denials`, dan foreign key mengikuti tipe ID target.
4. **Inertia v1**
    - Ganti `Inertia::flash` dengan session flash + share di middleware.
    - Ganti event flash highlight dengan prop flash biasa.
5. **Database selain PostgreSQL/MySQL 8+**
    - Ganti `ROW_NUMBER()` dengan strategi hitung halaman alternatif (mis. query count + filter sebelum ID), atau hitung di PHP dengan dataset terbatas.
6. **Tanpa AppDataTable server-side**
    - Implementasikan ekuivalen dengan query string `q/page/per_page/sort/dir/f_*`, partial reload, dan `serverTotalRows`.
7. **Tanpa modular monolith**
    - Pertahankan pemisahan Controller → Service/Contract; letakkan service di `app/Services` jika tidak ada folder Modules.
8. **Tanpa Jabatan**
    - Hapus `jabatan_id`, `role_source`, badge Jabatan, dan guard nama role jabatan.
9. **Superadmin awal**
    - Sediakan seeder untuk role default (`superadmin`, `admin`, `dosen`, `mahasiswa`) dan satu akun superadmin. Tanpa itu, tidak ada yang bisa membuka halaman Identity.

## 14. Urutan implementasi

1. Migrasi + model: users, user_program_studi, permission_categories, metadata permissions, role_parents, role_permission_denials, model kustom Role/Permission.
2. Seeder role default + akun superadmin.
3. Middleware `route.permission` + share permission di Inertia + `usePermission`.
4. Contract + service User; route User; halaman Index/Create + komponen.
5. Contract + service Role; route Role; halaman Index/Create/Edit + RoleForm.
6. Scanner + service Permission; route Permission; halaman Index/Module/Show + modal.
7. Flash highlight user/role/kategori + `find*Page()`.
8. Guard, validasi, dan error mapping bulk.
9. Uji manual + test fitur.
10. Baru migrasikan halaman existing (jika ada) setelah komponen stabil.

## 15. Verifikasi

### Backend

1. Hanya superadmin + permission yang sesuai dapat mengakses route Identity.
2. User index: tab, count, search, filter role/status/prodi, sort, pagination benar.
3. Bulk create maksimal 10; error per baris tampil di baris/field yang tepat.
4. Guard user: superadmin tidak bisa dibuat/dihapus; akun sendiri tidak bisa dihapus; admin general vs admin prodi; mahasiswa eksklusif; prodi wajib.
5. Role: create/update/delete; parent cycle ditolak; default role tidak bisa dihapus; role yang dipakai tidak bisa dihapus.
6. Permission efektif: grant + inheritance - deny; superadmin bypass.
7. Scanner idempoten; assignment manual tidak tertimpa; stale ditandai; kategori kosong dipangkas.
8. Kategori: hanya permission uncategorized yang bisa ditambahkan; unassign kembali ke uncategorized; reset mengembalikan kategori otomatis.
9. Flash highlight: token, tableKey, rowIds, page benar; highlight hanya untuk ≤ 10 record.
10. `php artisan test --filter=Identity` (atau modul terkait) lulus.

### Frontend

1. Tabs berganti dengan query `tab`; filter tab lama tidak bocor.
2. Server-side table memuat ulang hanya prop di `reloadOnly`.
3. Modal edit/role/confirm/info tampil dan menutup benar.
4. Bulk create: tambah/edit/duplikat/hapus, validasi client, scroll ke error, submit.
5. Role form: inheritance, deny, select all/reset/clear, filter module, modal permission.
6. Permission: scan, module detail, category detail/edit, assign/unassign/reset, rename module.
7. Layout responsif; tabel scroll horizontal; kolom aksi sticky.
8. `npm run lint` dan `npm run build` lulus.

## 16. Jebakan implementasi

- **Permission = nama route.** Jangan membuat sistem permission terpisah dari nama route kecuali seluruh middleware ikut diubah.
- **Deny menang atas grant.** Saat menghitung permission efektif, kurangi denial setelah union seluruh ancestor.
- **Superadmin bypass.** Middleware dan `can()` harus konsisten; jangan hanya mengandalkan frontend.
- **Jangan query permission langsung di frontend.** Gunakan `auth.permissions` dari share Inertia.
- **`ROW_NUMBER()` harus memakai query, sort, dan filter yang sama** dengan index; jika tidak, halaman highlight meleset.
- **`f_status` adalah string** `'true'`/`'false'` dari query string; jangan membandingkan langsung dengan boolean.
- **Ganti tab harus menghapus `page` dan `f_*`** agar filter tab sebelumnya tidak terbawa.
- **`_query` wajib dipertahankan** saat edit/delete agar kembali ke halaman dan filter yang sama.
- **`reloadOnly` harus cocok** dengan prop yang dikirim controller; jika kosong, `AppDataTable` tidak melakukan visit.
- **`tableKey` flash harus sama** dengan `table-key` komponen; mismatch membuat highlight tidak pernah tampil.
- **Bulk create maksimal 10**; validasi per baris harus memetakan error ke `users.N.field`.
- **Akun dosen**: role dan prodi tidak boleh diubah dari user management jika integrasi Dosen/Jabatan dipakai.
- **Default role**: nama dan parent tidak boleh berubah; role default tidak bisa dihapus.
- **Role yang dipakai atau punya child** tidak bisa dihapus.
- **Uncategorized** adalah kategori sistem; jangan izinkan edit permission_ids di dalamnya.
- **Scanner tidak menghapus permission**, hanya menandai stale; jangan mengubahnya menjadi hard delete tanpa persetujuan.
- **Assignment source**: `manual` harus dipertahankan scanner; `scanner` dipakai saat reset.
- **Role name dinormalisasi ke slug**; tampilkan preview agar user tidak kaget.
- **Jangan memakai `orderBy` dari input mentah**; selalu whitelist kolom sort.
- **Eager loading** wajib untuk roles, programStudis, permissions, parents, deniedPermissions.
- **Jangan menambahkan cache** untuk permission/role tanpa persetujuan developer; driver cache berbeda per server.
- **Model Role/Permission kustom** harus tetap kompatibel dengan Spatie (`HasRoles`, `syncPermissions`, `deniedPermissions`).
- **UUID vs bigint** pada pivot role/permission harus konsisten.
- **Frontend guard bukan enforcement**; backend tetap memvalidasi setiap request.

## 17. Temuan source yang perlu diperhatikan

1. **Mismatch `tableKey` highlight kategori**
    - `PermissionController::flashCategoryHighlight()` memakai `identity-permission-category-table`.
    - `PermissionCategoryTable.vue` memakai `identity-permission-category-detail-table`.
    - Akibatnya highlight kategori tidak pernah cocok. Di target, gunakan satu `tableKey` yang sama.
2. **Prop tidak terpakai di Permission Index**
    - Controller mengirim `permissionCategories`, `permissionCategoryModules`, `uncategorizedPermissions`, `allPermissions`, tetapi halaman index hanya memakai `permissionModules`.
3. **Tombol hapus user**
    - Tombol tetap dirender untuk user sendiri/superadmin, tetapi handler tidak melakukan apa pun. Untuk UX, pertimbangkan menyembunyikan atau menonaktifkan tombol.
4. **Parent role di UI**
    - Form role hanya menampilkan parent default `admin`/`dosen` dan parent yang sudah dimiliki role. Backend mengizinkan parent lain; jika target ingin bebas memilih semua role, sesuaikan UI.
5. **`clearAllGrants` pada role system**
    - Tombol Hapus Semua disabled jika role system masih punya perubahan permission. Pastikan perilaku ini disengaja.
6. **Nama module pada edit kategori**
    - Modal edit kategori mengirim `module` dari nilai awal, tetapi tidak menampilkan input module. Jika target perlu memindahkan kategori antar module, tambahkan field eksplisit.
7. **Back URL module detail**
    - Halaman module detail kembali ke index tanpa membawa query tabel. Jika state tabel penting, teruskan query.
8. **`users.type` legacy**
    - Kolom `type` masih ada di migrasi awal tetapi tidak dipakai fitur ini. Jangan jadikan sumber kebenaran role.

## 18. Definition of Done

- Migrasi dan model RBAC terpasang; seeder role default + superadmin tersedia.
- Middleware route permission + share permission berjalan; frontend `can()` terhubung.
- User management (index, tabs, filter, edit, roles modal, delete, bulk create) berfungsi.
- Role management (index, CRUD, inheritance, grant/deny, permission modal) berfungsi tanpa sync Jabatan.
- Permission management (scanner, module list/detail, kategori, assign/unassign/reset, rename module) berfungsi.
- Flash highlight user/role/kategori memakai `tableKey` yang konsisten.
- Guard backend dan validasi berjalan; frontend hanya lapisan UX.
- Checklist §15 lulus.
- Tidak ada halaman/tabel existing yang dimigrasikan tanpa persetujuan.
- Ringkas di akhir: file yang dibuat/diubah, dependency/token yang dipetakan, gap integrasi (Dosen/Mahasiswa/Program Studi/Jabatan), dan hasil verifikasi.
