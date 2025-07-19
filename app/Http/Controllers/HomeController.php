<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CollectionItem;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * Show the application homepage.
     */
    public function index()
    {
        // If user is not authenticated, show login page
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Redirect to dashboard
        return redirect()->route('dashboard');
    }

    /**
     * Show the user dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Get search and filter parameters
        $search = $request->get('search', '');
        $typeFilter = $request->get('type', '');
        $page = max(1, intval($request->get('page', 1)));
        $itemsPerPage = 12;

        // Build query
        $query = CollectionItem::forUser($user->id);

        // Apply search filter
        if ($search) {
            $query->search($search);
        }

        // Apply type filter
        if ($typeFilter) {
            $query->ofType($typeFilter);
        }

        // Get paginated results
        $items = $query->orderBy('created_at', 'desc')
                      ->paginate($itemsPerPage, ['*'], 'page', $page);

        // Get statistics
        $stats = [
            'total_items' => CollectionItem::forUser($user->id)->count(),
            'games' => CollectionItem::forUser($user->id)->ofType('game')->count(),
            'films' => CollectionItem::forUser($user->id)->ofType('film')->count(),
            'series' => CollectionItem::forUser($user->id)->ofType('serie')->count(),
            'books' => CollectionItem::forUser($user->id)->ofType('book')->count(),
            'music' => CollectionItem::forUser($user->id)->ofType('music')->count(),
        ];

        // Get recent items
        $recentItems = CollectionItem::forUser($user->id)
                                   ->orderBy('created_at', 'desc')
                                   ->limit(5)
                                   ->get();

        return view('dashboard', compact('items', 'stats', 'recentItems', 'search', 'typeFilter'));
    }
} 