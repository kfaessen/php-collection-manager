<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuw Item Toevoegen - Collectie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .barcode-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
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
                        <i class="fas fa-plus me-3"></i>Nieuw Item Toevoegen
                    </h1>
                    <a href="{{ route('collection.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Terug naar Collectie
                    </a>
                </div>

                <!-- Barcode Scanner Section -->
                <div class="barcode-section mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-barcode me-2"></i>Barcode Scanner
                    </h5>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" id="barcodeInput" class="form-control" placeholder="Scan barcode of voer handmatig in...">
                                <button class="btn btn-light" type="button" onclick="scanBarcode()">
                                    <i class="fas fa-search me-2"></i>Zoeken
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-light w-100" type="button" onclick="openCamera()">
                                <i class="fas fa-camera me-2"></i>Camera
                            </button>
                        </div>
                    </div>
                    <div id="barcodeResult" class="mt-3"></div>
                </div>

                <!-- Form -->
                <div class="card form-card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('collection.store') }}">
                            @csrf
                            
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-md-8">
                                    <h5 class="mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Basis Informatie
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Titel *</label>
                                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                               id="title" name="title" value="{{ old('title') }}" required>
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
                                                    <option value="game" {{ old('type') === 'game' ? 'selected' : '' }}>Game</option>
                                                    <option value="film" {{ old('type') === 'film' ? 'selected' : '' }}>Film</option>
                                                    <option value="serie" {{ old('type') === 'serie' ? 'selected' : '' }}>Serie</option>
                                                    <option value="book" {{ old('type') === 'book' ? 'selected' : '' }}>Boek</option>
                                                    <option value="music" {{ old('type') === 'music' ? 'selected' : '' }}>Muziek</option>
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
                                                       id="platform" name="platform" value="{{ old('platform') }}">
                                                @error('platform')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category" class="form-label">Categorie</label>
                                        <input type="text" class="form-control @error('category') is-invalid @enderror" 
                                               id="category" name="category" value="{{ old('category') }}">
                                        @error('category')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Beschrijving</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
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
                                            <option value="5" {{ old('condition_rating') == 5 ? 'selected' : '' }}>Uitstekend (5)</option>
                                            <option value="4" {{ old('condition_rating') == 4 ? 'selected' : '' }}>Zeer goed (4)</option>
                                            <option value="3" {{ old('condition_rating') == 3 ? 'selected' : '' }}>Goed (3)</option>
                                            <option value="2" {{ old('condition_rating') == 2 ? 'selected' : '' }}>Redelijk (2)</option>
                                            <option value="1" {{ old('condition_rating') == 1 ? 'selected' : '' }}>Slecht (1)</option>
                                        </select>
                                        @error('condition_rating')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="purchase_date" class="form-label">Aankoopdatum</label>
                                        <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" 
                                               id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}">
                                        @error('purchase_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="purchase_price" class="form-label">Aankoopprijs (€)</label>
                                        <input type="number" step="0.01" min="0" class="form-control @error('purchase_price') is-invalid @enderror" 
                                               id="purchase_price" name="purchase_price" value="{{ old('purchase_price') }}">
                                        @error('purchase_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="current_value" class="form-label">Huidige waarde (€)</label>
                                        <input type="number" step="0.01" min="0" class="form-control @error('current_value') is-invalid @enderror" 
                                               id="current_value" name="current_value" value="{{ old('current_value') }}">
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
                                               id="location" name="location" value="{{ old('location') }}">
                                        @error('location')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="barcode" class="form-label">Barcode</label>
                                        <input type="text" class="form-control @error('barcode') is-invalid @enderror" 
                                               id="barcode" name="barcode" value="{{ old('barcode') }}">
                                        @error('barcode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="cover_image" class="form-label">Cover Afbeelding URL</label>
                                <input type="url" class="form-control @error('cover_image') is-invalid @enderror" 
                                       id="cover_image" name="cover_image" value="{{ old('cover_image') }}">
                                @error('cover_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notities</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('collection.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Annuleren
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Item Opslaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function scanBarcode() {
            const barcode = document.getElementById('barcodeInput').value;
            if (!barcode) {
                alert('Voer een barcode in');
                return;
            }

            // Set barcode in form
            document.getElementById('barcode').value = barcode;

            // Search for metadata
            fetch('{{ route("collection.scan-barcode") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ barcode: barcode })
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('barcodeResult');
                if (data.success) {
                    // Fill form with metadata
                    if (data.data.title) document.getElementById('title').value = data.data.title;
                    if (data.data.type) document.getElementById('type').value = data.data.type;
                    if (data.data.description) document.getElementById('description').value = data.data.description;
                    if (data.data.platform) document.getElementById('platform').value = data.data.platform;
                    if (data.data.category) document.getElementById('category').value = data.data.category;
                    if (data.data.cover_image) document.getElementById('cover_image').value = data.data.cover_image;
                    if (data.data.purchase_price) document.getElementById('purchase_price').value = data.data.purchase_price;
                    if (data.data.current_value) document.getElementById('current_value').value = data.data.current_value;

                    resultDiv.innerHTML = '<div class="alert alert-success">Metadata succesvol opgehaald!</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-warning">' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('barcodeResult').innerHTML = '<div class="alert alert-danger">Fout bij het scannen van barcode</div>';
            });
        }

        function openCamera() {
            // This would integrate with a barcode scanner library
            alert('Camera functionaliteit wordt nog geïmplementeerd');
        }
    </script>
</body>
</html> 