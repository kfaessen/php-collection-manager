<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\Permission;
use App\Models\CollectionItem;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:manage_users');
    }

    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_items' => CollectionItem::count(),
            'total_groups' => Group::count(),
            'total_permissions' => Permission::count(),
        ];

        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        $recentItems = CollectionItem::with('user')->orderBy('created_at', 'desc')->limit(5)->get();

        return view('admin.index', compact('stats', 'recentUsers', 'recentItems'));
    }

    /**
     * Show all users.
     */
    public function users()
    {
        $users = User::with('groups')->paginate(15);
        
        return view('admin.users', compact('users'));
    }

    /**
     * Show all groups.
     */
    public function groups()
    {
        $groups = Group::with('permissions')->paginate(15);
        
        return view('admin.groups', compact('groups'));
    }

    /**
     * Show user details.
     */
    public function showUser(User $user)
    {
        $user->load('groups', 'collectionItems');
        
        return view('admin.user-show', compact('user'));
    }

    /**
     * Show group details.
     */
    public function showGroup(Group $group)
    {
        $group->load('users', 'permissions');
        
        return view('admin.group-show', compact('group'));
    }
} 