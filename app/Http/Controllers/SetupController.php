<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use PDO;
use PDOException;

class SetupController extends Controller
{
    /**
     * Check if application is already set up.
     */
    public function checkSetup()
    {
        // Check if migrations table exists and has been run
        $isSetup = $this->isApplicationSetup();
        
        if ($isSetup) {
            return redirect('/');
        }
        
        return redirect()->route('setup.welcome');
    }

    /**
     * Show setup welcome page.
     */
    public function welcome()
    {
        if ($this->isApplicationSetup()) {
            return redirect('/');
        }

        return view('setup.welcome');
    }

    /**
     * Show database configuration page.
     */
    public function database()
    {
        if ($this->isApplicationSetup()) {
            return redirect('/');
        }

        return view('setup.database');
    }

    /**
     * Test database connection.
     */
    public function testDatabase(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            // First, try to connect without specifying database to test server connection
            $pdo = new PDO(
                "mysql:host={$request->host};port={$request->port}",
                $request->username,
                $request->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Test if we can create/access the database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$request->database}`");
            
            // Test connection to the specific database
            $pdo = new PDO(
                "mysql:host={$request->host};port={$request->port};dbname={$request->database}",
                $request->username,
                $request->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Test if we can create tables (check permissions)
            $pdo->exec("CREATE TABLE IF NOT EXISTS `test_permissions` (id INT)");
            $pdo->exec("DROP TABLE IF EXISTS `test_permissions`");

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful! Server is ready for migrations.',
            ]);
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Provide more specific error messages
            $userMessage = 'Database connection failed: ';
            
            switch ($errorCode) {
                case 2002:
                    $userMessage .= 'Kan de database server niet bereiken. Controleer of de host en port correct zijn.';
                    break;
                case 1045:
                    $userMessage .= 'Ongeldige gebruikersnaam of wachtwoord.';
                    break;
                case 1049:
                    $userMessage .= 'Database bestaat niet en kan niet worden aangemaakt. Controleer gebruikersrechten.';
                    break;
                case 1044:
                    $userMessage .= 'Geen toegang tot database. Controleer gebruikersrechten.';
                    break;
                case 1142:
                    $userMessage .= 'Geen rechten om tabellen aan te maken. Controleer database rechten.';
                    break;
                default:
                    $userMessage .= $errorMessage;
            }
            
            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'debug_info' => config('app.debug') ? $errorMessage : null,
            ], 400);
        }
    }

    /**
     * Save database configuration and run migrations.
     */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            // First test the connection
            $this->testDatabase($request);
            
            // Update .env file
            $this->updateEnvDatabase($request->all());
            
            // Clear config cache
            Artisan::call('config:clear');
            
            // Run migrations with detailed output
            $migrationOutput = '';
            $exitCode = Artisan::call('migrate', ['--force' => true, '--verbose' => true]);
            $migrationOutput = Artisan::output();
            
            if ($exitCode !== 0) {
                throw new \Exception('Migrations failed: ' . $migrationOutput);
            }

            // Run seeders with detailed output
            $seederOutput = '';
            $exitCode = Artisan::call('db:seed', ['--force' => true, '--verbose' => true]);
            $seederOutput = Artisan::output();
            
            if ($exitCode !== 0) {
                throw new \Exception('Seeders failed: ' . $seederOutput);
            }

            return response()->json([
                'success' => true,
                'message' => 'Database configured and migrations completed successfully! All tables have been created.',
                'migration_output' => $migrationOutput,
                'seeder_output' => $seederOutput,
                'redirect' => route('setup.admin'),
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Provide more user-friendly error messages
            if (strpos($errorMessage, 'Could not create .env file') !== false) {
                $errorMessage = 'Kan .env bestand niet aanmaken. Controleer bestandsrechten op de server.';
            } elseif (strpos($errorMessage, 'Could not read .env file') !== false) {
                $errorMessage = 'Kan .env bestand niet lezen. Controleer bestandsrechten op de server.';
            } elseif (strpos($errorMessage, 'Could not write to .env file') !== false) {
                $errorMessage = 'Kan .env bestand niet schrijven. Controleer bestandsrechten op de server.';
            } elseif (strpos($errorMessage, 'Migrations failed') !== false) {
                $errorMessage = 'Database migraties zijn mislukt. Controleer database rechten en probeer opnieuw.';
            } elseif (strpos($errorMessage, 'Seeders failed') !== false) {
                $errorMessage = 'Database seeders zijn mislukt. Controleer database rechten en probeer opnieuw.';
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Setup failed: ' . $errorMessage,
                'debug_info' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show admin user creation page.
     */
    public function admin()
    {
        if ($this->isApplicationSetup()) {
            return redirect('/');
        }

        return view('setup.admin');
    }

    /**
     * Create admin user.
     */
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Create admin user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            // Assign admin role
            $user->assignRole('admin');

            // Mark setup as complete
            $this->markSetupComplete();

            return response()->json([
                'success' => true,
                'message' => 'Admin user created successfully!',
                'redirect' => route('setup.complete'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show setup complete page.
     */
    public function complete()
    {
        if (!$this->isApplicationSetup()) {
            return redirect()->route('setup.welcome');
        }

        return view('setup.complete');
    }

    /**
     * Check if application is already set up.
     */
    private function isApplicationSetup()
    {
        try {
            // Check if migrations table exists and has been run
            if (!Schema::hasTable('migrations')) {
                return false;
            }

            $migrations = DB::table('migrations')->count();
            return $migrations > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update .env file with database configuration.
     */
    private function updateEnvDatabase($config)
    {
        // Try multiple possible .env file locations
        $possibleEnvFiles = [
            base_path('.env'),
            dirname(base_path()) . '/.env',
            '/var/www/.env',
            '/home/*/public_html/.env',
            $_SERVER['DOCUMENT_ROOT'] . '/../.env',
            $_SERVER['DOCUMENT_ROOT'] . '/.env',
        ];

        $envFile = null;
        foreach ($possibleEnvFiles as $file) {
            if (file_exists($file) && is_readable($file) && is_writable($file)) {
                $envFile = $file;
                break;
            }
        }

        // If no .env file found, try to create one
        if (!$envFile) {
            $envFile = base_path('.env');
            
            // Create .env file if it doesn't exist
            if (!file_exists($envFile)) {
                $defaultEnvContent = $this->getDefaultEnvContent($config);
                if (file_put_contents($envFile, $defaultEnvContent) === false) {
                    throw new \Exception('Could not create .env file. Please check file permissions.');
                }
                return;
            }
        }

        // Read current .env content
        $envContent = file_get_contents($envFile);
        if ($envContent === false) {
            throw new \Exception('Could not read .env file. Please check file permissions.');
        }

        // Update database configuration
        $envContent = $this->updateEnvVariable($envContent, 'DB_HOST', $config['host']);
        $envContent = $this->updateEnvVariable($envContent, 'DB_PORT', $config['port']);
        $envContent = $this->updateEnvVariable($envContent, 'DB_DATABASE', $config['database']);
        $envContent = $this->updateEnvVariable($envContent, 'DB_USERNAME', $config['username']);
        $envContent = $this->updateEnvVariable($envContent, 'DB_PASSWORD', $config['password']);
        
        // Ensure APP_KEY is set
        if (!preg_match('/APP_KEY=base64:/', $envContent)) {
            $envContent = $this->updateEnvVariable($envContent, 'APP_KEY', 'base64:' . base64_encode(random_bytes(32)));
        }

        // Write updated content
        if (file_put_contents($envFile, $envContent) === false) {
            throw new \Exception('Could not write to .env file. Please check file permissions.');
        }

        // Clear config cache to reload new settings
        \Artisan::call('config:clear');
    }

    /**
     * Update or add an environment variable in .env content.
     */
    private function updateEnvVariable($content, $key, $value)
    {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$value}";
        
        if (preg_match($pattern, $content)) {
            // Variable exists, update it
            return preg_replace($pattern, $replacement, $content);
        } else {
            // Variable doesn't exist, add it
            return $content . "\n{$replacement}";
        }
    }

    /**
     * Get default .env content for new installations.
     */
    private function getDefaultEnvContent($config)
    {
        return "APP_NAME=\"Collection Manager\"
APP_ENV=production
APP_KEY=base64:" . base64_encode(random_bytes(32)) . "
APP_DEBUG=false
APP_URL=" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST={$config['host']}
DB_PORT={$config['port']}
DB_DATABASE={$config['database']}
DB_USERNAME={$config['username']}
DB_PASSWORD={$config['password']}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=\"hello@example.com\"
MAIL_FROM_NAME=\"\${APP_NAME}\"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME=\"\${APP_NAME}\"
VITE_PUSHER_APP_KEY=\"\${PUSHER_APP_KEY}\"
VITE_PUSHER_HOST=\"\${PUSHER_HOST}\"
VITE_PUSHER_PORT=\"\${PUSHER_PORT}\"
VITE_PUSHER_SCHEME=\"\${PUSHER_SCHEME}\"
VITE_PUSHER_APP_CLUSTER=\"\${PUSHER_APP_CLUSTER}\"
";
    }

    /**
     * Mark setup as complete.
     */
    private function markSetupComplete()
    {
        // Create a setup completion marker
        $setupFile = storage_path('app/setup_complete');
        file_put_contents($setupFile, date('Y-m-d H:i:s'));
    }

    /**
     * Show upgrade page for existing installations.
     */
    public function upgrade()
    {
        if (!$this->isApplicationSetup()) {
            return redirect()->route('setup.welcome');
        }

        $migrationStatus = $this->getMigrationStatus();
        
        return view('setup.upgrade', compact('migrationStatus'));
    }

    /**
     * Run database upgrades.
     */
    public function runUpgrade(Request $request)
    {
        if (!$this->isApplicationSetup()) {
            return redirect()->route('setup.welcome');
        }

        try {
            // Run pending migrations
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            
            if ($exitCode !== 0) {
                throw new \Exception('Migrations failed: ' . Artisan::output());
            }

            // Run seeders if needed
            $exitCode = Artisan::call('db:seed', ['--force' => true]);
            
            if ($exitCode !== 0) {
                throw new \Exception('Seeders failed: ' . Artisan::output());
            }

            return response()->json([
                'success' => true,
                'message' => 'Database upgrade completed successfully!',
                'redirect' => route('admin.dashboard'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upgrade failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get migration status.
     */
    private function getMigrationStatus()
    {
        try {
            $migrations = DB::table('migrations')->get();
            $files = collect(glob(database_path('migrations/*.php')));
            
            $pending = $files->count() - $migrations->count();
            
            return [
                'total_files' => $files->count(),
                'ran_migrations' => $migrations->count(),
                'pending_migrations' => max(0, $pending),
                'last_migration' => $migrations->last()?->migration ?? 'None',
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
} 