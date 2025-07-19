<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>Dashboard - Collectiebeheer</title>
    
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
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
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
                        <a class="nav-link active" href="{{ route('dashboard') }}">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('collection.index') }}">
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
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="bi bi-plus-lg"></i> 
                        <span class="d-none d-sm-inline">Item Toevoegen</span>
                    </button>
                    
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
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="h3">Welkom terug, {{ auth()->user()->first_name }}!</h1>
                    <p class="text-muted">Beheer je collectie van games, films en series</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <div class="stats-card text-center">
                        <h3 class="h2 mb-0">{{ $stats['total_items'] }}</h3>
                        <small>Total Items</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <div class="stats-card text-center">
                        <h3 class="h2 mb-0">{{ $stats['games'] }}</h3>
                        <small>Games</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <div class="stats-card text-center">
                        <h3 class="h2 mb-0">{{ $stats['films'] }}</h3>
                        <small>Films</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <div class="stats-card text-center">
                        <h3 class="h2 mb-0">{{ $stats['series'] }}</h3>
                        <small>Series</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <div class="stats-card text-center">
                        <h3 class="h2 mb-0">{{ $stats['books'] }}</h3>
                        <small>Books</small>
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <div class="stats-card text-center">
                        <h3 class="h2 mb-0">{{ $stats['music'] }}</h3>
                        <small>Music</small>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-12">
                    <form method="GET" action="{{ route('dashboard') }}" class="search-form">
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
                                <small class="text-muted">
                                    <i class="bi bi-collection"></i> 
                                    {{ $items->total() }} items
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Items -->
            @if($recentItems->count() > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <h4>Recent Toegevoegd</h4>
                        <div class="row">
                            @foreach($recentItems as $item)
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- All Items -->
            <div class="row">
                <div class="col-12">
                    <h4>Alle Items</h4>
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
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                        <i class="bi bi-plus-lg"></i> Item Toevoegen
                                    </button>
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
                </div>
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
    </script>
</body>
</html> 