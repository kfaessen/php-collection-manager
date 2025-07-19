<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CollectionItem;
use Illuminate\Support\Facades\Validator;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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

        return view('collection.index', compact('items', 'search', 'typeFilter'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('collection.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:game,film,serie,book,music',
            'description' => 'nullable|string',
            'platform' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'condition_rating' => 'nullable|integer|min:1|max:5',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'cover_image' => 'nullable|url|max:255',
            'barcode' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $item = CollectionItem::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'platform' => $request->platform,
            'category' => $request->category,
            'condition_rating' => $request->condition_rating ?? 5,
            'purchase_date' => $request->purchase_date,
            'purchase_price' => $request->purchase_price,
            'current_value' => $request->current_value,
            'location' => $request->location,
            'notes' => $request->notes,
            'cover_image' => $request->cover_image,
            'barcode' => $request->barcode,
        ]);

        return redirect()->route('collection.show', $item)
                        ->with('success', 'Item succesvol toegevoegd aan je collectie!');
    }

    /**
     * Display the specified resource.
     */
    public function show(CollectionItem $item)
    {
        // Check if user can view this item
        if ($item->user_id !== auth()->id() && !auth()->user()->hasPermission('manage_all_collections')) {
            abort(403, 'Geen toegang tot dit item.');
        }

        return view('collection.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CollectionItem $item)
    {
        // Check if user can edit this item
        if ($item->user_id !== auth()->id() && !auth()->user()->hasPermission('manage_all_collections')) {
            abort(403, 'Geen toegang tot dit item.');
        }

        return view('collection.edit', compact('item'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CollectionItem $item)
    {
        // Check if user can edit this item
        if ($item->user_id !== auth()->id() && !auth()->user()->hasPermission('manage_all_collections')) {
            abort(403, 'Geen toegang tot dit item.');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:game,film,serie,book,music',
            'description' => 'nullable|string',
            'platform' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'condition_rating' => 'nullable|integer|min:1|max:5',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'cover_image' => 'nullable|url|max:255',
            'barcode' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $item->update($request->all());

        return redirect()->route('collection.show', $item)
                        ->with('success', 'Item succesvol bijgewerkt!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CollectionItem $item)
    {
        // Check if user can delete this item
        if ($item->user_id !== auth()->id() && !auth()->user()->hasPermission('manage_all_collections')) {
            abort(403, 'Geen toegang tot dit item.');
        }

        $item->delete();

        return redirect()->route('collection.index')
                        ->with('success', 'Item succesvol verwijderd!');
    }

    /**
     * Scan barcode and get metadata from external APIs.
     */
    public function scanBarcode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldige barcode',
            ]);
        }

        $barcode = $request->barcode;

        // Check if item already exists
        $existing = CollectionItem::where('barcode', $barcode)
                                ->where('user_id', auth()->id())
                                ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Item met deze barcode bestaat al in je collectie',
                'existing_item' => $existing,
            ]);
        }

        // Try to get metadata from external APIs
        $metadata = $this->getMetadataFromAPIs($barcode);

        return response()->json([
            'success' => true,
            'data' => $metadata,
            'message' => 'Barcode gescand en metadata opgehaald',
        ]);
    }

    /**
     * Get metadata from external APIs based on barcode.
     */
    private function getMetadataFromAPIs($barcode)
    {
        $metadata = [
            'title' => '',
            'type' => '',
            'description' => '',
            'platform' => '',
            'category' => '',
            'cover_image' => '',
            'purchase_price' => null,
            'current_value' => null,
        ];

        // Try different APIs based on barcode length and format
        if (strlen($barcode) == 13) {
            // ISBN-13 for books
            $bookData = $this->getBookMetadata($barcode);
            if ($bookData) {
                $metadata = array_merge($metadata, $bookData);
                $metadata['type'] = 'book';
            }
        } elseif (strlen($barcode) == 10) {
            // ISBN-10 for books
            $bookData = $this->getBookMetadata($barcode);
            if ($bookData) {
                $metadata = array_merge($metadata, $bookData);
                $metadata['type'] = 'book';
            }
        } elseif (strlen($barcode) >= 12) {
            // EAN/UPC for games, movies, music
            $mediaData = $this->getMediaMetadata($barcode);
            if ($mediaData) {
                $metadata = array_merge($metadata, $mediaData);
            }
        }

        return $metadata;
    }

    /**
     * Get book metadata from Google Books API.
     */
    private function getBookMetadata($isbn)
    {
        try {
            $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if (isset($data['items'][0]['volumeInfo'])) {
                $book = $data['items'][0]['volumeInfo'];
                return [
                    'title' => $book['title'] ?? '',
                    'description' => $book['description'] ?? '',
                    'category' => $book['categories'][0] ?? '',
                    'cover_image' => $book['imageLinks']['thumbnail'] ?? '',
                    'platform' => 'Paperback', // Default, could be enhanced
                ];
            }
        } catch (\Exception $e) {
            error_log("Google Books API error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get media metadata from IGDB API (games) or OMDB API (movies).
     */
    private function getMediaMetadata($barcode)
    {
        // Try IGDB for games first
        $gameData = $this->getGameMetadata($barcode);
        if ($gameData) {
            return array_merge($gameData, ['type' => 'game']);
        }

        // Try OMDB for movies
        $movieData = $this->getMovieMetadata($barcode);
        if ($movieData) {
            return array_merge($movieData, ['type' => 'film']);
        }

        return null;
    }

    /**
     * Get game metadata from IGDB API.
     */
    private function getGameMetadata($barcode)
    {
        try {
            // Note: IGDB requires authentication, this is a simplified version
            // In production, you'd need to implement proper OAuth flow
            $clientId = env('IGDB_CLIENT_ID');
            $clientSecret = env('IGDB_SECRET');

            if (!$clientId || !$clientSecret) {
                return null;
            }

            // This would require proper IGDB API implementation
            // For now, return basic structure
            return [
                'title' => 'Game Title (from IGDB)',
                'description' => 'Game description from IGDB',
                'platform' => 'Multi-platform',
                'category' => 'Action',
                'cover_image' => '',
            ];
        } catch (\Exception $e) {
            error_log("IGDB API error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get movie metadata from OMDB API.
     */
    private function getMovieMetadata($barcode)
    {
        try {
            $apiKey = env('OMDB_API_KEY');
            if (!$apiKey) {
                return null;
            }

            // Note: OMDB doesn't directly support barcode lookup
            // This would need to be enhanced with a barcode-to-title mapping service
            return [
                'title' => 'Movie Title (from OMDB)',
                'description' => 'Movie description from OMDB',
                'platform' => 'Blu-ray',
                'category' => 'Action',
                'cover_image' => '',
            ];
        } catch (\Exception $e) {
            error_log("OMDB API error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Search collection items.
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Zoekopdracht moet minimaal 2 karakters bevatten',
            ]);
        }

        $query = $request->query;
        $user = auth()->user();

        $items = CollectionItem::forUser($user->id)
                              ->search($query)
                              ->limit(10)
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Create share link for collection.
     */
    public function createShareLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:collection_items,id',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldige parameters',
            ]);
        }

        $item = CollectionItem::findOrFail($request->item_id);
        
        // Check if user can share this item
        if ($item->user_id !== auth()->id() && !auth()->user()->hasPermission('manage_all_collections')) {
            return response()->json([
                'success' => false,
                'message' => 'Geen toegang tot dit item',
            ]);
        }

        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expiresAt = $request->expires_at ? now()->parse($request->expires_at) : now()->addDays(7);

        // Create share link
        $shareLink = \App\Models\SharedLink::create([
            'user_id' => auth()->id(),
            'item_id' => $item->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        $shareUrl = route('collection.shared', $token);

        return response()->json([
            'success' => true,
            'data' => [
                'share_url' => $shareUrl,
                'token' => $token,
                'expires_at' => $expiresAt->toISOString(),
            ],
            'message' => 'Deel link aangemaakt',
        ]);
    }

    /**
     * Export collection to CSV.
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        $items = CollectionItem::forUser($user->id)->get();

        $filename = 'collectie_' . $user->username . '_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($items) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Titel', 'Type', 'Platform', 'Categorie', 'Beschrijving', 
                'Conditie', 'Aankoopdatum', 'Aankoopprijs', 'Huidige waarde',
                'Locatie', 'Barcode', 'Notities', 'Toegevoegd op'
            ]);

            // Data rows
            foreach ($items as $item) {
                fputcsv($file, [
                    $item->title,
                    $item->type,
                    $item->platform,
                    $item->category,
                    $item->description,
                    $item->condition_rating,
                    $item->purchase_date,
                    $item->purchase_price,
                    $item->current_value,
                    $item->location,
                    $item->barcode,
                    $item->notes,
                    $item->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import collection from CSV.
     */
    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('csv_file');
        $imported = 0;
        $errors = [];

        if (($handle = fopen($file->getPathname(), "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    $item = CollectionItem::create([
                        'user_id' => auth()->id(),
                        'title' => $data[0] ?? '',
                        'type' => $data[1] ?? 'unknown',
                        'platform' => $data[2] ?? '',
                        'category' => $data[3] ?? '',
                        'description' => $data[4] ?? '',
                        'condition_rating' => intval($data[5]) ?: 5,
                        'purchase_date' => $data[6] ?: null,
                        'purchase_price' => floatval($data[7]) ?: null,
                        'current_value' => floatval($data[8]) ?: null,
                        'location' => $data[9] ?? '',
                        'barcode' => $data[10] ?? '',
                        'notes' => $data[11] ?? '',
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Rij " . ($imported + 2) . ": " . $e->getMessage();
                }
            }
            fclose($handle);
        }

        $message = "{$imported} items geïmporteerd";
        if (!empty($errors)) {
            $message .= ". Fouten: " . implode(', ', $errors);
        }

        return redirect()->route('collection.index')
                        ->with('success', $message);
    }

    /**
     * Get collection statistics.
     */
    public function statistics()
    {
        $user = auth()->user();
        
        $stats = [
            'total_items' => CollectionItem::forUser($user->id)->count(),
            'by_type' => CollectionItem::forUser($user->id)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_platform' => CollectionItem::forUser($user->id)
                ->selectRaw('platform, COUNT(*) as count')
                ->groupBy('platform')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'platform'),
            'total_value' => CollectionItem::forUser($user->id)
                ->whereNotNull('current_value')
                ->sum('current_value'),
            'total_purchase_value' => CollectionItem::forUser($user->id)
                ->whereNotNull('purchase_price')
                ->sum('purchase_price'),
            'recent_additions' => CollectionItem::forUser($user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Bulk operations (delete, move, etc.).
     */
    public function bulkOperation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:collection_items,id',
            'operation' => 'required|in:delete,move,export',
            'location' => 'required_if:operation,move|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldige parameters',
            ]);
        }

        $items = CollectionItem::whereIn('id', $request->item_ids)
                              ->where('user_id', auth()->id())
                              ->get();

        switch ($request->operation) {
            case 'delete':
                $items->each(function($item) {
                    $item->delete();
                });
                $message = count($items) . ' items verwijderd';
                break;

            case 'move':
                $items->each(function($item) use ($request) {
                    $item->update(['location' => $request->location]);
                });
                $message = count($items) . ' items verplaatst naar ' . $request->location;
                break;

            case 'export':
                // Return items for export
                return response()->json([
                    'success' => true,
                    'data' => $items,
                    'message' => count($items) . ' items geselecteerd voor export',
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Show shared collection item.
     */
    public function showShared($token)
    {
        $sharedLink = \App\Models\SharedLink::where('token', $token)
                                            ->active()
                                            ->with('item')
                                            ->first();

        if (!$sharedLink) {
            abort(404, 'Deel link is niet geldig of verlopen.');
        }

        $item = $sharedLink->item;
        
        return view('collection.shared', compact('item', 'sharedLink'));
    }
} 