<?php



function backupDatabase($file, $con) {
    $dump = "-- MySQL Dump Clone\n";
    $dump .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    try {
        /* ============================
         * 0. DATABASE CREATION
         * ============================ */
        $dbRes = $con->query("SELECT DATABASE() AS dbname");
        $dbName = $dbRes->fetch(PDO::FETCH_ASSOC)['dbname'];

        $dbInfo = $con->query("
            SELECT DEFAULT_CHARACTER_SET_NAME AS charset,
           DEFAULT_COLLATION_NAME AS `collation`
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = '$dbName'
        ")->fetch(PDO::FETCH_ASSOC);

  

              $dump .= "CREATE DATABASE IF NOT EXISTS `$dbName` "
      . "CHARACTER SET " . $dbInfo['charset']
      . " COLLATE " . $dbInfo['collation'] . ";\n";


        $dump .= "USE `$dbName`;\n\n";
        

        /* ============================
         * 1. TABLES + DATA
         * ============================ */
        $tables = [];
        $result = $con->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $dump .= "DROP TABLE IF EXISTS `$table`;\n";

            // Get CREATE TABLE
            $row2 = $con->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $createTable = $row2['Create Table'];

            // Preserve AUTO_INCREMENT
            $aiRes = $con->query("
                SELECT AUTO_INCREMENT
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '$table'
            ")->fetch(PDO::FETCH_ASSOC);
            if (!empty($aiRes['AUTO_INCREMENT'])) {
                $createTable .= " AUTO_INCREMENT=" . $aiRes['AUTO_INCREMENT'];
            }

            // Fetch table comment
            $tableCommentRes = $con->query("
                SELECT TABLE_COMMENT 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = '$table'
            ")->fetch(PDO::FETCH_ASSOC);
            $tableComment = $tableCommentRes['TABLE_COMMENT'] ?? '';
            if (!empty($tableComment)) {
                $createTable .= " COMMENT=" . $con->quote($tableComment); 
            }
 

            $dump .= $createTable . ";\n\n";

            // Dump data
            $result2 = $con->query("SELECT * FROM `$table`");
            $rows = $result2->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                foreach ($rows as $row3) {
                    $values = [];
                    foreach ($row3 as $val) { 
                        // If $val is null, it returns "NULL".  
                        $values[] = ($val === null) ? "NULL" : $con->quote($val);
                    }
                    $dump .= "INSERT INTO `$table` VALUES(" . implode(",", $values) . ");\n";
                }
                $dump .= "\n";
            }
        }

        /* ============================
         * 2. VIEWS
         * ============================ */
        $views = [];
        $res = $con->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        while ($row = $res->fetch(PDO::FETCH_NUM)) {
            $views[] = $row[0];
        }
        foreach ($views as $view) {
            $row = $con->query("SHOW CREATE VIEW `$view`")->fetch(PDO::FETCH_ASSOC);
            $dump .= "DROP VIEW IF EXISTS `$view`;\n";
            $dump .= $row['Create View'] . ";\n\n";
        }

        /* ============================
         * 3. TRIGGERS
         * ============================ */
        $triggers = $con->query("SHOW TRIGGERS");
        while ($trig = $triggers->fetch(PDO::FETCH_ASSOC)) {
            $triggerName = $trig['Trigger'];
            $row = $con->query("SHOW CREATE TRIGGER `$triggerName`")->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['SQL Original Statement'])) {
                $dump .= "DROP TRIGGER IF EXISTS `$triggerName`;\n";
                $dump .= "DELIMITER ;;\n";
                $dump .= $row['SQL Original Statement'] . ";;\n";
                $dump .= "DELIMITER ;\n\n";
            }
        }

        /* ============================
         * 4. PROCEDURES & FUNCTIONS
         * ============================ */
        $routines = $con->query("SELECT ROUTINE_TYPE, ROUTINE_NAME 
                                 FROM INFORMATION_SCHEMA.ROUTINES 
                                 WHERE ROUTINE_SCHEMA = DATABASE()");
        while ($routine = $routines->fetch(PDO::FETCH_ASSOC)) {
            $type = $routine['ROUTINE_TYPE'];
            $name = $routine['ROUTINE_NAME'];
            $row = $con->query("SHOW CREATE $type `$name`")->fetch(PDO::FETCH_ASSOC);
            $stmt = $row["Create $type"];
            $dump .= "DROP $type IF EXISTS `$name`;\n";
            $dump .= "DELIMITER ;;\n";
            $dump .= $stmt . ";;\n";
            $dump .= "DELIMITER ;\n\n";
        }

        /* ============================
         * 5. EVENTS
         * ============================ */
        $events = $con->query("SHOW EVENTS WHERE Db = DATABASE()");
        while ($ev = $events->fetch(PDO::FETCH_ASSOC)) {
            $eventName = $ev['Name'];
            $row = $con->query("SHOW CREATE EVENT `$eventName`")->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['Create Event'])) {
                $dump .= "DROP EVENT IF EXISTS `$eventName`;\n";
                $dump .= "DELIMITER ;;\n";
                $dump .= $row['Create Event'] . ";;\n";
                $dump .= "DELIMITER ;\n\n";
            }
        }
 

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $file_content = file_put_contents($file, $dump);
        return [
                'status' => 'success',
                'file'   => $file_content,
                'message'   => 'Successfully created'

            ];

    } catch (Throwable $e) {
        $dump = date("Y-m-d H:i:s") . " - Backup Error in backupDatabase: " . $e->getMessage();
        $file_content = file_put_contents($file, $dump);
        return [
                'status' => 'error',
                'file'   => $file_content,
                'message'   =>  $e->getMessage()
            ];

        error_log(date("Y-m-d H:i:s") . " - Backup Error in backupDatabase: " . $e->getMessage() . "\n", 3, "error.log");
    }

}


