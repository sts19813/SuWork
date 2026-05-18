<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAccessController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAccess($request);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'role' => ['nullable', 'string', 'max:190'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $roleFilter = trim((string) ($filters['role'] ?? ''));

        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get();

        $users = User::query()
            ->with(['roles:name,id', 'permissions:name,id'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = "%{$search}%";
                $query->where(function ($inner) use ($like): void {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when($roleFilter !== '', fn($query) => $query->role($roleFilter))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('access.index', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'filters' => [
                'q' => $search,
                'role' => $roleFilter,
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'role_names' => ['nullable', 'array'],
            'role_names.*' => ['string', Rule::exists('roles', 'name')],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $user = User::query()->create([
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'password' => (string) $validated['password'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);
        $user->syncRoles($validated['role_names'] ?? []);
        $user->syncPermissions($validated['permission_names'] ?? []);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'role_names' => ['nullable', 'array'],
            'role_names.*' => ['string', Rule::exists('roles', 'name')],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $payload = [
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
        if (filled($validated['password'] ?? null)) {
            $payload['password'] = (string) $validated['password'];
        }

        $user->update($payload);
        $user->syncRoles($validated['role_names'] ?? []);
        $user->syncPermissions($validated['permission_names'] ?? []);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:roles,name'],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::query()->create([
            'name' => trim((string) $validated['name']),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($validated['permission_names'] ?? []);

        return back()->with('success', 'Rol creado correctamente.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('roles', 'name')->ignore($role->id)],
            'permission_names' => ['nullable', 'array'],
            'permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->update([
            'name' => trim((string) $validated['name']),
        ]);
        $role->syncPermissions($validated['permission_names'] ?? []);

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:permissions,name'],
        ]);

        Permission::query()->create([
            'name' => trim((string) $validated['name']),
            'guard_name' => 'web',
        ]);

        return back()->with('success', 'Permiso creado correctamente.');
    }

    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update([
            'name' => trim((string) $validated['name']),
        ]);

        return back()->with('success', 'Permiso actualizado correctamente.');
    }

    private function ensureAccess(Request $request): void
    {
        $user = $request->user();
        $isAdminRole = $user?->hasRole('administrador') || $user?->hasRole('admin');
        if (!$isAdminRole && !$user?->can('usuarios.gestionar')) {
            abort(403);
        }
    }
}

