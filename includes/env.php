<?php
namespace CollectionManager;

/**
 * Environment Configuration Loader
 * 
 * Dit bestand laadt configuratie uit .env bestand
 */

class Environment 
{
    private static $loaded = false;
    
    /**
     * Laad de .env variabelen
     */
    public static function load($path = null) 
    {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            throw new \Exception("Environment bestand niet gevonden: " . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip commentaren
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Verwijder quotes als aanwezig
                if (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                    ($value[0] === "'" && $value[strlen($value) - 1] === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Haal een environment variabele op
     */
    public static function get($key, $default = null) 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Check of we in debug mode zijn
     */
    public static function isDebug() 
    {
        return self::get('APP_DEBUG') === 'true';
    }
    
    /**
     * Haal het database prefix op
     */
    public static function getDbPrefix() 
    {
        return self::get('DB_PREFIX', 'dev_');
    }
} 