function createBackup($con, $uploadDir) {
    try {
        // $backupResult = createBackup($con, $uploadDir); // Use default directory, or
        $backupDirectory = $uploadDir.'backup/'; 

        // Create base backup directory if missing
        if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0777, true)) {
            throw new Exception("Could not create backup directory: " . $backupDirectory);
        }
        if (!is_writable($backupDirectory)) {
            throw new Exception("Backup directory is not writable: " . $backupDirectory);
        }

        // === Clean up old backups (older than 365 days) ===
        $now = time();
        $daysToKeep = 365;
        foreach (glob($backupDirectory . "*", GLOB_ONLYDIR) as $dir) {
            $folderDate = basename($dir); // e.g., "2025-09-18"
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $folderDate)) {
                $folderTime = strtotime($folderDate);
                if ($folderTime !== false && ($now - $folderTime) > ($daysToKeep * 86400)) {
                    // Delete old folder and contents
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $file) {
                        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                    }
                    rmdir($dir);
                }
            }
        }

        // Create date-based subdirectory (e.g., backup/2025-09-18/)
        $dateDir = $backupDirectory . date('Y-m-d') . '/';
        if (!is_dir($dateDir) && !mkdir($dateDir, 0755, true)) {
            throw new Exception("Could not create date directory: " . $dateDir);
        }

        // Generate timestamped filenames
        $timestamp = date('H-i-s'); // only time since date is already in folder
        $sqlFile = $dateDir . 'backup_' . $timestamp . '.sql';
        $zipFile = $dateDir . 'backup_' . $timestamp . '.zip';

        // Step 1: Write SQL dump
        $backup = backupDatabase($sqlFile, $con);

        if ($backup["status"] == 'error') {
            throw new Exception("Error creating SQL dump. Error message: " . $backup["message"]);
        }

        // Step 2: Compress with password
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            throw new Exception("Could not create ZIP archive.");
        }

        $zip->addFile($sqlFile, basename($sqlFile));
        $zip->setEncryptionName(basename($sqlFile), ZipArchive::EM_AES_256, "Nana Yaw");
        $zip->close();

        // Delete plain SQL file (leave only encrypted zip)
        unlink($sqlFile);

        return ["status" => 'success', "message" => "Backup created successfully: " . $zipFile];

    } catch (Throwable $e) {
        error_log(date("Y-m-d H:i:s") . " - Backup Error: " . $e->getMessage() . "\n", 3, "error.log");
        return ["status" => 'error', "message" => $e->getMessage()];
    }
}
 

function get_system_info(): array
{
    try {

        if (session_status() === PHP_SESSION_NONE) session_start();

        $infoFile = '../assets/backend/software_info.json';

        if (!file_exists($infoFile)) {
            throw new RuntimeException('software_info.json not found');
        }

        $content = file_get_contents($infoFile);

        if ($content === false) {
            throw new RuntimeException('Failed to read software_info.json');
        }

        $info = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }

        $school_id = $_POST['school_id'] ?? ($_SESSION['school_id'] ?? 'no_id');

        $software_name = strtolower($info['software_name'] ?? 'application');
        $phinnatek_favicon = $info['software_favicon'] ?? '';
        $phinnatek_logo = $info['software_logo'] ?? '';

        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $serverPort = $_SERVER['SERVER_PORT'] ?? '';

        $exeoutput = $serverName === 'heserver' && $httpHost === 'heserver' && (string)$serverPort === '443';
        // $exeoutput = $serverName !== 'heserver' || $httpHost === 'heserver' && (string)$serverPort === '443';
        $data = [
                    'status' => 'success',
                    'school_id' => $school_id,
                    'software_name' => $software_name,
                    'software_favicon' => $phinnatek_favicon,
                    'software_logo' => $phinnatek_logo,
                    'exeoutput' => $exeoutput,
                    'raw_data' => $info,
                ];
        // error_log('GET_SYSTEM_INFO data: ' . print_r($data, true));

        return $data;

    } catch (Throwable $e) {

        error_log('GET_SYSTEM_INFO ERROR: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => $e->getMessage(),
        ];
    }
}

 

function get_system_file($dbPath)
{
    try { 

        $default_avatar = '../assets/img/avatars/avatar.png';

        if (empty($dbPath)) return $default_avatar;

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['file_exists_cache'])) {
            $_SESSION['file_exists_cache'] = [];
        }

        $cleanPath = ltrim($dbPath, './');

        $system = get_system_info();

        if (($system['status'] ?? 'error') !== 'success') return $default_avatar;

        $exeoutput = $system['exeoutput'];
        $software_name = $system['software_name'];
        $school_id = $system['school_id'];

        $file_path = '';
        $check_path = '';

        if ($exeoutput) {

            $appData = getenv('APPDATA') ?: sys_get_temp_dir();

            // Remove leading "server_files/<school_id>/" if already present
            $cleanPath = preg_replace('#^server_files/' . preg_quote((string)$school_id, '#') . '/#', '', $cleanPath);
            $cleanPath = preg_replace('#^server_files/#', '', $cleanPath);

            // Real filesystem path
            $check_path = $appData . DIRECTORY_SEPARATOR . $software_name . DIRECTORY_SEPARATOR . 'server_files' . DIRECTORY_SEPARATOR . $school_id . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);

            // Browser path
            $file_path = 'file:///' . str_replace('\\', '/', $check_path);

            error_log('EXE CHECK PATH: ' . $check_path);
            error_log('EXE SRC PATH: ' . $file_path);

        } else {

            if (strpos($cleanPath, 'assets/') === 0) {
                $file_path = '../' . $cleanPath;
            } else {
                $file_path = SYSTEM_MEDIA_BASE . $cleanPath;
            }

            $check_path = $file_path;
        }

        if (isset($_SESSION['file_exists_cache'][$check_path])) {
            return $_SESSION['file_exists_cache'][$check_path] ? $file_path : $default_avatar;
        }

        if (!$exeoutput && filter_var($file_path, FILTER_VALIDATE_URL)) {

            $headers = @get_headers($file_path);
            $isValid = $headers && strpos($headers[0], '200') !== false;

        } else {

            $isValid = file_exists($check_path);
        }

        $_SESSION['file_exists_cache'][$check_path] = $isValid;

        return $isValid ? $file_path : $default_avatar;

    } catch (Throwable $e) {

        error_log('GET_SYSTEM_FILE ERROR: ' . $e->getMessage());

        return $default_avatar;
    }
}

 

