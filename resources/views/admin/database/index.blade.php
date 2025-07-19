<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Beheer - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .status-success {
            border-left: 4px solid #28a745;
        }
        .status-error {
            border-left: 4px solid #dc3545;
        }
        .status-warning {
            border-left: 4px solid #ffc107;
        }
        .action-btn {
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
        .log-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="mb-3">
                    <i class="fas fa-database me-3"></i>Database Beheer
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Database</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Connection Status -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card status-card {{ $connectionStatus['status'] === 'success' ? 'status-success' : 'status-error' }}">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-plug me-2"></i>Database Connectie
                        </h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-{{ $connectionStatus['status'] === 'success' ? 'success' : 'danger' }}">
                                        {{ $connectionStatus['status'] === 'success' ? 'Verbonden' : 'Fout' }}
                                    </span>
                                </p>
                                @if($connectionStatus['status'] === 'success')
                                    <p class="mb-1"><strong>Versie:</strong> {{ $connectionStatus['version'] }}</p>
                                    <p class="mb-1"><strong>Database:</strong> {{ $connectionStatus['database'] }}</p>
                                    <p class="mb-0"><strong>Host:</strong> {{ $connectionStatus['host'] }}</p>
                                @else
                                    <p class="mb-0 text-danger">{{ $connectionStatus['message'] }}</p>
                                @endif
                            </div>
                            <button class="btn btn-outline-primary" onclick="testConnection()">
                                <i class="fas fa-sync-alt me-2"></i>Test Connectie
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card status-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle me-2"></i>Database Informatie
                        </h5>
                        @if(isset($databaseInfo['error']))
                            <p class="text-danger">{{ $databaseInfo['error'] }}</p>
                        @else
                            <p class="mb-1"><strong>Database:</strong> {{ $databaseInfo['database'] }}</p>
                            <p class="mb-1"><strong>Tabellen:</strong> {{ $databaseInfo['table_count'] }}</p>
                            <p class="mb-0"><strong>Grootte:</strong> {{ $databaseInfo['size_mb'] }} MB</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Migration Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card status-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-code-branch me-2"></i>Migratie Status
                        </h5>
                        @if(isset($migrationStatus['error']))
                            <p class="text-danger">{{ $migrationStatus['error'] }}</p>
                        @else
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-primary">{{ $migrationStatus['total_files'] }}</h4>
                                        <p class="mb-0">Totaal Bestanden</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-success">{{ $migrationStatus['ran_migrations'] }}</h4>
                                        <p class="mb-0">Uitgevoerd</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-warning">{{ $migrationStatus['pending_migrations'] }}</h4>
                                        <p class="mb-0">Wachtend</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6 class="text-muted">{{ $migrationStatus['last_migration'] }}</h6>
                                        <p class="mb-0">Laatste Migratie</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>Database Acties
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <button class="btn btn-primary w-100 action-btn" onclick="runMigrations()">
                                    <i class="fas fa-play me-2"></i>Run Migrations
                                </button>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button class="btn btn-success w-100 action-btn" onclick="runSeeders()">
                                    <i class="fas fa-seedling me-2"></i>Run Seeders
                                </button>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button class="btn btn-warning w-100 action-btn" onclick="refreshDatabase()">
                                    <i class="fas fa-redo me-2"></i>Refresh Database
                                </button>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button class="btn btn-danger w-100 action-btn" onclick="resetDatabase()">
                                    <i class="fas fa-trash me-2"></i>Reset Database
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Database -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>Database Aanmaken
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="createDatabaseForm">
                            <div class="mb-3">
                                <label for="databaseName" class="form-label">Database Naam</label>
                                <input type="text" class="form-control" id="databaseName" name="database_name" 
                                       placeholder="collection_manager" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database me-2"></i>Database Aanmaken
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Database Configuratie
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="configInfo">
                            <p class="text-muted">Laden...</p>
                        </div>
                        <button class="btn btn-outline-secondary" onclick="loadConfig()">
                            <i class="fas fa-sync-alt me-2"></i>Ververs Configuratie
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>Database Tabellen
                        </h5>
                        <button class="btn btn-outline-primary" onclick="loadTables()">
                            <i class="fas fa-sync-alt me-2"></i>Ververs
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="tablesInfo">
                            <p class="text-muted">Laden...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Output -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-terminal me-2"></i>Log Output
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="logOutput" class="log-output">
                            <p class="text-muted">Wachtend op acties...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bevestiging</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                    <button type="button" class="btn btn-danger" id="confirmAction">Bevestigen</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadConfig();
            loadTables();
        });

        function log(message, type = 'info') {
            const logOutput = document.getElementById('logOutput');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `text-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'muted'}`;
            logEntry.innerHTML = `[${timestamp}] ${message}`;
            logOutput.appendChild(logEntry);
            logOutput.scrollTop = logOutput.scrollHeight;
        }

        function testConnection() {
            log('Testing database connection...');
            fetch('{{ route("database.test-connection") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    log('Connection test successful: ' + data.message, 'success');
                } else {
                    log('Connection test failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                log('Connection test error: ' + error.message, 'error');
            });
        }

        function runMigrations() {
            if (confirm('Weet je zeker dat je de migraties wilt uitvoeren?')) {
                log('Running migrations...');
                fetch('{{ route("database.migrate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        log('Migrations completed successfully', 'success');
                        log(data.output);
                    } else {
                        log('Migrations failed: ' + data.message, 'error');
                        log(data.output);
                    }
                })
                .catch(error => {
                    log('Migration error: ' + error.message, 'error');
                });
            }
        }

        function runSeeders() {
            if (confirm('Weet je zeker dat je de seeders wilt uitvoeren?')) {
                log('Running seeders...');
                fetch('{{ route("database.seed") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        log('Seeders completed successfully', 'success');
                        log(data.output);
                    } else {
                        log('Seeders failed: ' + data.message, 'error');
                        log(data.output);
                    }
                })
                .catch(error => {
                    log('Seeder error: ' + error.message, 'error');
                });
            }
        }

        function refreshDatabase() {
            showConfirmModal(
                'Weet je zeker dat je de database wilt verversen? Dit zal alle data verwijderen en opnieuw migreren en seeden.',
                () => {
                    log('Refreshing database...');
                    fetch('{{ route("database.refresh") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            log('Database refreshed successfully', 'success');
                            log(data.output);
                        } else {
                            log('Database refresh failed: ' + data.message, 'error');
                            log(data.output);
                        }
                    })
                    .catch(error => {
                        log('Refresh error: ' + error.message, 'error');
                    });
                }
            );
        }

        function resetDatabase() {
            showConfirmModal(
                'Weet je zeker dat je de database wilt resetten? Dit zal ALLE data permanent verwijderen!',
                () => {
                    log('Resetting database...');
                    fetch('{{ route("database.reset") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            log('Database reset successfully', 'success');
                            log(data.output);
                        } else {
                            log('Database reset failed: ' + data.message, 'error');
                            log(data.output);
                        }
                    })
                    .catch(error => {
                        log('Reset error: ' + error.message, 'error');
                    });
                }
            );
        }

        function loadConfig() {
            fetch('{{ route("database.config") }}')
            .then(response => response.json())
            .then(data => {
                const configInfo = document.getElementById('configInfo');
                configInfo.innerHTML = `
                    <p><strong>Connection:</strong> ${data.connection}</p>
                    <p><strong>Host:</strong> ${data.host}</p>
                    <p><strong>Port:</strong> ${data.port}</p>
                    <p><strong>Database:</strong> ${data.database}</p>
                    <p><strong>Username:</strong> ${data.username}</p>
                    <p><strong>Charset:</strong> ${data.charset}</p>
                `;
            })
            .catch(error => {
                document.getElementById('configInfo').innerHTML = '<p class="text-danger">Error loading config</p>';
            });
        }

        function loadTables() {
            fetch('{{ route("database.tables") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tablesInfo = document.getElementById('tablesInfo');
                    let html = '<div class="table-responsive"><table class="table table-striped">';
                    html += '<thead><tr><th>Tabel</th><th>Kolommen</th><th>Rijen</th></tr></thead><tbody>';
                    
                    data.tables.forEach(table => {
                        html += `<tr>
                            <td><strong>${table.name}</strong></td>
                            <td>${table.columns.length}</td>
                            <td>${table.row_count}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    tablesInfo.innerHTML = html;
                } else {
                    document.getElementById('tablesInfo').innerHTML = '<p class="text-danger">Error loading tables</p>';
                }
            })
            .catch(error => {
                document.getElementById('tablesInfo').innerHTML = '<p class="text-danger">Error loading tables</p>';
            });
        }

        function showConfirmModal(message, action) {
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmAction').onclick = () => {
                action();
                bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
            };
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }

        // Create database form
        document.getElementById('createDatabaseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            log('Creating database: ' + formData.get('database_name'));
            
            fetch('{{ route("database.create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    database_name: formData.get('database_name')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    log('Database created successfully: ' + data.message, 'success');
                    this.reset();
                } else {
                    log('Database creation failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                log('Database creation error: ' + error.message, 'error');
            });
        });
    </script>
</body>
</html> 