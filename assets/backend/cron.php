<?php
try {
    // Force silent execution diagnostics logs trail tracking
    
    // ====================================================================
    // FIXED: ABSOLUTE PATH ENGINE INJECTION (Zero Path Translation Errors)
    // ====================================================================
    // Dynamically calculates the absolute root folder folder address (C:/laragon/www/clinic/)
    $baseProjectRootDirectory = dirname(__DIR__, 2) . '/';

    // Securely pull your connection handles using exact directory paths
    require_once   'system_dir_helper.php'; // needed 
    require_once $baseProjectRootDirectory . 'conn.php'; 
    require_once  'helper_functions.php'; 

    if (!$con) {
        throw new Exception('Database connection failed on absolute path mapping check.');
    }
    error_log("CRON RUNTIME DIAGNOSTIC TRACK - POST Data:\n" . print_r($_POST, true));  

    // Force MySQL engine compiler to accept deep matrix scans for large tables
    $con->exec('SET SQL_BIG_SELECTS=1, MAX_JOIN_SIZE=9100000000'); 

    // Initialize automated backup creation tool loops
    $backupResult = createBackup($con, $uploadDir); 

    if (isset($backupResult["status"]) && $backupResult["status"] === 'error') {
        $errorDescriptionMessage = $backupResult["message"] ?? 'Unknown compilation fault.';
        error_log(date("Y-m-d H:i:s") . " - Backup Error: " . $errorDescriptionMessage . "\n", 3, "error.log");
        exit;
    }

    // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
    $friendly_backup_msg = "The system automatically copied all the clinic books, patient folders, and money records, and locked them safely inside a backup box so the clinic files never get lost.";
    recordSystemActivityLog($con, 'Admin', 'INSERT', $friendly_backup_msg);

    echo "SUCCESS: Cron automation script executed flawlessly. System activity trail locked.";
    exit;

} catch (\Throwable $th) {
    $logMsg = " ❌ CRON BACKUP ENGINE CRITICAL ROUTE EXCEPTION: " . $th->getMessage() . " in " . $th->getFile() . " on line " . $th->getLine();
    error_log(date("Y-m-d H:i:s") . $logMsg . "\n", 3, "error.log");
    exit;
}



// @echo off
// echo =======================================================
// echo     STARTING CLINIC BACKEND AUTOMATION PROCESS...
// echo =======================================================

// :: Explicitly call the Laragon PHP engine
// "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "C:\laragon\www\clinic\assets\backend\cron.php"

// echo =======================================================
// echo     PROCESS FINISHED running.
// echo =======================================================
// echo.
// pause

// CMD to run as adminstrator
// schtasks /create /sc minute /mo 1 /tn "SedacoeClinicAutomaticBackup" /tr "C:\laragon\www\cronical.bat" /ru "SYSTEM"