function decodeLogin($encoded) {
    $login_encript = 'mySuperSecretKey123!';
    if (empty($encoded)) return false;
    $data = base64_decode((string)$encoded, true);
    if ($data === false) return false;
    $key = hash('sha256', $login_encript, true);
    $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    if (strlen($data) <= $ivLength) return false;
    $iv = substr($data, 0, $ivLength);
    $ciphertext = substr($data, $ivLength);
    $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    if ($plaintext === false) return false;
    $maybeJson = json_decode($plaintext, true); 
// Debug log
if ($maybeJson === null) {
    // error_log("Decrypted raw plaintext:\n" . $plaintext);
    // error_log("JSON decode error: " . json_last_error_msg());
} else {
    // error_log("Decrypted data (array):\n" . print_r($maybeJson, true));
}

    return (json_last_error() === JSON_ERROR_NONE) ? $maybeJson : $plaintext;
}
 

// HELPER: FETCH STAFF DIRECTORY COMPLIANT WITH STRICT FULL GROUP MODE BOUNDARIES
function fetchClinicStaffDirectory(PDO $con): array {
    try {
        $query = "
            SELECT id, staff_id, full_name, role, status,
                   DATE_FORMAT(created_at, '%d-%b-%Y') as date_created
            FROM users
            GROUP BY id, staff_id, full_name, role, status, created_at
            ORDER BY full_name ASC
        ";
        $stmt = $con->prepare($query);
        $stmt->execute();
        return ['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (\Throwable $ex) {
        return ['status' => 'error', 'data' => [], 'message' => $ex->getMessage()];
    }
}
// HELPER: CALCULATE HEALTHY METRICS RUNWAYS COMPLIANT WITH FULL GROUP RULES
function fetchFinancialLedgerSummary(PDO $con): array {
    try {
        // 1. Calculate aggregated financial metrics boxes
        $statsStmt = $con->query("
            SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'Inflow' THEN amount ELSE 0 END), 0.00) as total_inflow,
                COALESCE(SUM(CASE WHEN transaction_type = 'Outflow' THEN amount ELSE 0 END), 0.00) as total_outflow
            FROM cash_flow_ledger
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        $net_balance = (float)$stats['total_inflow'] - (float)$stats['total_outflow'];

        // 2. Fetch recent ledger log tracks explicitly grouped to prevent strict error mode crashes
        $logsQuery = "
            SELECT 
                l.id, l.transaction_type, l.category, l.amount, l.description,
                DATE_FORMAT(l.created_at, '%d-%b-%Y %h:%i %p') as date_logged,
                u.full_name as cashier_name
            FROM cash_flow_ledger l
            JOIN users u ON l.recorded_by = u.id
            GROUP BY l.id, l.transaction_type, l.category, l.amount, l.description, l.created_at, u.full_name
            ORDER BY l.id DESC LIMIT 100
        ";
        $logsStmt = $con->prepare($logsQuery);
        $logsStmt->execute();

        return [
            'status' => 'success',
            'inflow' => (float)$stats['total_inflow'],
            'outflow' => (float)$stats['total_outflow'],
            'net_balance' => $net_balance,
            'logs' => $logsStmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (\Throwable $ex) {
        return ['status' => 'error', 'logs' => [], 'message' => $ex->getMessage()];
    }
}
/**
 * Automatically sweeps completed patient collections directly into the master cash flow ledger.
 * Prevents non-aggregate group conflicts and maintains precise audit trail reference rows.
 */
function logPatientPaymentToCashFlowLedger(PDO $con, array $selected_bill_ids, int $consultation_id, int $cashier_id): array {
    try {
        if (empty($selected_bill_ids)) {
            return ['status' => 'skipped', 'message' => 'No invoices passed.'];
        }

        // 1. Calculate the exact total sum of the itemized invoices currently being paid
        $placeholders = implode(',', array_fill(0, count($selected_bill_ids), '?'));
        $sumQuery = "SELECT COALESCE(SUM(amount), 0.00) FROM billing WHERE id IN ($placeholders) AND payment_status = 'Unpaid'";
        $sumStmt = $con->prepare($sumQuery);
        $sumStmt->execute(array_map('intval', $selected_bill_ids));
        $totalCashCollected = (float)$sumStmt->fetchColumn();

        // 2. If money was collected, execute a clean inflow ledger entry mutation
        if ($totalCashCollected > 0) {
            $ledgerQuery = "
                INSERT INTO cash_flow_ledger (
                    transaction_type, category, reference_id, amount, description, recorded_by
                ) VALUES (
                    'Inflow', 'Drugs', ?, ?, ?, ?
                )";
            
            $ledgerStmt = $con->prepare($ledgerQuery);
            $ledgerStmt->execute([
                (int)$selected_bill_ids[0], // Binds the first invoice ID as a structural tracking reference
                $totalCashCollected,
                "Patient medical settlement clearance under Consultation Visit Ref ID: " . $consultation_id,
                $cashier_id
            ]);

            return [
                'status'  => 'success', 
                'amount'  => $totalCashCollected, 
                'message' => "Successfully swept GHS " . number_format($totalCashCollected, 2) . " into cash flow ledger charts."
            ];
        }

        return ['status' => 'skipped', 'message' => 'No pending outstanding balances found matching input arrays.'];

    } catch (\Throwable $caughtException) {
        error_log("logPatientPaymentToCashFlowLedger pipeline drop failure: " . $caughtException->getMessage());
        return ['status' => 'error', 'message' => $caughtException->getMessage()];
    }
}

/**
 * Full-Group compliant helper function to fetch warehouse storage listings
 */
function fetchMainStoreWarehouseInventory(PDO $con): array {
    try {
        $stockQuery = "
            SELECT 
                id, drug_name, quantity_in_warehouse, batch_number,
                DATE_FORMAT(updated_at, '%d-%b-%Y %h:%i %p') as formatted_date
            FROM main_medical_store
            -- FIXED: Explicitly list grouping boundaries to satisfy strict ONLY_FULL_GROUP_BY modes
            GROUP BY id, drug_name, quantity_in_warehouse, batch_number, updated_at
            ORDER BY drug_name ASC
        ";
        $stockStmt = $con->prepare($stockQuery);
        $stockStmt->execute();
        return ['status' => 'success', 'data' => $stockStmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (\Throwable $ex) {
        error_log("Main store inventory master fetch crash: " . $ex->getMessage());
        return ['status' => 'error', 'data' => [], 'message' => 'Warehouse storage lookups crashed: ' . $ex->getMessage()];
    }
}
/**
 * Full-Group compliant helper function to fetch shelf catalogs and sent restock vouchers.
 * Explicitly structures grouped select targets to satisfy strict SQL mode rules.
 */
function fetchPharmacyRequisitionDataPack(PDO $con): array {
    try {
        // 1. Fetch current shelf inventory items to fill the drop-down selector
        $stockQuery = "
            SELECT id, drug_name, quantity_in_store, min_threshold_qty 
            FROM pharmacy_store 
            ORDER BY drug_name ASC
        ";
        $stockStmt = $con->prepare($stockQuery);
        $stockStmt->execute();
        $inventory = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch the chronological history trail of sent restock vouchers
        $reqQuery = "
            SELECT 
                r.id as requisition_id, 
                r.requested_qty, 
                r.status, 
                DATE_FORMAT(r.created_at, '%d-%b-%Y %h:%i %p') as date_sent,
                ps.drug_name, 
                u.full_name as staff_name
            FROM pharmacy_requisitions r
            JOIN pharmacy_store ps ON r.drug_id = ps.id
            JOIN users u ON r.requested_by = u.id
            -- FIXED: Added strict full aggregation parameters for strict GROUP BY compatibility
            GROUP BY 
                r.id, 
                r.requested_qty, 
                r.status, 
                r.created_at, 
                ps.drug_name, 
                u.full_name
            ORDER BY r.id DESC
            LIMIT 50
        ";
        $reqStmt = $con->prepare($reqQuery);
        $reqStmt->execute();
        $requisitions = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success', 
            'inventory' => $inventory, 
            'requisitions' => $requisitions
        ];
    } catch (\Throwable $ex) {
        error_log("Dedicated pharmacy requisition backend fetch crash: " . $ex->getMessage());
        return [
            'status' => 'error', 
            'inventory' => [], 
            'requisitions' => [], 
            'message' => 'Procurement ledger processing anomaly: ' . $ex->getMessage()
        ];
    }
}

/**
 * Full-Group compliant function to aggregate pending restock vouchers
 */
function getMainStoreRequisitionsQueue(PDO $con): array {
    try {
        // 1. Fetch total distinct items managed inside warehouse
        $countStmt = $con->query("SELECT COUNT(*) FROM main_medical_store");
        $totalItemsCount = (int)$countStmt->fetchColumn();

        // 2. Fetch all un-fulfilled sub-depot vouchers safely grouped
        $fetchQuery = "
            SELECT 
                r.id as requisition_id, 
                r.drug_id as pharmacy_drug_id, 
                r.requested_qty, 
                r.status,
                DATE_FORMAT(r.created_at, '%d-%b-%Y %h:%i %p') as date_requested,
                ps.drug_name, 
                u.full_name as staff_name,
                COALESCE(mms.quantity_in_warehouse, 0) as main_warehouse_qty, 
                COALESCE(mms.id, 0) as warehouse_drug_id
            FROM pharmacy_requisitions r
            JOIN pharmacy_store ps ON r.drug_id = ps.id
            JOIN users u ON r.requested_by = u.id
            LEFT JOIN main_medical_store mms ON ps.drug_name = mms.drug_name
            WHERE r.status = 'Pending'
            GROUP BY 
                r.id, 
                r.drug_id, 
                r.requested_qty, 
                r.status, 
                r.created_at, 
                ps.drug_name, 
                u.full_name, 
                mms.quantity_in_warehouse, 
                mms.id
            ORDER BY r.id ASC
        ";
        $stmt = $con->prepare($fetchQuery);
        $stmt->execute();
        
        return [
            'status' => 'success', 
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total_warehouse_items' => $totalItemsCount
        ];
    } catch (\Throwable $ex) {
        error_log("Main store queue fetch failure exception: " . $ex->getMessage());
        return ['status' => 'error', 'data' => [], 'total_warehouse_items' => 0, 'message' => $ex->getMessage()];
    }
}

 
function fetchPharmacyInventoryDataPack(PDO $con): array {
    try {
        $stockQuery = "
            SELECT 
                id, drug_name, selling_price, quantity_in_store, min_threshold_qty,
                DATE_FORMAT(expiry_date, '%d-%b-%Y') as formatted_expiry,
                CASE 
                    WHEN expiry_date <= CURRENT_DATE THEN 'Expired' 
                    WHEN quantity_in_store <= min_threshold_qty THEN 'Low Stock' 
                    ELSE 'Good' 
                END as stock_health_status
            FROM pharmacy_store ORDER BY drug_name ASC
        ";
        $stockStmt = $con->prepare($stockQuery);
        $stockStmt->execute();
        return ['status' => 'success', 'inventory' => $stockStmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (\Throwable $ex) {
        return ['status' => 'error', 'inventory' => [], 'message' => $ex->getMessage()];
    }
}

// ====================================================================
// UNIFIED MASTER CONTROLLER ROUTER: INPATIENT WARD ADMISSIONS SUITE
// ====================================================================

// HELPER A: AGGREGATE WAITING AND ACTIVE WARD PATIENTS LIST QUEUE
function getWardAdmissionsQueue(PDO $con): array {
    try {
        // Query 1: Fetch all inpatient cases requested today or actively admitted
        $queueQuery = "
            SELECT 
                p.id as patient_id, p.name as patient_name, p.folder_number, p.insurance_type,
                p.gender, FLOOR(DATEDIFF(CURRENT_DATE, p.date_of_birth)/365.25) as calculated_age,
                c.id as consultation_id, c.diagnosis,
                wa.id as admission_id, wa.admission_status, wa.nursing_notes,
                wb.bed_number, wc.ward_name,
                DATE_FORMAT(wa.admitted_at, '%d-%b-%Y %h:%i %p') as date_logged
            FROM consultations c
            JOIN patients p ON c.patient_id = p.id
            -- Intercepts any case marked 'Inpatient' by the doctor
            LEFT JOIN ward_admissions wa ON wa.consultation_id = c.id
            LEFT JOIN ward_beds wb ON wa.bed_id = wb.id
            LEFT JOIN ward_catalog wc ON wb.ward_id = wc.id
            WHERE c.disposition = 'Inpatient' AND (wa.admission_status IS NULL OR wa.admission_status != 'Discharged')
            GROUP BY 
                c.id, p.id, p.name, p.folder_number, p.insurance_type, p.gender, 
                p.date_of_birth, c.diagnosis, wa.id, wa.admission_status, 
                wa.nursing_notes, wb.bed_number, wc.ward_name, wa.admitted_at
            ORDER BY wa.id ASC, c.id ASC
        ";
        $stmt = $con->prepare($queueQuery);
        $stmt->execute();
        $queueDataset = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Auto-initialize internal registration tracks for rogue rows not yet written to the sub-table
        foreach ($queueDataset as &$row) {
            if (!$row['admission_id']) {
                // Auto-create missing awaiting bed child records silently to shield operational workflows
                $ins = $con->prepare("INSERT INTO ward_admissions (patient_id, consultation_id, admission_status) VALUES (?, ?, 'Awaiting Bed')");
                $ins->execute([$row['patient_id'], $row['consultation_id']]);
                $row['admission_id'] = $con->lastInsertId();
                $row['admission_status'] = 'Awaiting Bed';
                $row['date_logged'] = date('d-M-Y h:i A');
            }
        }

        // Query 2: Fetch an active inventory list of all empty beds across facility mapping channels
        $bedsQuery = "
            SELECT wb.id as bed_id, wb.bed_number, wc.ward_name, wc.ward_type 
            FROM ward_beds wb
            JOIN ward_catalog wc ON wb.ward_id = wc.id
            WHERE wb.bed_status = 'Available'
            ORDER BY wc.ward_name ASC, wb.bed_number ASC
        ";
        $bedsStmt = $con->prepare($bedsQuery);
        $bedsStmt->execute();
        $availableBedsList = $bedsStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success', 
            'queue' => $queueDataset,
            'beds' => $availableBedsList
        ];
    } catch (\Throwable $ex) {
        return ['status' => 'error', 'queue' => [], 'beds' => [], 'message' => $ex->getMessage()];
    }
}
function getAllPatients(PDO $dbConnection): array {
    try {
        // This query groups all historical vitals into a JSON block for each patient row
        $fetchQuery = "
            SELECT 
                p.*, 
                FLOOR(DATEDIFF(CURRENT_DATE, p.date_of_birth)/365.25) as calculated_age,
                (
                    SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', v.id,
                            'bp', v.bp,
                            'pulse', v.pulse,
                            'temperature', v.temperature,
                            'weight', v.weight,
                            'spo2', v.spo2,
                            'created_at', v.created_at
                        )
                    )
                    FROM opd_vitals v 
                    WHERE v.patient_id = p.id
                ) as vitals_history
            FROM patients p
            ORDER BY p.id DESC
        ";
        
        $stmt = $dbConnection->query($fetchQuery);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode the json column for each patient row so PHP outputs standard object properties
        foreach ($records as &$row) {
            $row['vitals_history'] = $row['vitals_history'] ? json_decode($row['vitals_history'], true) : [];
        }

        return [
            'status'  => 'success',
            'data'    => $records,
            'message' => 'Patient profiles and full history loaded.'
        ];

    } catch (\Throwable $caughtException) {
        error_log("getAllPatients core query engine crash: " . $caughtException->getMessage());
        return [
            'status'  => 'error',
            'data'    => [],
            'message' => 'An anomaly occurred: ' . $caughtException->getMessage()
        ];
    }
}
 

// 1. SELECT PATIENTS THAT ARE WAITING FOR THE DOCTOR WITH DEEP PRE-FILL ARRAYS
function getWaitingQueue(PDO $dbConnection): array {
    try {
        $currentDate = date('Y-m-d');
        $fetchQuery = "
            SELECT 
                p.*, 
                FLOOR(DATEDIFF(CURRENT_DATE, p.date_of_birth)/365.25) as calculated_age,
                v.id as vital_id, v.bp, v.pulse, v.temperature, v.weight, v.spo2, v.doctor_status, v.created_at as vital_date,
                
                -- PULLS TODAY'S UNFINISHED PARTIAL CONSULTATION TRACKERS
                c_today.id as today_consultation_id,
                c_today.complaints as today_complaints,
                c_today.diagnosis as today_diagnosis,
                c_today.disposition as today_admission_status,
                
                -- NEW FINTECH UPGRADE: FETCHES ALL LABS SENT TODAY IN A CLEAN ARRAY
                (
                    SELECT JSON_ARRAYAGG(lr_t.lab_catalog_id) 
                    FROM lab_requests lr_t 
                    WHERE lr_t.consultation_id = c_today.id
                ) as today_lab_ids,
                
                -- NEW FINTECH UPGRADE: FETCHES ALL MEDICINES AND INJECTIONS PRE-SAVED TODAY
                (
                    SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'drug_id', pr_t.drug_id,
                            'drug_name', ps_t.drug_name,
                            'dosage', pr_t.dosage_instruction,             
                            'dosage_instruction', pr_t.dosage_instruction   
                        )
                    )
                    FROM prescriptions pr_t
                    JOIN pharmacy_store ps_t ON pr_t.drug_id = ps_t.id
                    WHERE pr_t.consultation_id = c_today.id
                ) as today_prescriptions,

                -- PULLS ALL PAST VISITS PACKED WITH THEIR MATCHING LABS AND DRUGS
                (
                    SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', c.id,
                            'complaints', c.complaints,
                            'diagnosis', c.diagnosis,
                            'created_at', c.created_at,
                            'date', DATE(c.created_at),
                            
                            'labs', (
                                SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        'id', lr.id,
                                        'test_name', lc.test_name,
                                        'status', lr.status,
                                        'lab_result', lr.lab_result,
                                        'attached_file', lr.attached_file
                                    )
                                ) 
                                FROM lab_requests lr
                                JOIN lab_catalog lc ON lr.lab_catalog_id = lc.id
                                WHERE lr.consultation_id = c.id
                            ),
                            
                            'drugs', (
                                SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        'id', pr.id,
                                        'drug_name', ps.drug_name,
                                        'dosage', pr.dosage_instruction,
                                        'status', pr.status
                                    )
                                ) 
                                FROM prescriptions pr
                                JOIN pharmacy_store ps ON pr.drug_id = ps.id
                                WHERE pr.consultation_id = c.id
                            )
                        )
                    ) 
                    FROM consultations c 
                    WHERE c.patient_id = p.id
                ) as medical_history,
                
                -- PULLS FULL CHRONOLOGICAL LIST OF ALL PAST TRIAGE PARAMETERS
                (
                    SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', vt.id, 
                            'bp', vt.bp, 
                            'pulse', vt.pulse, 
                            'temperature', vt.temperature, 
                            'weight', vt.weight, 
                            'spo2', vt.spo2, 
                            'created_at', vt.created_at
                        )
                    ) FROM opd_vitals vt WHERE vt.patient_id = p.id
                ) as vitals_history

            FROM patients p
            INNER JOIN opd_vitals v ON v.patient_id = p.id
            LEFT JOIN consultations c_today ON c_today.vitals_id = v.id
            WHERE DATE(v.created_at) = :today AND v.doctor_status = 'Pending'
            ORDER BY v.id ASC
        ";
        
        $stmt = $dbConnection->prepare($fetchQuery);
        $stmt->execute([':today' => $currentDate]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode nested string properties safely so JavaScript can loop through them instantly
        foreach ($records as &$row) {
            $row['today_lab_ids']       = $row['today_lab_ids'] ? json_decode($row['today_lab_ids'], true) : [];
            $row['today_prescriptions'] = $row['today_prescriptions'] ? json_decode($row['today_prescriptions'], true) : [];
            $row['medical_history']     = $row['medical_history'] ? json_decode($row['medical_history'], true) : [];
            $row['vitals_history']      = $row['vitals_history'] ? json_decode($row['vitals_history'], true) : [];
        }

        return [
            'status' => 'success', 
            'data'   => $records
        ];

    } catch (\Throwable $ex) {
        error_log("Queue Engine Crash Anomaly: " . $ex->getMessage());
        return [
            'status'  => 'error', 
            'data'    => [], 
            'message' => 'An entry reading error occurred inside consultation logs: ' . $ex->getMessage()
        ];
    }
}


