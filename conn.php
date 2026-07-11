<?php
// ============================================================
// Secure Database Configuration & Auto Logic
// ============================================================

// ✅ System Limits & Error Reporting
set_time_limit(300);
ini_set('memory_limit', '1G');
error_reporting(E_ALL & ~E_DEPRECATED); 
ini_set('display_errors', '0'); // Keeps JSON responses clean

// ✅ Define root directory
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}
 
 

$host = $_SERVER['HTTP_HOST'] ?? '';
$parts = explode('.', $host);

// --- Protocol Detection ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

// Check if it's a valid domain (at least one dot and not an IP)
if (count($parts) >= 2 && !filter_var($host, FILTER_VALIDATE_IP)) {
    
    // If it's a subdomain (e.g., sub.domain.com), get the root (domain.com)
    // We ignore 'www' as a subdomain
    if (count($parts) > 2 && $parts[0] !== 'www') {
        // Gets the last two parts: domain.com
        $rootDomain = $parts[count($parts)-2] . '.' . $parts[count($parts)-1];
        define('SYSTEM_MEDIA_BASE', $protocol . $rootDomain . '/');
    } else {
        // Main domain (domain.com or www.domain.com)
        $cleanHost = str_replace('www.', '', $host);
        define('SYSTEM_MEDIA_BASE', $protocol . $cleanHost . '/');
    }
} else {
    
        $cleanHost = str_replace('www.', '', $host);
        define('SYSTEM_MEDIA_BASE', $protocol . $cleanHost . '/');
    // Localhost or direct IP access
    // define('SYSTEM_MEDIA_BASE', '../');
}



// ✅ Environment Detection (EXE vs WEB)
$isExe = (($_SERVER['HTTP_HOST'] ?? '') === 'heserver');
$online = !$isExe;

/**
 * HELPER: SECURE DATA RETRIEVAL
 * Fetches from ExeOutput (strIDs) or Browser Env ($_ENV).
 */
function get_config_val(string $exeId, string $envKey): string {
    global $isExe;
    if ($isExe && function_exists('exo_get_protstring')) {
        $val = exo_get_protstring($exeId);
        if ($val !== '') return $val;
    }
    return isset($_ENV[$envKey]) ? (string)$_ENV[$envKey] : '';
}

// ============================================================
// Composer & ENV Loading (WEB ONLY)
// ============================================================
if ($online) {
    $autoloadFile = ROOT_DIR . '/assets/libraries/secure/vendor/autoload.php';
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
            $dotenv->load();
        } catch (Throwable $e) {
            // Silently handle missing .env
        }
    }
}

// ============================================================
// Define CONSTANTS (Zero Strings Visible)
// Map these in ExeOutput String Protection:
// str0: Pass, str1: Name, str2: User, str3: Host, str4: Driver, 
// str5: Port, str6: Charset, str7: DumpPath, str8: LoginKey, 
// str9: DumpKey, str10: SMS, str11: Public, str12: Secret
// ============================================================
if (!defined('DB_HOST'))       define('DB_HOST',       get_config_val('str0', 'DB_HOST'));
if (!defined('DB_USER'))       define('DB_USER',       get_config_val('str1', 'DB_USER'));
if (!defined('DB_PASSWORD'))   define('DB_PASSWORD',   get_config_val('str2', 'DB_PASSWORD'));
if (!defined('DB_NAME'))       define('DB_NAME',       get_config_val('str3', 'DB_NAME'));
if (!defined('DB_PORT'))       define('DB_PORT',       get_config_val('str4', 'DB_PORT'));
if (!defined('DB_DRIVER'))     define('DB_DRIVER',     get_config_val('str5', 'DB_DRIVER'));
if (!defined('DB_CHARSET'))    define('DB_CHARSET',    get_config_val('str6', 'DB_CHARSET'));

if (!defined('SMS_KEY'))       define('SMS_KEY',       get_config_val('str7', 'SMS_KEY'));
if (!defined('login_encript')) define('login_encript', get_config_val('str8', 'login_encript'));
if (!defined('DUMP_KEY'))      define('DUMP_KEY',      get_config_val('str9', 'DUMP_KEY'));
if (!defined('PublicKey'))     define('PublicKey',     get_config_val('str10', 'PublicKey'));
if (!defined('secretKey'))     define('secretKey',     get_config_val('str11', 'secretKey'));

if (!defined('SQL_DUMP_FILE')) { 
    $dumpPath = ltrim(get_config_val('str11', 'SQL_DUMP_FILE'), '/');
    define('SQL_DUMP_FILE', ROOT_DIR . '/' . $dumpPath);
}

// Map variables for legacy code support
$login_encript = login_encript; 
$PublicKey     = PublicKey; 
$secretKey     = secretKey; 
 
// ============================================================
// Try connecting to database
// ============================================================ 
try {

    $dsn = DB_DRIVER . ":host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=" . DB_CHARSET ;

    $con = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

} catch (PDOException $e) {

    // ========================================================
    // Database does NOT exist
    // ========================================================
    if ($e->getCode() == 1049) {

        // ====================================================
        // OPTION 1: USE SQL DUMP
        // ====================================================
        if (DUMP_KEY === 'use_sql') {
    try {
        // 1️⃣ Connect WITHOUT specifying database
        $dsnNoDb = DB_DRIVER . ":host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET ;
        $pdo = new PDO($dsnNoDb, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        // 2️⃣ Create database if not exists
        $pdo->exec("
            CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_general_ci
        ");

        // 3️⃣ Select database
        $pdo->exec("USE `" . DB_NAME . "`");

        // 4️⃣ Check database engine/version
        $serverVersion = strtolower($pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
        $collationReplacement = 'utf8mb4_0900_ai_ci';

        if (strpos($serverVersion, 'mariadb') !== false || version_compare($serverVersion, '8.0.0', '<')) {
            // MariaDB or MySQL < 8 → fallback collation
            $collationReplacement = 'utf8mb4_unicode_520_ci';
        }

        // 5️⃣ Read SQL dump
        if (!file_exists(SQL_DUMP_FILE)) {
            throw new Exception("SQL dump file not found.");
        }
        $sqlDump = file_get_contents(SQL_DUMP_FILE);

        // 6️⃣ Replace MySQL 8 collation if needed
        if ($collationReplacement !== 'utf8mb4_0900_ai_ci') {
            $sqlDump = str_ireplace('utf8mb4_0900_ai_ci', $collationReplacement, $sqlDump);
        }

        // 7️⃣ Execute the dump
        $pdo->exec($sqlDump);

        // 8️⃣ Optional install lock
        file_put_contents(ROOT_DIR . '/.installed', 'installed');

        // 9️⃣ Reload application
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;

    } catch (Throwable $ex) {
        http_response_code(500);
        echo "❌ Database installation failed: " . $ex->getMessage();
        exit;
    }
}

 

        // ====================================================
        // OPTION 2: REDIRECT TO INSTALLATION
        // ====================================================
        if (DUMP_KEY === 'installation') {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $installation_dir = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/";

            if (!headers_sent()) {
                header("Location: $installation_dir");
            } else {
                echo "<script>window.location.href='$installation_dir';</script>";
            }
            exit;
        }

        // ====================================================
        // UNKNOWN dump_key
        // ====================================================
        http_response_code(500);
        echo "❌ Database missing and dump_key is invalid." . DUMP_KEY;
        exit;
    }

    // ========================================================
    // OTHER DATABASE ERRORS
    // ========================================================
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}
