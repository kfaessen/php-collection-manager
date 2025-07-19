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
            $pdo = new PDO(
                "mysql:host={$request->host};port={$request->port}",
                $request->username,
                $request->password
            );
            
            // Test if we can create/access the database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$request->database}`");
            
            // Test connection to the specific database
            $pdo = new PDO(
                "mysql:host={$request->host};port={$request->port};dbname={$request->database}",
                $request->username,
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful!',
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
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
            // Update .env file
            $this->updateEnvDatabase($request->all());
            
            // Clear config cache
            Artisan::call('config:clear');
            
            // Run migrations
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            
            if ($exitCode !== 0) {
                throw new \Exception('Migrations failed: ' . Artisan::output());
            }

            // Run seeders
            $exitCode = Artisan::call('db:seed', ['--force' => true]);
            
            if ($exitCode !== 0) {
                throw new \Exception('Seeders failed: ' . Artisan::output());
            }

            return response()->json([
                'success' => true,
                'message' => 'Database configured and migrations completed successfully!',
                'redirect' => route('setup.admin'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Setup failed: ' . $e->getMessage(),
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
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            throw new \Exception('.env file not found');
        }

        $envContent = file_get_contents($envFile);
        
        // Update database configuration
        $envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST=' . $config['host'], $envContent);
        $envContent = preg_replace('/DB_PORT=.*/', 'DB_PORT=' . $config['port'], $envContent);
        $envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $config['database'], $envContent);
        $envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=' . $config['username'], $envContent);
        $envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=' . $config['password'], $envContent);
        
        // Ensure APP_KEY is set
        if (!preg_match('/APP_KEY=base64:/', $envContent)) {
            $envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=base64:' . base64_encode(random_bytes(32)), $envContent);
        }
        
        file_put_contents($envFile, $envContent);
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