// A. REUSABLE FUNCTION: FETCH ALL ACTIVE UNPAID BILLING RECORDS WITH INVOICE ACCOUNTS
function getPendingCashierQueue(PDO $con): array {
    try {
        $fetchQuery = "
            SELECT 
                p.id as patient_id, 
                p.name as patient_name, 
                p.folder_number, 
                p.insurance_type,
                b.consultation_id,
                COUNT(b.id) as total_unpaid_items,
                COALESCE(SUM(b.amount), 0.00) as total_outstanding_debt,
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'bill_id', b.id,
                        'item_type', b.item_type,
                        'item_reference_id', b.item_reference_id,
                        'amount', b.amount,
                        'created_at', DATE_FORMAT(b.created_at, '%d-%b-%Y %h:%i %p'),
                        -- SUBQUERY LOOKUP: Dynamically pull exact item descriptive labels
                        'item_description', CASE 
                            WHEN b.item_type = 'Consultation' THEN 'Doctor Consultation Review Fee'
                            WHEN b.item_type = 'Lab Fee' THEN (
                                SELECT lc.test_name FROM lab_requests lr 
                                JOIN lab_catalog lc ON lr.lab_catalog_id = lc.id 
                                WHERE lr.id = b.item_reference_id LIMIT 1
                            )
                            WHEN b.item_type = 'Drugs' THEN (
                                SELECT ps.drug_name FROM prescriptions pr 
                                JOIN pharmacy_store ps ON pr.drug_id = ps.id 
                                WHERE pr.id = b.item_reference_id LIMIT 1
                            )
                            ELSE 'General Facility Charges'
                        END
                    )
                ) as itemized_bill_list
            FROM billing b
            JOIN patients p ON b.patient_id = p.id
            WHERE b.payment_status = 'Unpaid'
            -- FIXED: Added all unaggregated select columns to fulfill ONLY_FULL_GROUP_BY criteria
            GROUP BY 
                b.consultation_id, 
                p.id, 
                p.name, 
                p.folder_number, 
                p.insurance_type
            -- FIXED: Ordered by the group target column to satisfy strict indexing constraints
            ORDER BY b.consultation_id ASC
        ";
        $stmt = $con->prepare($fetchQuery);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as &$row) {
            $row['itemized_bill_list'] = json_decode($row['itemized_bill_list'], true) ?: [];
            $row['total_unpaid_items'] = (int)$row['total_unpaid_items'];
            $row['total_outstanding_debt'] = (float)$row['total_outstanding_debt'];
        }
        return ['status' => 'success', 'data' => $records];
    } catch (\Throwable $ex) {
        error_log("Cashier Ledger Full Group Fetch Crash: " . $ex->getMessage());
        return ['status' => 'error', 'data' => [], 'message' => $ex->getMessage()];
    }
}

