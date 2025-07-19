<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Collectie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .item-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            height: 100%;
        }
        .item-card:hover {
            transform: translateY(-2px);
        }
        .cover-image {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        .type-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
        .condition-stars {
            color: #ffc107;
        }
        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
        }
        .search-box:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-3">
                    <i class="fas fa-collection me-3"></i>Mijn Collectie
                </h1>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('collection.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nieuw Item
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('collection.index') }}" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control search-box border-start-0" 
                                   placeholder="Zoek in je collectie..." value="{{ $search }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">Alle types</option>
                            <option value="game" {{ $typeFilter === 'game' ? 'selected' : '' }}>Games</option>
                            <option value="film" {{ $typeFilter === 'film' ? 'selected' : '' }}>Films</option>
                            <option value="serie" {{ $typeFilter === 'serie' ? 'selected' : '' }}>Series</option>
                            <option value="book" {{ $typeFilter === 'book' ? 'selected' : '' }}>Boeken</option>
                            <option value="music" {{ $typeFilter === 'music' ? 'selected' : '' }}>Muziek</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        @if($items->count() > 0)
            <div class="row">
                @foreach($items as $item)
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card item-card">
                            <!-- Cover Image -->
                            @if($item->cover_image)
                                <img src="{{ $item->cover_image }}" alt="{{ $item->title }}" class="cover-image w-100">
                            @else
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center cover-image w-100">
                                    <i class="fas fa-image fs-1"></i>
                                </div>
                            @endif

                            <div class="card-body">
                                <!-- Title and Type -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0 text-truncate" title="{{ $item->title }}">
                                        {{ $item->title }}
                                    </h6>
                                    <span class="badge bg-primary type-badge text-uppercase">{{ $item->type }}</span>
                                </div>

                                <!-- Platform and Category -->
                                @if($item->platform || $item->category)
                                    <p class="text-muted small mb-2">
                                        @if($item->platform)
                                            <i class="fas fa-gamepad me-1"></i>{{ $item->platform }}
                                        @endif
                                        @if($item->platform && $item->category)
                                            <span class="mx-1">•</span>
                                        @endif
                                        @if($item->category)
                                            <i class="fas fa-tag me-1"></i>{{ $item->category }}
                                        @endif
                                    </p>
                                @endif

                                <!-- Condition Rating -->
                                @if($item->condition_rating)
                                    <div class="mb-2">
                                        <span class="condition-stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star{{ $i <= $item->condition_rating ? '' : '-o' }}"></i>
                                            @endfor
                                        </span>
                                        <small class="text-muted ms-1">({{ $item->condition_rating }}/5)</small>
                                    </div>
                                @endif

                                <!-- Value Information -->
                                @if($item->current_value || $item->purchase_price)
                                    <div class="mb-3">
                                        @if($item->current_value)
                                            <div class="text-success small">
                                                <i class="fas fa-chart-line me-1"></i>€{{ number_format($item->current_value, 2) }}
                                            </div>
                                        @endif
                                        @if($item->purchase_price)
                                            <div class="text-muted small">
                                                <i class="fas fa-euro-sign me-1"></i>Aankoop: €{{ number_format($item->purchase_price, 2) }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="d-flex gap-2">
                                    <a href="{{ route('collection.show', $item) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="fas fa-eye me-1"></i>Bekijk
                                    </a>
                                    <a href="{{ route('collection.edit', $item) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($items->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="text-center py-5">
                <i class="fas fa-collection fs-1 text-muted mb-3"></i>
                <h4 class="text-muted">Geen items gevonden</h4>
                <p class="text-muted">
                    @if($search || $typeFilter)
                        Probeer je zoekopdracht aan te passen of voeg je eerste item toe.
                    @else
                        Je hebt nog geen items in je collectie. Voeg je eerste item toe!
                    @endif
                </p>
                <a href="{{ route('collection.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Eerste Item Toevoegen
                </a>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 