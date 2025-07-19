<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>{{ $item->title }} - Collectiebeheer</title>
    
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
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('collection.index') }}">Collectie</a></li>
                            <li class="breadcrumb-item active">{{ $item->title }}</li>
                        </ol>
                    </nav>
                    <h1 class="h3">{{ $item->title }}</h1>
                </div>
                <div class="btn-group">
                    <a href="{{ route('collection.edit', $item) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Bewerken
                    </a>
                    <button class="btn btn-outline-success" onclick="shareItem()">
                        <i class="bi bi-share"></i> Delen
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteItem()">
                        <i class="bi bi-trash"></i> Verwijderen
                    </button>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <!-- Item Image -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        @if($item->hasCoverImage())
                            <img src="{{ $item->cover_image_url }}" 
                                 class="card-img-top" 
                                 alt="Cover van {{ $item->title }}"
                                 style="height: 400px; object-fit: cover;">
                        @else
                            <div class="card-img-top d-flex align-items-center justify-content-center" 
                                 style="height: 400px; background: #f8f9fa;">
                                <i class="bi bi-image fs-1 text-muted"></i>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Item Details -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Basis Informatie</h5>
                                    <dl class="row">
                                        <dt class="col-sm-4">Type:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bg-secondary">{{ ucfirst($item->type) }}</span>
                                        </dd>
                                        
                                        @if($item->platform)
                                            <dt class="col-sm-4">Platform:</dt>
                                            <dd class="col-sm-8">{{ $item->platform }}</dd>
                                        @endif
                                        
                                        @if($item->category)
                                            <dt class="col-sm-4">Categorie:</dt>
                                            <dd class="col-sm-8">{{ $item->category }}</dd>
                                        @endif
                                        
                                        <dt class="col-sm-4">Conditie:</dt>
                                        <dd class="col-sm-8">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star{{ $i <= $item->condition_rating ? '-fill text-warning' : '' }}"></i>
                                            @endfor
                                            ({{ $item->condition_rating }}/5)
                                        </dd>
                                        
                                        @if($item->location)
                                            <dt class="col-sm-4">Locatie:</dt>
                                            <dd class="col-sm-8">{{ $item->location }}</dd>
                                        @endif
                                        
                                        @if($item->barcode)
                                            <dt class="col-sm-4">Barcode:</dt>
                                            <dd class="col-sm-8">
                                                <code>{{ $item->barcode }}</code>
                                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyBarcode()">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </dd>
                                        @endif
                                    </dl>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Financiële Informatie</h5>
                                    <dl class="row">
                                        @if($item->purchase_date)
                                            <dt class="col-sm-4">Aankoopdatum:</dt>
                                            <dd class="col-sm-8">{{ $item->purchase_date->format('d-m-Y') }}</dd>
                                        @endif
                                        
                                        @if($item->purchase_price)
                                            <dt class="col-sm-4">Aankoopprijs:</dt>
                                            <dd class="col-sm-8">€{{ number_format($item->purchase_price, 2) }}</dd>
                                        @endif
                                        
                                        @if($item->current_value)
                                            <dt class="col-sm-4">Huidige waarde:</dt>
                                            <dd class="col-sm-8">€{{ number_format($item->current_value, 2) }}</dd>
                                        @endif
                                        
                                        @if($item->purchase_price && $item->current_value)
                                            <dt class="col-sm-4">Winst/Verlies:</dt>
                                            <dd class="col-sm-8">
                                                @php
                                                    $difference = $item->current_value - $item->purchase_price;
                                                    $percentage = ($difference / $item->purchase_price) * 100;
                                                @endphp
                                                <span class="badge bg-{{ $difference >= 0 ? 'success' : 'danger' }}">
                                                    {{ $difference >= 0 ? '+' : '' }}€{{ number_format($difference, 2) }}
                                                    ({{ $difference >= 0 ? '+' : '' }}{{ number_format($percentage, 1) }}%)
                                                </span>
                                            </dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                            
                            @if($item->description)
                                <hr>
                                <h5>Beschrijving</h5>
                                <p>{{ $item->description }}</p>
                            @endif
                            
                            @if($item->notes)
                                <hr>
                                <h5>Notities</h5>
                                <p>{{ $item->notes }}</p>
                            @endif
                            
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-plus"></i>
                                        Toegevoegd: {{ $item->created_at->format('d-m-Y H:i') }}
                                    </small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-check"></i>
                                        Laatst bijgewerkt: {{ $item->updated_at->format('d-m-Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deleteItem() {
            if (confirm('Weet je zeker dat je dit item wilt verwijderen?')) {
                fetch(`/collection/{{ $item->id }}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route("collection.index") }}';
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

        function shareItem() {
            fetch('/api/collection/share', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    item_id: {{ $item->id }},
                    expires_at: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString()
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Copy to clipboard
                    navigator.clipboard.writeText(data.data.share_url).then(() => {
                        alert('Deel link gekopieerd naar klembord!');
                    });
                } else {
                    alert('Fout bij maken deel link: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Er is een fout opgetreden');
            });
        }

        function copyBarcode() {
            navigator.clipboard.writeText('{{ $item->barcode }}').then(() => {
                alert('Barcode gekopieerd naar klembord!');
            });
        }
    </script>
</body>
</html> 