// ====================================================================
// UNIFIED MASTER CONTROLLER ROUTER: NURSES INJECTION BAY BOARD
// ====================================================================

// A. REUSABLE FUNCTION: FETCH ACTIVE NURSING BAY QUEUE
function getPendingInjectionsQueue(PDO $con): array {
    try {
        $fetchQuery = "
            SELECT 
                p.id as patient_id, p.name as patient_name, p.folder_number, p.insurance_type,
                c.id as consultation_id, u.full_name as prescribing_doctor,
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'prescription_id', pr.id,
                        'drug_name', ps.drug_name,
                        'dosage', pr.dosage_instruction,
                        'created_at', pr.created_at
                    )
                ) as injections_list
            FROM prescriptions pr
            JOIN consultations c ON pr.consultation_id = c.id
            JOIN patients p ON pr.patient_id = p.id
            JOIN users u ON c.doctor_id = u.id
            JOIN pharmacy_store ps ON pr.drug_id = ps.id
            WHERE pr.status = 'Pending' 
              AND pr.dosage_instruction LIKE '[INJECTION]%'
            GROUP BY c.id, p.id, p.name, p.folder_number, p.insurance_type, u.full_name
            ORDER BY c.id ASC
        ";
        $stmt = $con->prepare($fetchQuery);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as &$row) {
            $row['injections_list'] = json_decode($row['injections_list'], true) ?: [];
        }
        return ['status' => 'success', 'data' => $records];
    } catch (\Throwable $ex) {
        return ['status' => 'error', 'data' => [], 'message' => $ex->getMessage()];
    }
}
function getPendingPharmacyQueue(PDO $con): array {
    try {
        $fetchQuery = "
            SELECT 
                p.id as patient_id, 
                p.name as patient_name, 
                p.folder_number, 
                p.insurance_type,
                c.id as consultation_id, 
                c.diagnosis,
                u.full_name as prescribing_doctor,
                
                -- COMPACT CHECK 1: COUNT PENDING INJECTIONS FOR THIS MASTER CONSULTATION ID
                (
                    SELECT COUNT(*) 
                    FROM prescriptions pr_inj 
                    WHERE pr_inj.consultation_id = c.id 
                      AND pr_inj.status = 'Pending' 
                      AND pr_inj.dosage_instruction LIKE '[INJECTION]%'
                ) as pending_injections_count,

                -- COMPACT CHECK 2: COUNT UNPAID BILLING ITEMS FOR THIS CONSULTATION ID
                (
                    SELECT COUNT(*) 
                    FROM billing b_check 
                    WHERE b_check.consultation_id = c.id 
                      AND b_check.payment_status = 'Unpaid'
                ) as unpaid_bills_count,

                -- COMPACT CHECK 3: CALCULATE EXACT TOTAL AMOUNT TO PAY FOR THIS CONSULTATION
                (
                    SELECT COALESCE(SUM(b_sum.amount), 0.00) 
                    FROM billing b_sum 
                    WHERE b_sum.consultation_id = c.id 
                      AND b_sum.payment_status = 'Unpaid'
                ) as total_unpaid_amount,

                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'prescription_id', pr.id,
                        'drug_id', pr.drug_id,
                        'drug_name', ps.drug_name,
                        'dosage', pr.dosage_instruction,
                        'selling_price', ps.selling_price,
                        'stock_qty', ps.quantity_in_store
                    )
                ) as medications_list
            FROM prescriptions pr
            JOIN consultations p_c ON pr.consultation_id = p_c.id
            JOIN opd_vitals v ON p_c.vitals_id = v.id
            JOIN patients p ON pr.patient_id = p.id
            JOIN consultations c ON pr.consultation_id = c.id
            JOIN users u ON c.doctor_id = u.id
            JOIN pharmacy_store ps ON pr.drug_id = ps.id
            WHERE pr.status = 'Pending' 
              AND pr.dosage_instruction NOT LIKE '[INJECTION]%'
            GROUP BY 
                c.id, 
                p.id, 
                p.name, 
                p.folder_number, 
                p.insurance_type, 
                c.diagnosis, 
                u.full_name
            ORDER BY c.id ASC
        ";
        
        $stmt = $con->prepare($fetchQuery);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as &$row) {
            $row['medications_list'] = json_decode($row['medications_list'], true) ?: [];
            // Cast numerical values explicitly
            $row['pending_injections_count'] = (int)$row['pending_injections_count'];
            $row['unpaid_bills_count'] = (int)$row['unpaid_bills_count'];
            $row['total_unpaid_amount'] = (float)$row['total_unpaid_amount'];
        }

        return ['status' => 'success', 'data' => $records];
    } catch (\Throwable $ex) {
        error_log("Pharmacy Queue Fetch Crash: " . $ex->getMessage());
        return ['status' => 'error', 'data' => [], 'message' => $ex->getMessage()];
    }
}


