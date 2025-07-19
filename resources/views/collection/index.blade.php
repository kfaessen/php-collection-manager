<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>Collectie - Collectiebeheer</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .item-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .item-cover {
            height: 200px;
            object-fit: cover;
        }
        
        .placeholder-cover {
            height: 200px;
            background: #f8f9fa;
        }
        
        .text-truncate-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-collection"></i> Collectiebeheer
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('collection.index') }}">
                            <i class="bi bi-collection"></i> Collectie
                        </a>
                    </li>
                    @can('manage_users')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.index') }}">
                                <i class="bi bi-gear"></i> Beheer
                            </a>
                        </li>
                    @endcan
                </ul>
                
                <div class="d-flex align-items-center">
                    <a href="{{ route('collection.create') }}" class="btn btn-success me-2">
                        <i class="bi bi-plus-lg"></i> 
                        <span class="d-none d-sm-inline">Item Toevoegen</span>
                    </a>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                            <i class="bi bi-person-circle"></i> 
                            <span class="d-none d-sm-inline">{{ auth()->user()->first_name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <i class="bi bi-person"></i> Mijn Profiel
                                </a>
                            </li>
                            @can('manage_users')
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.index') }}">
                                        <i class="bi bi-gear"></i> Beheer
                                    </a>
                                </li>
                            @endcan
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}">
                                    <i class="bi bi-box-arrow-right"></i> Uitloggen
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container mt-4">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="h3">Mijn Collectie</h1>
                    <p class="text-muted">Beheer je games, films, series, boeken en muziek</p>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-12">
                    <form method="GET" action="{{ route('collection.index') }}" class="search-form">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-6 col-lg-5">
                                <div class="position-relative">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Zoeken in collectie..." 
                                           value="{{ $search }}"
                                           aria-label="Zoeken in collectie">
                                </div>
                            </div>
                            <div class="col-12 col-md-4 col-lg-3">
                                <select name="type" class="form-select" aria-label="Filter by type">
                                    <option value="">Alle types</option>
                                    <option value="game" {{ $typeFilter === 'game' ? 'selected' : '' }}>Games</option>
                                    <option value="film" {{ $typeFilter === 'film' ? 'selected' : '' }}>Films</option>
                                    <option value="serie" {{ $typeFilter === 'serie' ? 'selected' : '' }}>Series</option>
                                    <option value="book" {{ $typeFilter === 'book' ? 'selected' : '' }}>Boeken</option>
                                    <option value="music" {{ $typeFilter === 'music' ? 'selected' : '' }}>Muziek</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 col-lg-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-search"></i>
                                    <span class="d-none d-lg-inline">Zoeken</span>
                                </button>
                            </div>
                            <div class="col-12 col-lg-2 text-end">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="openBarcodeScanner()">
                                        <i class="bi bi-upc-scan"></i>
                                        <span class="d-none d-lg-inline">Barcode</span>
                                    </button>
                                    <small class="text-muted">
                                        <i class="bi bi-collection"></i> 
                                        {{ $items->total() }} items
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Items Grid -->
            <div class="row" id="items-grid">
                @if($items->count() === 0)
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <div class="mb-3">
                                <i class="bi bi-collection fs-1"></i>
                            </div>
                            <h4>Geen items gevonden</h4>
                            <p class="mb-3">
                                @if($search || $typeFilter)
                                    Geen items gevonden met de huidige filters. Probeer andere zoektermen.
                                @else
                                    Voeg je eerste item toe aan de collectie!
                                @endif
                            </p>
                            <a href="{{ route('collection.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Item Toevoegen
                            </a>
                        </div>
                    </div>
                @else
                    @foreach($items as $item)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card h-100 item-card">
                                @if($item->hasCoverImage())
                                    <img src="{{ $item->cover_image_url }}" 
                                         class="card-img-top item-cover" 
                                         alt="Cover van {{ $item->title }}"
                                         loading="lazy">
                                @else
                                    <div class="card-img-top placeholder-cover d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                    </div>
                                @endif
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title h6">{{ $item->title }}</h5>
                                    
                                    <div class="mb-2">
                                        <span class="badge bg-secondary">{{ ucfirst($item->type) }}</span>
                                        @if($item->platform)
                                            <span class="badge bg-info">{{ $item->platform }}</span>
                                        @endif
                                    </div>
                                    
                                    @if($item->description)
                                        <p class="card-text text-muted small flex-grow-1 text-truncate-3">
                                            {{ $item->description }}
                                        </p>
                                    @endif
                                    
                                    <div class="mt-auto">
                                        <small class="text-muted d-block mb-2">
                                            <i class="bi bi-calendar-plus"></i>
                                            Toegevoegd: {{ $item->created_at->format('d-m-Y') }}
                                        </small>
                                        <div class="btn-group w-100" role="group">
                                            <a href="{{ route('collection.show', $item) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                                <span class="d-none d-lg-inline">Bekijk</span>
                                            </a>
                                            <a href="{{ route('collection.edit', $item) }}" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                                <span class="d-none d-lg-inline">Bewerk</span>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteItem({{ $item->id }})">
                                                <i class="bi bi-trash"></i>
                                                <span class="d-none d-lg-inline">Verwijder</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Pagination -->
            @if($items->hasPages())
                <div class="row">
                    <div class="col-12">
                        <nav aria-label="Pagination Navigation">
                            {{ $items->appends(request()->query())->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deleteItem(itemId) {
            if (confirm('Weet je zeker dat je dit item wilt verwijderen?')) {
                fetch(`/collection/${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Fout bij verwijderen: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden');
                });
            }
        }

        function openBarcodeScanner() {
            // Simple barcode input for now - could be enhanced with camera scanning
            const barcode = prompt('Voer de barcode in:');
            if (barcode) {
                scanBarcode(barcode);
            }
        }

        function scanBarcode(barcode) {
            fetch('/api/collection/scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ barcode: barcode }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.title) {
                        // Pre-fill the create form with scanned data
                        const url = new URL('{{ route("collection.create") }}');
                        url.searchParams.set('scanned', 'true');
                        url.searchParams.set('title', data.data.title);
                        url.searchParams.set('type', data.data.type);
                        url.searchParams.set('description', data.data.description);
                        url.searchParams.set('platform', data.data.platform);
                        url.searchParams.set('category', data.data.category);
                        url.searchParams.set('cover_image', data.data.cover_image);
                        url.searchParams.set('barcode', barcode);
                        window.location.href = url.toString();
                    } else {
                        // No metadata found, go to create form with just barcode
                        const url = new URL('{{ route("collection.create") }}');
                        url.searchParams.set('barcode', barcode);
                        window.location.href = url.toString();
                    }
                } else {
                    if (data.existing_item) {
                        alert('Dit item bestaat al in je collectie: ' + data.existing_item.title);
                        window.location.href = `/collection/${data.existing_item.id}`;
                    } else {
                        alert('Fout bij scannen: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het scannen');
            });
        }
    </script>
</body>
</html> 