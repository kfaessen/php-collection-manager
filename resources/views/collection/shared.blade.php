<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $item->title }} - Gedeelde Collectie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .item-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .item-card:hover {
            transform: translateY(-2px);
        }
        .cover-image {
            max-height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .type-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .condition-stars {
            color: #ffc107;
        }
        .shared-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Shared Link Info -->
                <div class="shared-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-share-alt me-3 fs-4"></i>
                        <div>
                            <h5 class="mb-1">Gedeeld Collectie Item</h5>
                            <p class="mb-0 small">
                                @if($sharedLink->expires_at)
                                    Verloopt op: {{ $sharedLink->expires_at->format('d-m-Y H:i') }}
                                @else
                                    Geen vervaldatum
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item Details -->
                <div class="card item-card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Cover Image -->
                            <div class="col-md-4 mb-3">
                                @if($item->cover_image)
                                    <img src="{{ $item->cover_image }}" alt="{{ $item->title }}" class="img-fluid cover-image w-100">
                                @else
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center cover-image w-100">
                                        <i class="fas fa-image fs-1"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Item Information -->
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h2 class="card-title mb-0">{{ $item->title }}</h2>
                                    <span class="badge bg-primary type-badge text-uppercase">{{ $item->type }}</span>
                                </div>

                                @if($item->description)
                                    <p class="text-muted mb-3">{{ $item->description }}</p>
                                @endif

                                <div class="row">
                                    @if($item->platform)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-gamepad me-2"></i>Platform:</strong>
                                            <span class="ms-2">{{ $item->platform }}</span>
                                        </div>
                                    @endif

                                    @if($item->category)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-tag me-2"></i>Categorie:</strong>
                                            <span class="ms-2">{{ $item->category }}</span>
                                        </div>
                                    @endif

                                    @if($item->condition_rating)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-star me-2"></i>Conditie:</strong>
                                            <span class="ms-2 condition-stars">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star{{ $i <= $item->condition_rating ? '' : '-o' }}"></i>
                                                @endfor
                                            </span>
                                        </div>
                                    @endif

                                    @if($item->purchase_date)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-calendar me-2"></i>Aankoopdatum:</strong>
                                            <span class="ms-2">{{ \Carbon\Carbon::parse($item->purchase_date)->format('d-m-Y') }}</span>
                                        </div>
                                    @endif

                                    @if($item->purchase_price)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-euro-sign me-2"></i>Aankoopprijs:</strong>
                                            <span class="ms-2">€{{ number_format($item->purchase_price, 2) }}</span>
                                        </div>
                                    @endif

                                    @if($item->current_value)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-chart-line me-2"></i>Huidige waarde:</strong>
                                            <span class="ms-2">€{{ number_format($item->current_value, 2) }}</span>
                                        </div>
                                    @endif

                                    @if($item->location)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-map-marker-alt me-2"></i>Locatie:</strong>
                                            <span class="ms-2">{{ $item->location }}</span>
                                        </div>
                                    @endif

                                    @if($item->barcode)
                                        <div class="col-sm-6 mb-2">
                                            <strong><i class="fas fa-barcode me-2"></i>Barcode:</strong>
                                            <span class="ms-2">{{ $item->barcode }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if($item->notes)
                                    <div class="mt-3">
                                        <strong><i class="fas fa-sticky-note me-2"></i>Notities:</strong>
                                        <p class="mt-2 mb-0">{{ $item->notes }}</p>
                                    </div>
                                @endif

                                <div class="mt-4 pt-3 border-top">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Toegevoegd op {{ $item->created_at->format('d-m-Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center mt-4">
                    <a href="{{ route('collection.index') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Terug naar Collectie
                    </a>
                    @auth
                        @if(auth()->id() === $item->user_id)
                            <a href="{{ route('collection.edit', $item) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Bewerken
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 