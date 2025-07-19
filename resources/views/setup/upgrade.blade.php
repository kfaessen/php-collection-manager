<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Upgrade - Collection Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .setup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 700px;
            width: 90%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .status-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .status-box h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            color: #666;
        }
        
        .status-value {
            font-weight: 500;
            color: #333;
        }
        
        .status-value.success {
            color: #28a745;
        }
        
        .status-value.warning {
            color: #ffc107;
        }
        
        .status-value.danger {
            color: #dc3545;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
            margin-right: 1rem;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 1rem 0;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .info-box {
            background: #e7f3ff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-box h3 {
            color: #0056b3;
            margin-bottom: 1rem;
        }
        
        .info-box p {
            color: #0056b3;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <div class="logo">📚 Collection Manager</div>
            <h1 class="title">Database Upgrade</h1>
            <p class="subtitle">Update je database naar de nieuwste versie</p>
        </div>
        
        <div class="info-box">
            <h3>⚠️ Belangrijk</h3>
            <p>Maak altijd een backup van je database voordat je een upgrade uitvoert. Dit zorgt ervoor dat je gegevens veilig zijn in geval van problemen.</p>
        </div>
        
        <div id="alerts"></div>
        
        <div class="status-box">
            <h3>Database Status</h3>
            <div class="status-item">
                <span class="status-label">Totaal aantal migraties:</span>
                <span class="status-value">{{ $migrationStatus['total_files'] ?? 0 }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Uitgevoerde migraties:</span>
                <span class="status-value success">{{ $migrationStatus['ran_migrations'] ?? 0 }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Wachtende migraties:</span>
                <span class="status-value {{ ($migrationStatus['pending_migrations'] ?? 0) > 0 ? 'warning' : 'success' }}">
                    {{ $migrationStatus['pending_migrations'] ?? 0 }}
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Laatste migratie:</span>
                <span class="status-value">{{ $migrationStatus['last_migration'] ?? 'Geen' }}</span>
            </div>
        </div>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Database upgrade uitvoeren...</p>
        </div>
        
        <div style="text-align: center;">
            @if(($migrationStatus['pending_migrations'] ?? 0) > 0)
                <button type="button" class="btn btn-success" onclick="runUpgrade()">Database Upgrade Uitvoeren</button>
            @else
                <div class="alert alert-success">
                    Je database is al up-to-date! Er zijn geen nieuwe migraties beschikbaar.
                </div>
            @endif
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Terug naar Admin</a>
            <a href="{{ route('collection.index') }}" class="btn">Naar Collectie</a>
        </div>
    </div>
    
    <script>
        function showAlert(message, type = 'info') {
            const alerts = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alerts.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
        
        async function runUpgrade() {
            if (!confirm('Weet je zeker dat je de database upgrade wilt uitvoeren? Maak eerst een backup van je database.')) {
                return;
            }
            
            showLoading();
            
            try {
                const response = await fetch('{{ route("setup.run-upgrade") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Er is een fout opgetreden bij het uitvoeren van de upgrade.', 'danger');
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html> 