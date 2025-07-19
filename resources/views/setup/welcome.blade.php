<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Manager - Setup Wizard</title>
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
        }
        
        .setup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .welcome-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .welcome-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .features {
            text-align: left;
            margin-bottom: 2rem;
        }
        
        .features h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .feature-list {
            list-style: none;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            color: #666;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .feature-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .setup-steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .setup-steps h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .step-number {
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-right: 1rem;
        }
        
        .step-text {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">📚 Collection Manager</div>
        
        <h1 class="welcome-title">Welkom bij de Setup Wizard</h1>
        
        <p class="welcome-text">
            Collection Manager is een krachtige applicatie voor het beheren van je persoonlijke collectie van games, films, boeken, muziek en meer.
        </p>
        
        <div class="features">
            <h3>Wat kun je verwachten:</h3>
            <ul class="feature-list">
                <li>Beheer je collectie items met uitgebreide metadata</li>
                <li>Barcode scanning voor automatische item detectie</li>
                <li>Waarde tracking en conditie beoordeling</li>
                <li>Delen van collecties via veilige links</li>
                <li>Export en import functionaliteit</li>
                <li>Gebruikersbeheer en rollen systeem</li>
                <li>Push notificaties en real-time updates</li>
            </ul>
        </div>
        
        <div class="setup-steps">
            <h3>Setup proces:</h3>
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-text">Database configuratie</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">Database migraties uitvoeren</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Admin gebruiker aanmaken</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-text">Klaar om te gebruiken!</div>
            </div>
        </div>
        
        <a href="{{ route('setup.database') }}" class="btn">Start Setup Wizard</a>
    </div>
</body>
</html> 