<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $item->title }} Bewerken - Collectie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .preview-image {
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>
                        <i class="fas fa-edit me-3"></i>{{ $item->title }} Bewerken
                    </h1>
                    <a href="{{ route('collection.show', $item) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Terug naar Item
                    </a>
                </div>

                <!-- Form -->
                <div class="card form-card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('collection.update', $item) }}">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-md-8">
                                    <h5 class="mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Basis Informatie
                                    </h5>
                                    
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
                                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
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
                                                       id="platform" name="platform" value="{{ old('platform', $item->platform) }}">
                                                @error('platform')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category" class="form-label">Categorie</label>
                                        <input type="text" class="form-control @error('category') is-invalid @enderror" 
                                               id="category" name="category" value="{{ old('category', $item->category) }}">
                                        @error('category')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Beschrijving</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" name="description" rows="3">{{ old('description', $item->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Additional Information -->
                                <div class="col-md-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-cog me-2"></i>Extra Informatie
                                    </h5>

                                    <div class="mb-3">
                                        <label for="condition_rating" class="form-label">Conditie</label>
                                        <select class="form-select @error('condition_rating') is-invalid @enderror" 
                                                id="condition_rating" name="condition_rating">
                                            <option value="">Selecteer conditie</option>
                                            <option value="5" {{ old('condition_rating', $item->condition_rating) == 5 ? 'selected' : '' }}>Uitstekend (5)</option>
                                            <option value="4" {{ old('condition_rating', $item->condition_rating) == 4 ? 'selected' : '' }}>Zeer goed (4)</option>
                                            <option value="3" {{ old('condition_rating', $item->condition_rating) == 3 ? 'selected' : '' }}>Goed (3)</option>
                                            <option value="2" {{ old('condition_rating', $item->condition_rating) == 2 ? 'selected' : '' }}>Redelijk (2)</option>
                                            <option value="1" {{ old('condition_rating', $item->condition_rating) == 1 ? 'selected' : '' }}>Slecht (1)</option>
                                        </select>
                                        @error('condition_rating')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

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
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Locatie</label>
                                        <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                               id="location" name="location" value="{{ old('location', $item->location) }}">
                                        @error('location')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="barcode" class="form-label">Barcode</label>
                                        <input type="text" class="form-control @error('barcode') is-invalid @enderror" 
                                               id="barcode" name="barcode" value="{{ old('barcode', $item->barcode) }}">
                                        @error('barcode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="cover_image" class="form-label">Cover Afbeelding URL</label>
                                <input type="url" class="form-control @error('cover_image') is-invalid @enderror" 
                                       id="cover_image" name="cover_image" value="{{ old('cover_image', $item->cover_image) }}">
                                @error('cover_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($item->cover_image)
                                    <div class="mt-2">
                                        <img src="{{ $item->cover_image }}" alt="Huidige cover" class="preview-image">
                                    </div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notities</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes', $item->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('collection.show', $item) }}" class="btn btn-outline-secondary me-2">
                                        <i class="fas fa-times me-2"></i>Annuleren
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                        <i class="fas fa-trash me-2"></i>Verwijderen
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Wijzigingen Opslaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Item Verwijderen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Weet je zeker dat je "{{ $item->title }}" wilt verwijderen?</p>
                    <p class="text-muted small">Deze actie kan niet ongedaan worden gemaakt.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                    <form method="POST" action="{{ route('collection.destroy', $item) }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Verwijderen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 