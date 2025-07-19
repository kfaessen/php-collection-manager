<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>{{ $item->title }} - Gedeelde Collectie</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="bi bi-collection"></i> Collectiebeheer
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class="bi bi-share"></i> Gedeelde Collectie
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container mt-4">
            <!-- Header -->
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="text-center mb-4">
                        <h1 class="h3">{{ $item->title }}</h1>
                        <p class="text-muted">Gedeeld door {{ $sharedLink->user->first_name }} {{ $sharedLink->user->last_name }}</p>
                        @if($sharedLink->expires_at)
                            <small class="text-muted">
                                <i class="bi bi-clock"></i>
                                Deze link verloopt op {{ $sharedLink->expires_at->format('d-m-Y H:i') }}
                            </small>
                        @endif
                    </div>

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

                    <!-- Call to Action -->
                    <div class="text-center mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h5>Vind je dit interessant?</h5>
                                <p class="text-muted">Maak je eigen collectie aan en begin met het bijhouden van je games, films, boeken en meer!</p>
                                <a href="{{ route('login') }}" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Maak Account Aan
                                </a>
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-house"></i> Meer Informatie
                                </a>
                            </div>
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