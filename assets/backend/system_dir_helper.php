<?php
// ============================================================
// LOGGING & DIRECTORY CONFIGURATION (BANK/CREDIT UNION)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if (function_exists('get_system_info')) {
        $system = get_system_info();

        if (($system['status'] ?? 'error') !== 'success') {
            throw new RuntimeException($system['message'] ?? 'Failed to load core system configuration info');
        }
    } else {
                // Array of potential file path variations across different root environments
        $possibleInfoPaths = [
            'software_info.json',
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'software_info.json',
            dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'software_info.json',
            __DIR__ . DIRECTORY_SEPARATOR . 'software_info.json'
        ];

        $infoFile = null;
        foreach ($possibleInfoPaths as $path) {
            if (file_exists($path)) {
                $infoFile = $path;
                break; // Stop looking once the file is found
            }
        }

        if ($infoFile === null) {
            throw new RuntimeException('System configuration file (software_info.json) not found in any registered root directory.');
        }

        $content = file_get_contents($infoFile);
        if ($content === false) {
            throw new RuntimeException('Failed to read configuration parameters from software_info.json');
        } 

        $raw = json_decode($content, true);
        if (!is_array($raw)) {
            throw new RuntimeException('Malformed corporate data signature found in software_info.json');
        }

        // Branch-scoped domain variables (formerly school_id)
        $branch_id = $_POST['branch_id'] ?? ($_SESSION['branch_id'] ?? 'no_id');

        $system = [
            'status'           => 'success',
            'branch_id'        => $branch_id,
            'software_name'    => strtolower($raw['software_name'] ?? 'application'),
            'software_favicon' => $raw['software_favicon'] ?? '',
            'brand_name'        => $raw['brand_name'] ?? '',
            'software_logo'    => $raw['software_logo'] ?? '',
            'exeoutput'        => ($_SERVER['SERVER_NAME'] ?? '') === 'heserver' &&
                                  ($_SERVER['HTTP_HOST'] ?? '') === 'heserver' &&
                                  (string)($_SERVER['SERVER_PORT'] ?? '') === '443',
            'raw_data'         => $raw,
        ];
    }

    // Set variables globally from scoped configuration
    $software_name     = $system['software_name'];
    $brand_name     = $system['brand_name'];
    $software_favicon = $system['software_favicon'];
    $software_logo    = $system['software_logo'];
    $exeoutput         = $system['exeoutput'];

    // ============================================================
    // SECURE STORAGE INTERFACE PATHS
    // ============================================================

    if ($exeoutput) {
        $appData = getenv('APPDATA') ?: sys_get_temp_dir();
        $appRoot = $appData . DIRECTORY_SEPARATOR . $software_name;

        $uploadDir = $appRoot . DIRECTORY_SEPARATOR;
        $uploadUrl = $software_name . '/';

        $tempDir   = $uploadDir . 'temp' . DIRECTORY_SEPARATOR;
        $tempUrl   = $uploadUrl . 'temp/';

        $logDirPath = $uploadDir . 'logs' . DIRECTORY_SEPARATOR;
        $photo_update_info_value = 2;
    } else {
        $basePath = dirname(__DIR__, 3);

        $uploadDir = $basePath . DIRECTORY_SEPARATOR . 'server_files' . DIRECTORY_SEPARATOR . $software_name . DIRECTORY_SEPARATOR;
        $uploadUrl = '../../server_files/' . $software_name . '/';

        $tempDir   = $uploadDir . 'temp' . DIRECTORY_SEPARATOR;
        $tempUrl   = $uploadUrl . 'temp/';

        $logDirPath = $uploadDir . 'logs' . DIRECTORY_SEPARATOR;

        // Local environment safety evaluation
        $photo_update_info_value = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) ? 2 : 1;
    }

    // Create system directory nodes safely
    foreach ([$uploadDir, $tempDir, $logDirPath] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" could not be securely generated', $dir));
        }
    }

    // Isolate cryptographic and runtime transactional log files
    $securityLog = $logDirPath . 'security.log';
    $phpErrorLog = $logDirPath . 'php-error.log';

    foreach ([$securityLog, $phpErrorLog] as $file) {
        if (!file_exists($file)) {
            if (touch($file) === false) {
                throw new RuntimeException(sprintf('Log runtime buffer signature "%s" initialization failed', $file));
            }
            chmod($file, 0640);
        }
    }

    // Operational Environment PHP overrides
    ini_set('log_errors', '1');
    ini_set('error_log', $phpErrorLog);
    ini_set('display_errors', '0'); // Safe for financial systems production runtime
    ini_set('display_startup_errors', '0');

    date_default_timezone_set('UTC');

} catch (\Throwable $e) {
    // Determine the environment/request type to serve an appropriate error breakdown
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
              (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Internal Security Kernel Exception',
            'debug'   => $e->getMessage()
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        // Safe, sandboxed fallback view for standard browser tabs
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>System Error</title>
            <style>
                body { background: #f8f9fa; color: #333; font-family: -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                .error-card { background: #fff; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 480px; width: 100%; text-align: center; border-top: 4px solid #dc3545; }
                h1 { font-size: 1.5rem; color: #dc3545; margin-top: 0; }
                p { color: #6c757d; font-size: 0.95rem; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class="error-card">
                <h1>Configuration Exception</h1>
                <p>A critical filesystem runtime exception was caught. Please contact your system administrator or verify setup execution files.</p>
                <small style="color:#adb5bd; word-break: break-all;"><?= $infoFile. '-' . htmlspecialchars($e->getMessage()); ?></small>
            </div>
        </body>
        </html>
        <?php
    }
    exit;
}
