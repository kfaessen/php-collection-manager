<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Display admin dashboard.
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
        ];

        $recent_users = User::latest()->take(5)->get();
        $roles = Role::withCount('users')->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'roles'));
    }

    /**
     * Display a listing of users.
     */
    public function users()
    {
        $users = User::with('roles')
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when(request('role'), function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when(request('status'), function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(15);

        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function createUser()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function storeUser(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        if ($request->has('roles')) {
            $user->assignRole($request->roles);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Gebruiker succesvol aangemaakt.');
    }

    /**
     * Show the form for editing a user.
     */
    public function editUser(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('id')->toArray();
        
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified user.
     */
    public function updateUser(UpdateUserRequest $request, User $user)
    {
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles($request->roles ?? []);

        return redirect()->route('admin.users.index')
            ->with('success', 'Gebruiker succesvol bijgewerkt.');
    }

    /**
     * Remove the specified user.
     */
    public function destroyUser(User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Administrators kunnen niet worden verwijderd.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Gebruiker succesvol verwijderd.');
    }

    /**
     * Display a listing of roles.
     */
    public function roles()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function createRole()
    {
        $permissions = Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function storeRole(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol aangemaakt.');
    }

    /**
     * Show the form for editing a role.
     */
    public function editRole(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function updateRole(UpdateRoleRequest $request, Role $role)
    {
        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol bijgewerkt.');
    }

    /**
     * Remove the specified role.
     */
    public function destroyRole(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'De admin rol kan niet worden verwijderd.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol verwijderd.');
    }

    /**
     * Display a listing of permissions.
     */
    public function permissions()
    {
        $permissions = Permission::withCount(['roles', 'users'])
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function createPermission()
    {
        return view('admin.permissions.create');
    }

    /**
     * Store a newly created permission.
     */
    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        Permission::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol aangemaakt.');
    }

    /**
     * Show the form for editing a permission.
     */
    public function editPermission(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified permission.
     */
    public function updatePermission(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol bijgewerkt.');
    }

    /**
     * Remove the specified permission.
     */
    public function destroyPermission(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol verwijderd.');
    }
}
