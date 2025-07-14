<?php
/**
 * Public Database Setup Script
 * This script can be accessed publicly to setup the database
 */

// Prevent direct access in production
if (!isset($_GET['token']) || $_GET['token'] !== 'setup_' . date('Ymd')) {
    http_response_code(403);
    die('Access denied. Setup token required.');
}

// Load required files
require_once __DIR__ . '/../includes/Environment.php';
require_once __DIR__ . '/../includes/Database.php';

use CollectionManager\Environment;
use CollectionManager\Database;

// Enable error reporting for setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Collection Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Setup - Collection Manager</h1>
        
        <?php
        $action = $_GET['action'] ?? 'check';
        
        if ($action === 'setup') {
            echo "<h2>Database Setup Uitvoeren</h2>";
            
            try {
                // Initialize database (this will automatically run migrations)
                Database::init();
                
                echo "<div class='success'>✅ Database setup succesvol voltooid!</div>";
                echo "<p>De database en alle tabellen zijn aangemaakt en geïnitialiseerd.</p>";
                
                // Check if setup was successful
                if (!Database::needsSetup()) {
                    echo "<div class='success'>✅ Database is volledig geconfigureerd en klaar voor gebruik.</div>";
                    echo "<p><a href='index.php' class='btn'>Ga naar de applicatie</a></p>";
                } else {
                    echo "<div class='warning'>⚠️ Database setup voltooid, maar er zijn nog migraties nodig.</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Database setup mislukt: " . htmlspecialchars($e->getMessage()) . "</div>";
                echo "<p>Controleer je database configuratie in het .env bestand.</p>";
            }
            
        } else {
            echo "<h2>Database Status Controleren</h2>";
            
            try {
                // Check if database needs setup
                $needsSetup = Database::needsSetup();
                $currentVersion = Database::getCurrentVersion();
                $targetVersion = Database::getTargetVersion();
                
                echo "<div class='info'>📊 Database Status:</div>";
                echo "<ul>";
                echo "<li>Huidige versie: <strong>$currentVersion</strong></li>";
                echo "<li>Doel versie: <strong>$targetVersion</strong></li>";
                echo "<li>Setup nodig: <strong>" . ($needsSetup ? 'Ja' : 'Nee') . "</strong></li>";
                echo "</ul>";
                
                if ($needsSetup) {
                    echo "<div class='warning'>⚠️ Database setup is vereist.</div>";
                    echo "<p>Klik op de knop hieronder om de database automatisch te configureren:</p>";
                    echo "<a href='?action=setup&token=" . $_GET['token'] . "' class='btn'>Database Setup Uitvoeren</a>";
                } else {
                    echo "<div class='success'>✅ Database is volledig geconfigureerd!</div>";
                    echo "<p><a href='index.php' class='btn'>Ga naar de applicatie</a></p>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Kan database status niet controleren: " . htmlspecialchars($e->getMessage()) . "</div>";
                echo "<p>Dit kan betekenen dat de database nog niet bestaat of dat er connectie problemen zijn.</p>";
                echo "<a href='?action=setup&token=" . $_GET['token'] . "' class='btn'>Probeer Database Setup</a>";
            }
        }
        ?>
        
        <hr>
        <h3>Setup Instructies</h3>
        <p>Om de database setup uit te voeren:</p>
        <ol>
            <li>Zorg ervoor dat je database configuratie correct is in het <code>.env</code> bestand</li>
            <li>Klik op "Database Setup Uitvoeren" hierboven</li>
            <li>De setup zal automatisch alle tabellen aanmaken en initialiseren</li>
            <li>Na succesvolle setup kun je naar de applicatie navigeren</li>
        </ol>
        
        <p><strong>Let op:</strong> Deze setup pagina is alleen toegankelijk met een geldige token voor beveiliging.</p>
    </div>
</body>
</html> 