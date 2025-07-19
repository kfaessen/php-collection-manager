<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>Item Bewerken - Collectiebeheer</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('collection.index') }}">Collectie</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('collection.show', $item) }}">{{ $item->title }}</a></li>
                                    <li class="breadcrumb-item active">Bewerken</li>
                                </ol>
                            </nav>
                            <h1 class="h3">Item Bewerken</h1>
                            <p class="text-muted">Bewerk de details van "{{ $item->title }}"</p>
                        </div>
                        <a href="{{ route('collection.show', $item) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Terug naar Item
                        </a>
                    </div>

                    <!-- Form -->
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('collection.update', $item) }}">
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <!-- Basic Information -->
                                    <div class="col-12 col-md-8">
                                        <h5 class="mb-3">Basis Informatie</h5>
                                        
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Titel *</label>
                                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                                   id="title" name="title" value="{{ old('title', $item->title) }}" required>
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="type" class="form-label">Type *</label>
                                                    <select class="form-select @error('type') is-invalid @enderror" 
                                                            id="type" name="type" required>
                                                        <option value="">Selecteer type</option>
                                                        <option value="game" {{ old('type', $item->type) === 'game' ? 'selected' : '' }}>Game</option>
                                                        <option value="film" {{ old('type', $item->type) === 'film' ? 'selected' : '' }}>Film</option>
                                                        <option value="serie" {{ old('type', $item->type) === 'serie' ? 'selected' : '' }}>Serie</option>
                                                        <option value="book" {{ old('type', $item->type) === 'book' ? 'selected' : '' }}>Boek</option>
                                                        <option value="music" {{ old('type', $item->type) === 'music' ? 'selected' : '' }}>Muziek</option>
                                                    </select>
                                                    @error('type')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="platform" class="form-label">Platform</label>
                                                    <input type="text" class="form-control @error('platform') is-invalid @enderror" 
                                                           id="platform" name="platform" value="{{ old('platform', $item->platform) }}"
                                                           placeholder="bijv. PlayStation 4, Blu-ray, Hardcover">
                                                    @error('platform')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Beschrijving</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                                      id="description" name="description" rows="3" 
                                                      placeholder="Korte beschrijving van het item">{{ old('description', $item->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="category" class="form-label">Categorie</label>
                                                    <input type="text" class="form-control @error('category') is-invalid @enderror" 
                                                           id="category" name="category" value="{{ old('category', $item->category) }}"
                                                           placeholder="bijv. Action, Fantasy, Rock">
                                                    @error('category')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="condition_rating" class="form-label">Conditie</label>
                                                    <select class="form-select @error('condition_rating') is-invalid @enderror" 
                                                            id="condition_rating" name="condition_rating">
                                                        <option value="5" {{ old('condition_rating', $item->condition_rating) == '5' ? 'selected' : '' }}>Uitstekend (5)</option>
                                                        <option value="4" {{ old('condition_rating', $item->condition_rating) == '4' ? 'selected' : '' }}>Zeer goed (4)</option>
                                                        <option value="3" {{ old('condition_rating', $item->condition_rating) == '3' ? 'selected' : '' }}>Goed (3)</option>
                                                        <option value="2" {{ old('condition_rating', $item->condition_rating) == '2' ? 'selected' : '' }}>Redelijk (2)</option>
                                                        <option value="1" {{ old('condition_rating', $item->condition_rating) == '1' ? 'selected' : '' }}>Slecht (1)</option>
                                                    </select>
                                                    @error('condition_rating')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Information -->
                                    <div class="col-12 col-md-4">
                                        <h5 class="mb-3">Extra Informatie</h5>
                                        
                                        <div class="mb-3">
                                            <label for="purchase_date" class="form-label">Aankoopdatum</label>
                                            <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" 
                                                   id="purchase_date" name="purchase_date" 
                                                   value="{{ old('purchase_date', $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '') }}">
                                            @error('purchase_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="purchase_price" class="form-label">Aankoopprijs (€)</label>
                                            <input type="number" step="0.01" min="0" class="form-control @error('purchase_price') is-invalid @enderror" 
                                                   id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price) }}">
                                            @error('purchase_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="current_value" class="form-label">Huidige waarde (€)</label>
                                            <input type="number" step="0.01" min="0" class="form-control @error('current_value') is-invalid @enderror" 
                                                   id="current_value" name="current_value" value="{{ old('current_value', $item->current_value) }}">
                                            @error('current_value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="location" class="form-label">Locatie</label>
                                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                                   id="location" name="location" value="{{ old('location', $item->location) }}"
                                                   placeholder="bijv. Gaming Shelf, Bookshelf">
                                            @error('location')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="barcode" class="form-label">Barcode</label>
                                            <input type="text" class="form-control @error('barcode') is-invalid @enderror" 
                                                   id="barcode" name="barcode" value="{{ old('barcode', $item->barcode) }}"
                                                   placeholder="ISBN, EAN, etc.">
                                            @error('barcode')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="cover_image" class="form-label">Cover Afbeelding URL</label>
                                            <input type="url" class="form-control @error('cover_image') is-invalid @enderror" 
                                                   id="cover_image" name="cover_image" value="{{ old('cover_image', $item->cover_image) }}"
                                                   placeholder="https://example.com/image.jpg">
                                            @error('cover_image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notities</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3" 
                                              placeholder="Extra notities over het item">{{ old('notes', $item->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('collection.show', $item) }}" class="btn btn-secondary">
                                        <i class="bi bi-x-lg"></i> Annuleren
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Wijzigingen Opslaan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 