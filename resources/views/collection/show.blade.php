<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $item->title }} - Collectie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .item-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .cover-image {
            max-height: 400px;
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
        .value-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
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
            </div>

            <div class="col-lg-4">
                <!-- Value Information -->
                @if($item->current_value || $item->purchase_price)
                    <div class="card value-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title text-white mb-3">
                                <i class="fas fa-chart-line me-2"></i>Waarde Informatie
                            </h5>
                            
                            @if($item->current_value)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Huidige waarde:</span>
                                        <strong>€{{ number_format($item->current_value, 2) }}</strong>
                                    </div>
                                </div>
                            @endif

                            @if($item->purchase_price)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Aankoopprijs:</span>
                                        <strong>€{{ number_format($item->purchase_price, 2) }}</strong>
                                    </div>
                                </div>
                            @endif

                            @if($item->current_value && $item->purchase_price)
                                @php
                                    $difference = $item->current_value - $item->purchase_price;
                                    $percentage = ($difference / $item->purchase_price) * 100;
                                @endphp
                                <div class="border-top border-white pt-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Verschil:</span>
                                        <strong class="{{ $difference >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $difference >= 0 ? '+' : '' }}€{{ number_format($difference, 2) }}
                                            ({{ $percentage >= 0 ? '+' : '' }}{{ number_format($percentage, 1) }}%)
                                        </strong>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-cogs me-2"></i>Acties
                        </h5>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('collection.edit', $item) }}" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Bewerken
                            </a>
                            
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#shareModal">
                                <i class="fas fa-share-alt me-2"></i>Delen
                            </button>
                            
                            <a href="{{ route('collection.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Terug naar Collectie
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-share-alt me-2"></i>Item Delen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="shareUrl" class="form-label">Deel link:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyShareUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="expiresAt" class="form-label">Verloopt op:</label>
                        <input type="datetime-local" class="form-control" id="expiresAt">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
                    <button type="button" class="btn btn-primary" onclick="createShareLink()">
                        <i class="fas fa-share me-2"></i>Deel Link Aanmaken
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createShareLink() {
            const expiresAt = document.getElementById('expiresAt').value;
            
            fetch('{{ route("collection.share") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    item_id: {{ $item->id }},
                    expires_at: expiresAt || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('shareUrl').value = data.data.share_url;
                    // Set default expiration if not set
                    if (!expiresAt) {
                        document.getElementById('expiresAt').value = data.data.expires_at.slice(0, 16);
                    }
                } else {
                    alert('Fout bij het aanmaken van de deel link: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het aanmaken van de deel link.');
            });
        }

        function copyShareUrl() {
            const shareUrl = document.getElementById('shareUrl');
            shareUrl.select();
            document.execCommand('copy');
            
            // Show feedback
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                button.innerHTML = originalText;
            }, 2000);
        }

        // Set default expiration date (7 days from now)
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            now.setDate(now.getDate() + 7);
            document.getElementById('expiresAt').value = now.toISOString().slice(0, 16);
        });
    </script>
</body>
</html> 