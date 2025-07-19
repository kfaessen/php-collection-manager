<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class DatabaseAdminController extends Controller
{
    /**
     * Show the database admin dashboard.
     */
    public function index()
    {
        $connectionStatus = $this->testConnection();
        $databaseInfo = $this->getDatabaseInfo();
        $migrationStatus = $this->getMigrationStatus();
        
        return view('admin.database.index', compact('connectionStatus', 'databaseInfo', 'migrationStatus'));
    }

    /**
     * Test database connection.
     */
    public function testConnection()
    {
        try {
            $pdo = DB::connection()->getPdo();
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            
            return [
                'status' => 'success',
                'message' => 'Database connection successful',
                'version' => $version,
                'connection' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * Get database information.
     */
    public function getDatabaseInfo()
    {
        try {
            $connection = DB::connection();
            $database = config('database.connections.' . config('database.default') . '.database');
            
            // Get table count
            $tables = DB::select("SHOW TABLES");
            $tableCount = count($tables);
            
            // Get database size
            $sizeQuery = "SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
                FROM information_schema.tables 
                WHERE table_schema = ?";
            $size = DB::select($sizeQuery, [$database]);
            $databaseSize = $size[0]->size_mb ?? 0;
            
            return [
                'database' => $database,
                'table_count' => $tableCount,
                'size_mb' => $databaseSize,
                'tables' => $tables,
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get migration status.
     */
    public function getMigrationStatus()
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
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create database.
     */
    public function createDatabase(Request $request)
    {
        $request->validate([
            'database_name' => 'required|string|max:64',
        ]);

        try {
            $databaseName = $request->database_name;
            
            // Connect without specifying database
            $config = config('database.connections.' . config('database.default'));
            $config['database'] = null;
            
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password']
            );
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");
            
            // Update .env file
            $this->updateEnvDatabase($databaseName);
            
            return response()->json([
                'success' => true,
                'message' => "Database '{$databaseName}' created successfully",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create database: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run migrations.
     */
    public function runMigrations(Request $request)
    {
        try {
            $output = [];
            
            // Clear config cache first
            Artisan::call('config:clear');
            
            // Run migrations
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            
            if ($exitCode === 0) {
                $output = Artisan::output();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Migrations completed successfully',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Migrations failed',
                    'output' => Artisan::output(),
                ], 500);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run seeders.
     */
    public function runSeeders(Request $request)
    {
        try {
            $output = [];
            
            // Run seeders
            $exitCode = Artisan::call('db:seed', ['--force' => true]);
            
            if ($exitCode === 0) {
                $output = Artisan::output();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Seeders completed successfully',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Seeders failed',
                    'output' => Artisan::output(),
                ], 500);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seeder error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset database.
     */
    public function resetDatabase(Request $request)
    {
        try {
            $output = [];
            
            // Drop all tables
            $exitCode = Artisan::call('migrate:reset', ['--force' => true]);
            
            if ($exitCode === 0) {
                $output = Artisan::output();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Database reset successfully',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Database reset failed',
                    'output' => Artisan::output(),
                ], 500);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reset error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh database (reset + migrate + seed).
     */
    public function refreshDatabase(Request $request)
    {
        try {
            $output = [];
            
            // Refresh database
            $exitCode = Artisan::call('migrate:refresh', ['--force' => true, '--seed' => true]);
            
            if ($exitCode === 0) {
                $output = Artisan::output();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Database refreshed successfully',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Database refresh failed',
                    'output' => Artisan::output(),
                ], 500);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show database tables.
     */
    public function showTables()
    {
        try {
            $tables = DB::select("SHOW TABLES");
            $tableDetails = [];
            
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                
                // Get table structure
                $columns = DB::select("DESCRIBE `{$tableName}`");
                
                // Get row count
                $count = DB::table($tableName)->count();
                
                $tableDetails[] = [
                    'name' => $tableName,
                    'columns' => $columns,
                    'row_count' => $count,
                ];
            }
            
            return response()->json([
                'success' => true,
                'tables' => $tableDetails,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get tables: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update .env file with new database name.
     */
    private function updateEnvDatabase($databaseName)
    {
        $envFile = base_path('.env');
        
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            $content = preg_replace(
                '/^DB_DATABASE=.*$/m',
                "DB_DATABASE={$databaseName}",
                $content
            );
            file_put_contents($envFile, $content);
            
            // Clear config cache
            Artisan::call('config:clear');
        }
    }

    /**
     * Get database configuration.
     */
    public function getConfig()
    {
        $config = config('database.connections.' . config('database.default'));
        
        return response()->json([
            'connection' => config('database.default'),
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database'],
            'username' => $config['username'],
            'charset' => $config['charset'],
        ]);
    }

    /**
     * Test specific database configuration.
     */
    public function testConfig(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            $dsn = "mysql:host={$request->host};port={$request->port};dbname={$request->database}";
            $pdo = new PDO($dsn, $request->username, $request->password);
            
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            
            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
                'version' => $version,
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 500);
        }
    }
} 