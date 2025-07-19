<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>Beheer - Collectiebeheer</title>
    
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
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('admin.index') }}">
                            <i class="bi bi-gear"></i> Beheer
                        </a>
                    </li>
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
                    <h1 class="h3">Beheer Dashboard</h1>
                    <p class="text-muted">Beheer gebruikers, groepen en systeeminstellingen</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['total_users'] }}</h4>
                                    <p class="card-text">Totaal Gebruikers</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['active_users'] }}</h4>
                                    <p class="card-text">Actieve Gebruikers</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-person-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['total_items'] }}</h4>
                                    <p class="card-text">Totaal Items</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-collection fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $stats['total_groups'] }}</h4>
                                    <p class="card-text">Gebruikersgroepen</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-diagram-3 fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Snelle Acties</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="{{ route('admin.users') }}" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-people"></i> Gebruikers Beheren
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="{{ route('admin.groups') }}" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-diagram-3"></i> Groepen Beheren
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="{{ route('collection.index') }}" class="btn btn-outline-success w-100">
                                        <i class="bi bi-collection"></i> Collectie Bekijken
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="{{ route('collection.create') }}" class="btn btn-outline-info w-100">
                                        <i class="bi bi-plus-lg"></i> Item Toevoegen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <!-- Recent Users -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recente Gebruikers</h5>
                            <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-primary">Bekijk Alle</a>
                        </div>
                        <div class="card-body">
                            @if($recentUsers->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($recentUsers as $user)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $user->first_name }} {{ $user->last_name }}</h6>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                            <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }} rounded-pill">
                                                {{ $user->is_active ? 'Actief' : 'Inactief' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted text-center">Geen recente gebruikers</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Items -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recente Items</h5>
                            <a href="{{ route('collection.index') }}" class="btn btn-sm btn-outline-primary">Bekijk Alle</a>
                        </div>
                        <div class="card-body">
                            @if($recentItems->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($recentItems as $item)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $item->title }}</h6>
                                                <small class="text-muted">
                                                    {{ ucfirst($item->type) }} - {{ $item->user->first_name }} {{ $item->user->last_name }}
                                                </small>
                                            </div>
                                            <span class="badge bg-secondary rounded-pill">{{ ucfirst($item->type) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted text-center">Geen recente items</p>
                            @endif
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