function addPatientBillingInvoice(PDO $con, int $patient_id, int $consultation_id, string $insurance_type, string $item_type, int $item_reference_id, float $amount): array {
    try {
        if ($insurance_type !== 'None') {
            return ['status' => 'success', 'message' => 'Skipped. Covered by insurance.'];
        }

        // Check for duplicates to prevent double-charging
        $checkStmt = $con->prepare("SELECT id FROM billing WHERE item_type = :item_type AND item_reference_id = :ref_id LIMIT 1");
        $checkStmt->execute([':item_type' => $item_type, ':ref_id' => $item_reference_id]);
        if ($checkStmt->fetch()) {
            return ['status' => 'success', 'message' => 'Invoice skipped. Duplicate prevented.'];
        }

        // INSERT WITH NEW MASTER VISIT LINK ID
        $insertQuery = "INSERT INTO billing (patient_id, consultation_id, item_type, item_reference_id, amount, payment_status) 
                        VALUES (:pid, :cid, :item_type, :ref_id, :amount, 'Unpaid')";
        $insertStmt = $con->prepare($insertQuery);
        $insertStmt->execute([
            ':pid'       => $patient_id,
            ':cid'       => $consultation_id, // Cleanly binds to the column row
            ':item_type' => $item_type,
            ':ref_id'    => $item_reference_id,
            ':amount'    => $amount
        ]);

        return ['status' => 'success', 'message' => "Itemized invoice for '{$item_type}' created successfully."];

    } catch (\Throwable $caughtError) {
        error_log("addPatientBillingInvoice engine failure: " . $caughtError->getMessage());
        return ['status' => 'error', 'message' => "Billing transaction failed: " . $caughtError->getMessage()];
    }
}


    // 1. SELECT ALL ACTIVE PENDING LAB REQUESTS SENT FROM DOCTORS
    function getPendingLabRequests(PDO $dbConnection): array {
        try {
            $fetchQuery = "
                SELECT 
                    lr.id as request_id, lr.status, lr.created_at,
                    p.id as patient_id, p.name, p.folder_number, p.gender,
                    lc.test_name, lc.cost
                FROM lab_requests lr
                JOIN patients p ON lr.patient_id = p.id
                JOIN lab_catalog lc ON lr.lab_catalog_id = lc.id
                WHERE lr.status = 'Pending'
                ORDER BY lr.id ASC
            ";
            $stmt = $dbConnection->query($fetchQuery);
            return [
                'status' => 'success',
                'data'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (\Throwable $ex) {
            error_log("Lab Queue Fetch Error: " . $ex->getMessage());
            return ['status' => 'error', 'data' => [], 'message' => $ex->getMessage()];
        }
    }
/**
 * Automatically calculates and generates a unique sequential folder number for new patients.
 * Format Output: F-YY/MM/0001 (e.g., F-26/06/0001) with fallback offset protections.
 * 
 * @param PDO $dbConnection The active database transaction connection instance
 * @return array Standardised status parameter array matrix ['status' => ..., 'data' => ..., 'message' => ...]
 */
function generateUniqueFolderNumber(PDO $dbConnection): array {
    try {
        // 1. EXTRACT COMPACT FINTECH DATE METRICS
        $shortYear  = date('y'); // Two-digit year (e.g., '26')
        $monthMark  = date('m'); // Two-digit month with leading zero (e.g., '06')
        
        // Define look-ahead search prefix criteria matching your schema standard
        $searchPrefix = "F-{$shortYear}/{$monthMark}/%";

        // 2. CALCULATE MONTHLY SEQUENTIAL INDEX INCREMENTS
        // Count how many patient files exist matching the current year and month prefix pattern
        $seqStmt = $dbConnection->prepare("SELECT COUNT(id) as total FROM patients WHERE folder_number LIKE :folder_prefix");
        $seqStmt->execute([':folder_prefix' => $searchPrefix]);
        $rowResult = $seqStmt->fetch(PDO::FETCH_ASSOC);
        $nextSequenceNumber = ((int)($rowResult['total'] ?? 0)) + 1;
        
        // 3. COMPILE UNIQUE SERIAL NUMBER
        // Format sequence into a 4-digit zero-padded fintech-grade string
        $paddedSequence = str_pad($nextSequenceNumber, 4, '0', STR_PAD_LEFT);
        $generatedFolderNumber = "F-{$shortYear}/{$monthMark}/{$paddedSequence}";

        // 4. PREVENT STRUCTURAL RACE CONDITIONS OVERLAPS
        $finalCheck = $dbConnection->prepare("SELECT id FROM patients WHERE folder_number = :folder LIMIT 1");
        $finalCheck->execute([':folder' => $generatedFolderNumber]);
        if ($finalCheck->fetch()) {
            // Concurrent Fallback: Appends micro-temporal tracking parameters on collisions
            $generatedFolderNumber .= '-' . substr(time(), -3);
        }

        return [
            'status'  => 'success',
            'data'    => $generatedFolderNumber,
            'message' => 'Unique patient folder registration tracking number generated successfully.'
        ];

    } catch (\Throwable $caughtException) {
        error_log("generateUniqueFolderNumber runtime sequence engine breakdown: " . $caughtException->getMessage());

        return [
            'status'  => 'error',
            'data'    => '',
            'message' => 'An allocation error occurred while generating a fresh file folder: ' . $caughtException->getMessage()
        ];
    }
}

/**
 * Global atomic function to log system events instantly across all clinic modules.
 */
function recordSystemActivityLog(PDO $con, string $module, string $action_type, string $description): bool {
    try {
        $user_id    = $_SESSION['user_id'] ?? 1; // Fallback to system master ID if called outside active profiles
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $insQuery = "INSERT INTO system_activity_logs (user_id, module, action_type, description, ip_address) 
                     VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($insQuery);
        return $stmt->execute([$user_id, $module, $action_type, trim($description), $ip_address]);
    } catch (\Throwable $caughtError) {
        error_log("recordSystemActivityLog execution fault anomaly: " . $caughtError->getMessage());
        return false;
    }
}


