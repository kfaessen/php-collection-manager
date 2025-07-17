<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

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
            $user->roles()->attach($request->roles);
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
    public function updateUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

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

        $user->roles()->sync($request->roles ?? []);

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
        $permissions = Permission::orderBy('module')->orderBy('display_name')->get()
            ->groupBy('module');
        
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function storeRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->attach($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol aangemaakt.');
    }

    /**
     * Show the form for editing a role.
     */
    public function editRole(Role $role)
    {
        $permissions = Permission::orderBy('module')->orderBy('display_name')->get()
            ->groupBy('module');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function updateRole(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol succesvol bijgewerkt.');
    }

    /**
     * Remove the specified role.
     */
    public function destroyRole(Role $role)
    {
        if ($role->isSystem()) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Systeemrollen kunnen niet worden verwijderd.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Rol kan niet worden verwijderd omdat er nog gebruikers aan gekoppeld zijn.');
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
        $permissions = Permission::withCount('roles')
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%");
                });
            })
            ->when(request('module'), function ($query, $module) {
                $query->where('module', $module);
            })
            ->orderBy('module')
            ->orderBy('display_name')
            ->paginate(15);

        $modules = Permission::getModules();

        return view('admin.permissions.index', compact('permissions', 'modules'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function createPermission()
    {
        $modules = Permission::getModules();
        
        return view('admin.permissions.create', compact('modules'));
    }

    /**
     * Store a newly created permission.
     */
    public function storePermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Permission::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'module' => $request->module,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol aangemaakt.');
    }

    /**
     * Show the form for editing a permission.
     */
    public function editPermission(Permission $permission)
    {
        $modules = Permission::getModules();
        
        return view('admin.permissions.edit', compact('permission', 'modules'));
    }

    /**
     * Update the specified permission.
     */
    public function updatePermission(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $permission->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'module' => $request->module,
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol bijgewerkt.');
    }

    /**
     * Remove the specified permission.
     */
    public function destroyPermission(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Permissie kan niet worden verwijderd omdat er nog rollen aan gekoppeld zijn.');
        }

        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permissie succesvol verwijderd.');
    }
}
