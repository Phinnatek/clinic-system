<?php
declare(strict_types=1); 
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '1024M'); // Matches your 1G preference in a standard unit
gc_enable(); 
$ALLOW_ORIGIN = 'https://focusmedia.me';                   // frontend origin (change for prod)
$HMAC_SECRET   = getenv('HMAC_SECRET') ?: 'your-super-secure-shared-hmac-secret';
$CSRF_TTL      = 10 * 60; // 10 minutes default token lifetime
date_default_timezone_set('UTC');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $ALLOW_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS'); 
header('Access-Control-Allow-Credentials: true'); 
header('Cache-Control: no-cache, must-revalidate');        // Optional: prevent caching  
session_set_cookie_params([
    'lifetime' => 0,        // expire on browser close
    'path'     => '/',
    // 'domain' => 'yourdomain.com', // set if needed
    'secure'   => true,     // HTTPS only
    'httponly' => true,     // not accessible to JS
    'samesite' => 'Strict'  // CSRF protection
]);
if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
header('X-CSRF-Token: ' . $_SESSION['csrf_token']);
    // http_response_code(200);
    exit;
}elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed.']);
    exit;
}

require_once '../../assets/backend/system_dir_helper.php'; // needed 
error_log("POST Data:\n" . print_r($_POST, true));  
  
require_once '../../conn.php'; // your DB connection (adjust path)
require_once '../../assets/backend/helper_functions.php'; // if needed
// require_once '../../assets/backend/cron.php'; // if needed
$con->exec('SET SQL_BIG_SELECTS=1, MAX_JOIN_SIZE=9100000000'); 
try { 
   

if (!isset($con)) {
    echo json_encode(['status' => 'error', 'message' => 'System link connection dead.']);
    exit;
}

 
if (isset($_POST['fetch_system_activity_logs']) && $_POST['fetch_system_activity_logs'] === 'true') {
    try {
        // Query logs explicitly grouped by all columns to satisfy strict group mode exceptions
        $logsQuery = "
            SELECT 
                l.id, l.module, l.action_type, l.description, l.ip_address,
                DATE_FORMAT(l.created_at, '%d-%b-%Y %h:%i %p') as formatted_date,
                u.full_name as staff_name, u.role as staff_role
            FROM system_activity_logs l
            JOIN users u ON l.user_id = u.id
            GROUP BY l.id, l.module, l.action_type, l.description, l.ip_address, l.created_at, u.full_name, u.role
            ORDER BY l.id DESC 
            LIMIT 250
        ";
        $stmt = $con->prepare($logsQuery);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'data'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        exit;

    } catch (\Throwable $ex) {
        error_log("Activity log background compilation failure: " . $ex->getMessage());
        echo json_encode(['status' => 'error', 'data' => [], 'message' => $ex->getMessage()]);
        exit;
    }
}



// ====================================================================
// CENTRAL ROUTER TERMINAL: BACKGROUND CONSULTATION WAITING QUEUE SYNC
// ====================================================================
if (isset($_POST['action_refresh_consulting_queue']) && $_POST['action_refresh_consulting_queue'] === 'true') {
    
    try {
        if (!$con) {
            throw new Exception('Database connection dropped on transit.');
        }

        // Invoke your unified central data pool array lookup function 
        $queueResponse = getWaitingQueue($con);
        
        if ($queueResponse['status'] === 'error') {
            throw new Exception($queueResponse['message']);
        }

        // Return a pristine JSON packet containing the exact data parameter rows
        echo json_encode([
            'status'  => 'success',
            'message' => 'Consultation room live waiting lists refreshed cleanly.',
            'data'    => $queueResponse['data']
        ]);
        exit;

    } catch (\Throwable $caughtException) {
        error_log("Consulting Queue Fetch Crash Anomaly: " . $caughtException->getMessage());
        echo json_encode([
            'status'  => 'error',
            'message' => 'The async consulting list pipeline failed on server: ' . $caughtException->getMessage(),
            'data'    => []
        ]);
        exit;
    }
}

if (isset($_POST['action_generate_report']) && $_POST['action_generate_report'] === 'true') {
    try {
        $report_type = trim($_POST['report_type'] ?? '');
        $start_date  = trim($_POST['start_date'] ?? '');
        $end_date    = trim($_POST['end_date'] ?? '');

        if (empty($report_type) || empty($start_date) || empty($end_date)) {
            throw new Exception('Validation Fault: Filter categories and timeline ranges cannot be blank.');
        }

        $records = [];
        $summary = ['metric_lbl' => 'Total Transactions', 'metric_val' => 0];

        switch ($report_type) {
            case 'OPD_Admissions':
                // FETCH CLINICAL CASE NOTE ENROLLMENTS
                $stmt = $con->prepare("
                    SELECT c.id as ref_id, p.name as patient_name, p.folder_number, 
                           c.disposition, DATE_FORMAT(c.created_at, '%d-%b-%Y') as date_logged, 
                           u.full_name as provider
                    FROM consultations c
                    JOIN patients p ON c.patient_id = p.id
                    JOIN users u ON c.doctor_id = u.id
                    WHERE DATE(c.created_at) BETWEEN :start AND :end
                    GROUP BY c.id, p.name, p.folder_number, c.disposition, c.created_at, u.full_name
                    ORDER BY c.id DESC
                ");
                $stmt->execute([':start' => $start_date, ':end' => $end_date]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $summary['metric_lbl'] = 'Total Case Files Logged';
                $summary['metric_val'] = count($records);
                
                // Friendly Class 4 Description text string
                $friendly_msg = "An administrator opened the big books to look at the list of all patients who came to see the doctor from " . date('d-M-Y', strtotime($start_date)) . " to " . date('d-M-Y', strtotime($end_date)) . ".";
                break;

            case 'Lab_Analytics':
                // FETCH LABORATORY VOLUME METRICS
                $stmt = $con->prepare("
                    SELECT lr.id as ref_id, p.name as patient_name, p.folder_number,
                           lc.test_name as description, lr.status, DATE_FORMAT(lr.created_at, '%d-%b-%Y') as date_logged
                    FROM lab_requests lr
                    JOIN patients p ON lr.patient_id = p.id
                    JOIN lab_catalog lc ON lr.lab_catalog_id = lc.id
                    WHERE DATE(lr.created_at) BETWEEN :start AND :end
                    GROUP BY lr.id, p.name, p.folder_number, lc.test_name, lr.status, lr.created_at
                    ORDER BY lr.id DESC
                ");
                $stmt->execute([':start' => $start_date, ':end' => $end_date]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $summary['metric_lbl'] = 'Total Laboratory Tests Run';
                $summary['metric_val'] = count($records);
                
                // Friendly Class 4 Description text string
                $friendly_msg = "An administrator checked the laboratory list to see all the blood and medical tests the lab workers completed from " . date('d-M-Y', strtotime($start_date)) . " to " . date('d-M-Y', strtotime($end_date)) . ".";
                break;

            case 'Pharmacy_Sales':
                // FETCH PHARMACY BALANCES ACCUMULATORS
                $stmt = $con->prepare("
                    SELECT pr.id as ref_id, p.name as patient_name, p.folder_number,
                           ps.drug_name as description, DATE_FORMAT(pr.created_at, '%d-%b-%Y') as date_logged
                    FROM prescriptions pr
                    JOIN patients p ON pr.patient_id = p.id
                    JOIN pharmacy_store ps ON pr.drug_id = ps.id
                    WHERE pr.status = 'Dispensed' AND DATE(pr.created_at) BETWEEN :start AND :end
                    GROUP BY pr.id, p.name, p.folder_number, ps.drug_name, pr.created_at
                    ORDER BY pr.id DESC
                ");
                $stmt->execute([':start' => $start_date, ':end' => $end_date]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $summary['metric_lbl'] = 'Total Prescriptions Dispensed';
                $summary['metric_val'] = count($records);
                
                // Friendly Class 4 Description text string
                $friendly_msg = "An administrator pulled the pharmacy list to check all the medicines and tablets given out to sick people from " . date('d-M-Y', strtotime($start_date)) . " to " . date('d-M-Y', strtotime($end_date)) . ".";
                break;

            case 'Finances_Ledger':
                // FETCH LEDGER CASH REVENUE ACCUMULATORS
                $stmt = $con->prepare("
                    SELECT l.id as ref_id, l.transaction_type, l.category as description,
                           l.amount, DATE_FORMAT(l.created_at, '%d-%b-%Y') as date_logged
                    FROM cash_flow_ledger l
                    WHERE DATE(l.created_at) BETWEEN :start AND :end
                    GROUP BY l.id, l.transaction_type, l.category, l.amount, l.created_at
                    ORDER BY l.id DESC
                ");
                $stmt->execute([':start' => $start_date, ':end' => $end_date]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Compute cash totals inside the backend engine core loops
                $sumInflow = 0;
                foreach($records as $r) {
                    if($r['transaction_type'] === 'Inflow') $sumInflow += (float)$r['amount'];
                }
                $summary['metric_lbl'] = 'Total Gross Cash Collected';
                $summary['metric_val'] = 'GHS ' . number_format($sumInflow, 2);
                
                // Friendly Class 4 Description text string
                $friendly_msg = "An administrator checked the money drawer to count all the cash and school clinic fees collected from " . date('d-M-Y', strtotime($start_date)) . " to " . date('d-M-Y', strtotime($end_date)) . ".";
                break;

            default:
                throw new Exception('Selection Error: Unrecognized audit report filter category channel.');
        }

        // CALL IMMUTABLE ACTIVITY LOG HOOK
        recordSystemActivityLog($con, 'Admin', 'SELECT', $friendly_msg);

        echo json_encode([
            'status'     => 'success',
            'summary'    => $summary,
            'records'    => $records,
            'meta'       => [
                'type_lbl' => str_replace('_', ' ', $report_type),
                'timeline' => date('d-M-Y', strtotime($start_date)) . ' to ' . date('d-M-Y', strtotime($end_date))
            ]
        ]);
        exit;

    } catch (\Throwable $err) {
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}


if (isset($_POST['action_upload_base64_avatar']) && $_POST['action_upload_base64_avatar'] === 'true') {
    try {
        $session_id = $_SESSION['user_id'] ?? null;
        if (!$session_id) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Re-authenticate access keys.']);
            exit;
        }

        $base64StringData = isset($_POST['avatar_base64']) ? trim($_POST['avatar_base64']) : '';

        if (empty($base64StringData)) {
            throw new Exception('Upload Error: Missing cropped base64 image data payload.');
        }

        // 1. CLEAN METADATA STRINGS PACKS
        if (preg_match('/^data:image\/(\w+);base64,/', $base64StringData, $type)) {
            $base64StringData = substr($base64StringData, strpos($base64StringData, ',') + 1);
            $fileExtension = strtolower($type[1]); // Captures 'png', 'jpg', etc.
            
            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                throw new Exception('Format Blocked: Invalid image data structure format type.');
            }
        } else {
            throw new Exception('Payload Error: Invalid image data header parameters structure.');
        }

        // 2. CRYPTOGRAPHICALLY DECODE STRING BACK TO RAW BINARY STREAM
        $binaryDataStream = base64_decode($base64StringData);
        if ($binaryDataStream === false) {
            throw new Exception('Decoding Failure: Image data stream decryption corrupt.');
        }

        // 3. SET SECURE PATH LAYOUT TARGETS USING YOUR DEFINED DIRECTORIES
        $uploadDir = $uploadDir.'profile_images/';
        if (!is_dir($uploadDir)) { 
            mkdir($uploadDir, 0755, true); 
        }

        $newFileName = 'avatar_' . $session_id . '_' . time() . '.' . $fileExtension;
        $url = $uploadUrl.'profile_images/'.$newFileName;
        $finalDestPath = $uploadDir . $newFileName;

        // Write the decoded string straight onto the disk server as a real graphic image file
        if (file_put_contents($finalDestPath, $binaryDataStream) === false) {
            throw new Exception('Disk Error: Failed to write base64 image block onto system disk.');
        }

        // 4. TRANSACTIONAL MUTATION LOCKS FOR DATABASE SYNC
        $con->beginTransaction();
        
        // Fetch old image file name to perform storage cleanup housekeeping
        $oldImgStmt = $con->prepare("SELECT profile_image FROM users WHERE id = ? FOR UPDATE");
        $oldImgStmt->execute([$session_id]);
        $oldImageName = $oldImgStmt->fetchColumn();

        $upd = $con->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $upd->execute([$url, $session_id]);

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description text string)
        $friendly_avatar_msg = "A worker changed their account face photo on the profile screen so their true face shows on the computer dashboard layout.";
        recordSystemActivityLog($con, 'Admin', 'UPDATE', $friendly_avatar_msg);

        $con->commit();

        // Clear outdated image rows out of disk space cleanly
        if ($oldImageName && $oldImageName !== 'default-avatar.png') {
            @unlink($uploadDir . $oldImageName);
        }

        // Uses your predefined $uploadUrl variable to return the fresh image source path locator
        echo json_encode([
            'status' => 'success',
            'message' => 'Profile picture updated successfully via client-side rendering canvas.',
            'new_image' => $newFileName,
            'image_url' => $url
        ]);
        exit;

    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}


// ROUTER ACTION A: FETCH CURRENT LIVE USERS REPOSITORY
if (isset($_POST['fetch_staff_list']) && $_POST['fetch_staff_list'] === 'true') {
    echo json_encode(fetchClinicStaffDirectory($con));
    exit;
}

// ROUTER ACTION B: UPSERT NEW / EXISTENT STAFF PROFILE RECORD LINES
if (isset($_POST['action_save_staff']) && $_POST['action_save_staff'] === 'true') {
    try {
        $staff_id_pk = isset($_POST['staff_id_pk']) ? (int)$_POST['staff_id_pk'] : 0;
        $full_name   = trim($_POST['full_name'] ?? '');
        $role        = trim($_POST['role'] ?? '');
        $status      = trim($_POST['status'] ?? 'Active');
        $password    = trim($_POST['password'] ?? '');

        if (empty($full_name) || empty($role)) {
            throw new Exception('Validation Fault: Legal Full Name and Clinical Assignment Role fields cannot be completely blank.');
        }

        $con->beginTransaction();

        if ($staff_id_pk > 0) {
            // SCENARIO A: MODIFIER LAYER FOR LIVE PROFILE ENTRIES
            if (!empty($password)) {
                $sec_hash = password_hash($password, PASSWORD_BCRYPT);
                $upd = $con->prepare("UPDATE users SET full_name = ?, role = ?, status = ?, password = ? WHERE id = ?");
                $upd->execute([$full_name, $role, $status, $sec_hash, $staff_id_pk]);
            } else {
                $upd = $con->prepare("UPDATE users SET full_name = ?, role = ?, status = ? WHERE id = ?");
                $upd->execute([$full_name, $role, $status, $staff_id_pk]);
            }
            
            // Friendly Class 4 Description text string
            $friendly_msg = "An administrator changed the information for " . $full_name . " to correct their name, work room, or password keys.";
            recordSystemActivityLog($con, 'Admin', 'UPDATE', $friendly_msg);
            
            $msg = "Staff professional credentials modified successfully.";
        } else {
            // SCENARIO B: SECURE AUTOMATED GENERATION FOR FRESH ACCOUNTS
            if (empty($password)) {
                throw new Exception('Account Creation Error: Baseline authorization password tokens are mandatory for fresh profiles.');
            }
            
            // ATOMIC SERIAL BLOCK LOOKUP: Calculate the sequential numeric increment safely
            $countStmt = $con->query("SELECT COUNT(*) FROM users FOR UPDATE");
            $nextSequenceValue = ((int)$countStmt->fetchColumn()) + 1;
            
            // Format dynamic unique string reference token (e.g., STF-0001)
            $generatedStaffIdString = "STF-". str_pad($nextSequenceValue, 4, "0", STR_PAD_LEFT);

            $sec_hash = password_hash($password, PASSWORD_BCRYPT);
            
            $ins = $con->prepare("INSERT INTO users (staff_id, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$generatedStaffIdString, $sec_hash, $full_name, $role, $status]);
            
            // Friendly Class 4 Description text string
            $friendly_msg = "A brand new worker named " . $full_name . " was registered to start work in the clinic as a " . $role . " with ID number " . $generatedStaffIdString . ".";
            recordSystemActivityLog($con, 'Admin', 'INSERT', $friendly_msg);
            
            $msg = "Profile successfully generated! System ID allocated: [{$generatedStaffIdString}].";
        }

        $con->commit();
        echo json_encode(array_merge(['status' => 'success', 'message' => $msg], fetchClinicStaffDirectory($con)));
        exit;
    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}

 
if (isset($_POST['fetch_executive_metrics']) && $_POST['fetch_executive_metrics'] === 'true') {
    try {
        // 1. FINANCIAL CASH FLOW OVERVIEW SUMMARY BOXES
        $finQuery = "
            SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'Inflow' THEN amount ELSE 0 END), 0.00) as total_inflow,
                COALESCE(SUM(CASE WHEN transaction_type = 'Outflow' THEN amount ELSE 0 END), 0.00) as total_outflow
            FROM cash_flow_ledger
        ";
        $finStmt = $con->query($finQuery);
        $finStats = $finStmt->fetch(PDO::FETCH_ASSOC);
        
        $totalInflow  = (float)$finStats['total_inflow'];
        $totalOutflow = (float)$finStats['total_outflow'];
        $netRunway    = $totalInflow - $totalOutflow;

        // 2. CASE LOAD PROFILE VOLUME TRACKERS (ATTENDANCE STATS)
        $caseQuery = "
            SELECT 
                COUNT(c.id) as total_cases,
                COUNT(CASE WHEN c.disposition = 'Inpatient' THEN 1 END) as total_admissions,
                (SELECT COUNT(*) FROM lab_requests WHERE status = 'Pending') as pending_labs,
                (SELECT COUNT(*) FROM prescriptions WHERE status = 'Pending' AND dosage_instruction NOT LIKE '[INJECTION]%') as pending_phr
            FROM consultations c
        ";
        $caseStmt = $con->query($caseQuery);
        $caseStats = $caseStmt->fetch(PDO::FETCH_ASSOC);

        // 3. REVENUE EARNINGS GROUP DISTRIBUTION (FULL-GROUP MODE COMPLIANT)
        $revDistQuery = "
            SELECT 
                category as label, 
                COALESCE(SUM(amount), 0.00) as value
            FROM cash_flow_ledger
            WHERE transaction_type = 'Inflow'
            GROUP BY category
            ORDER BY value DESC
        ";
        $revDistStmt = $con->prepare($revDistQuery);
        $revDistStmt->execute();
        $revenueDistribution = $revDistStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. TOP PRESCRIBED MEDICATION LINES MATRIX
        $topDrugsQuery = "
            SELECT 
                ps.drug_name as drug, 
                COUNT(pr.id) as frequency
            FROM prescriptions pr
            JOIN pharmacy_store ps ON pr.drug_id = ps.id
            GROUP BY ps.id, ps.drug_name
            ORDER BY frequency DESC 
            LIMIT 5
        ";
        $topDrugsStmt = $con->prepare($topDrugsQuery);
        $topDrugsStmt->execute();
        $topMedications = $topDrugsStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. RECENT HIGH-VALUE SYSTEM LEDGER OUTFLOWS LOGS
        $expenseQuery = "
            SELECT 
                id, category, amount, description,
                DATE_FORMAT(created_at, '%d-%b-%Y') as date_logged
            FROM cash_flow_ledger
            WHERE transaction_type = 'Outflow'
            GROUP BY id, category, amount, description, created_at
            ORDER BY id DESC 
            LIMIT 5
        ";
        $expenseStmt = $con->prepare($expenseQuery);
        $expenseStmt->execute();
        $recentExpenses = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

        // CALL IMMUTABLE ACTIVITY LOG HOOK
        // Friendly Class 4 Description text string
        $friendly_dash_msg = "The big boss opened the master dashboard screen to look at charts showing all clinic money counts, sick children records, and top moving medicines.";
        recordSystemActivityLog($con, 'Admin', 'SELECT', $friendly_dash_msg);

        // Emit comprehensive structural reports package straight back via AJAX
        echo json_encode([
            'status' => 'success',
            'financials' => [
                'inflow' => $totalInflow,
                'outflow' => $totalOutflow,
                'net_balance' => $netRunway
            ],
            'caseload' => [
                'total_cases' => (int)$caseStats['total_cases'],
                'admissions' => (int)$caseStats['total_admissions'],
                'pending_labs' => (int)$caseStats['pending_labs'],
                'pending_pharmacy' => (int)$caseStats['pending_phr']
            ],
            'revenue_distribution' => $revenueDistribution,
            'top_medications' => $topMedications,
            'recent_expenses' => $recentExpenses
        ]);
        exit;

    } catch (\Throwable $ex) {
        error_log("Executive Reporting Suite Engine failure: " . $ex->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'An operational error occurred compiling analytics: ' . $ex->getMessage()
        ]);
        exit;
    }
}


// ACTION A: INITIAL REFRESH DATA DISPATCH
if (isset($_POST['finance_ledger_fetch']) && $_POST['finance_ledger_fetch'] === 'true') {
    echo json_encode(fetchFinancialLedgerSummary($con));
    exit;
}
// ACTION B: RECORD MANUAL CASH FLOW OUTFLOW / EXPENSE VOUCHER
if (isset($_POST['action_log_expense']) && $_POST['action_log_expense'] === 'true') {
    try {
        $category = trim($_POST['exp_category'] ?? '');
        $amount   = isset($_POST['exp_amount']) ? (float)$_POST['exp_amount'] : 0.00;
        $desc     = trim($_POST['exp_desc'] ?? '');
        $user_id  = $_SESSION['user_id'] ?? 1;

        if (empty($category) || $amount <= 0 || empty($desc)) {
            throw new Exception('Validation Error: Outflow records require explicit categories, descriptions, and values.');
        }

        $con->beginTransaction();

        $ins = $con->prepare("INSERT INTO cash_flow_ledger (transaction_type, category, amount, description, recorded_by) VALUES ('Outflow', ?, ?, ?, ?)");
        $ins->execute([$category, $amount, $desc, $user_id]);

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_expense_msg = "The cashier took GHS " . number_format($amount, 2) . " out of the clinic money drawer to pay for " . $desc . " under the " . $category . " list.";
        recordSystemActivityLog($con, 'Accounts', 'INSERT', $friendly_expense_msg);

        $con->commit();
        echo json_encode(array_merge(['status' => 'success', 'message' => 'Expense logged and balances adjusted safely.'], fetchFinancialLedgerSummary($con)));
        exit;
    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}

// ====================================================================
// STANDALONE BACKEND CONTROLLER: WAREHOUSE BULK INVENTORY VAULT CONTROL
// ==================================================================== 

// INTERCEPTOR A: MASTER WAREHOUSE INVENTORY LIST REFRESH
if (isset($_POST['warehouse_inventory_fetch']) && $_POST['warehouse_inventory_fetch'] === 'true') {
    echo json_encode(fetchMainStoreWarehouseInventory($con));
    exit;
}

// INTERCEPTOR B: INGEST / UPSERT A NEW SUPPLIER SHIPMENT BATCH RECIEVED
if (isset($_POST['action_log_warehouse_shipment']) && $_POST['action_log_warehouse_shipment'] === 'true') {
    try {
        $drug_name    = trim($_POST['drug_name'] ?? '');
        $quantity     = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $batch_number = trim($_POST['batch_number'] ?? '');

        if (empty($drug_name) || $quantity <= 0 || empty($batch_number)) {
            throw new Exception('Log Failure: Received fields metrics properties passed contain empty entries or zero volume values.');
        }

        // UPSERT LOGIC: If item name matching row exists, increment its count. Otherwise, insert a fresh row.
        $upsertQuery = "
            INSERT INTO main_medical_store (drug_name, quantity_in_warehouse, batch_number) 
            VALUES (:name, :qty, :batch)
            ON DUPLICATE KEY UPDATE 
                quantity_in_warehouse = quantity_in_warehouse + VALUES(quantity_in_warehouse),
                batch_number = VALUES(batch_number)
        ";
        
        $upsertStmt = $con->prepare($upsertQuery);
        $upsertStmt->execute([
            ':name'  => $drug_name,
            ':qty'   => $quantity,
            ':batch' => $batch_number
        ]);

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_ingest_msg = "The storekeeper received a big box from suppliers containing " . $quantity . " units of " . $drug_name . " and locked them inside the main warehouse vault under batch code " . $batch_number . ".";
        recordSystemActivityLog($con, 'Pharmacy', 'INSERT', $friendly_ingest_msg);

        echo json_encode(array_merge(
            ['status' => 'success', 'message' => "Bulk shipment of '{$drug_name}' ingested and batch '{$batch_number}' locked into vault counts safely."],
            fetchMainStoreWarehouseInventory($con)
        ));
        exit;
    } catch (\Throwable $err) {
        echo json_encode(['status' => 'error', 'message' => 'Shipment tracking failed: ' . $err->getMessage()]);
        exit;
    }
}
 

// ====================================================================
// ASYNC REQUEST ROUTING INTERCEPTOR GATES
// ====================================================================

// INTERCEPTOR A: OVERALL STOCK DROPDOWN AND LOG DATA REFRESH CALLS
if (isset($_POST['pharmacy_inventory_fetch']) && $_POST['pharmacy_inventory_fetch'] === 'true') {
    echo json_encode(fetchPharmacyRequisitionDataPack($con));
    exit;
}

// INTERCEPTOR B: SUBMIT AND TRANSMIT A NEW PROCUREMENT DEMAND VOUCHER TO THE DEPOT
if (isset($_POST['action_submit_requisition']) && $_POST['action_submit_requisition'] === 'true') {
    try {
        $drug_id = isset($_POST['req_drug_id']) ? (int)$_POST['req_drug_id'] : 0;
        $qty     = isset($_POST['req_qty']) ? (int)$_POST['req_qty'] : 0;
        $user_id = $_SESSION['user_id'] ?? 1; // Fallback profile marker index if session drops

        // Compulsory logical input sanity validation check
        if ($drug_id <= 0 || $qty <= 0) {
            throw new Exception('Requisition Denied: Please choose a valid drug and a replenishment quantity volume greater than zero.');
        }

        // Clean prepared statement insertion sequence mapping parameters
        $insQuery = "INSERT INTO pharmacy_requisitions (drug_id, requested_qty, requested_by, status) VALUES (?, ?, ?, 'Pending')";
        $insStmt = $con->prepare($insQuery);
        $insStmt->execute([$drug_id, $qty, $user_id]);

        // Pull drug name for friendly log text
        $nameFinder = $con->prepare("SELECT drug_name FROM pharmacy_store WHERE id = ? LIMIT 1");
        $nameFinder->execute([$drug_id]);
        $drug_name_string = $nameFinder->fetchColumn() ?: "Unknown Medicine";

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_req_msg = "The pharmacist checked the room shelves and sent an order paper to the main warehouse to ask for " . $qty . " more boxes of " . $drug_name_string . ".";
        recordSystemActivityLog($con, 'Pharmacy', 'INSERT', $friendly_req_msg);

        // Output success coupled with a clean recalculated data pack data map refresh
        echo json_encode(array_merge(
            ['status' => 'success', 'message' => 'Restock demand voucher successfully generated and dispatched to the main warehouse.'], 
            fetchPharmacyRequisitionDataPack($con)
        ));
        exit;
    } catch (\Throwable $err) {
        echo json_encode([
            'status'  => 'error', 
            'message' => 'Voucher submission failed: ' . $err->getMessage()
        ]);
        exit;
    }
}
 
// ROUTER TERMINAL A: FETCH ACTIVE PENDING PROCUREMENTS QUEUE
if (isset($_POST['main_store_fetch']) && $_POST['main_store_fetch'] === 'true') {
    echo json_encode(getMainStoreRequisitionsQueue($con));
    exit;
}

// ROUTER TERMINAL B: AUTHORIZE TRANSACTIONAL STOCK ASSET ASIGNMENT
if (isset($_POST['action_approve_requisition']) && $_POST['action_approve_requisition'] === 'true') {
    try {
        $req_id            = isset($_POST['requisition_id']) ? (int)$_POST['requisition_id'] : 0;
        $warehouse_drug_id = isset($_POST['warehouse_drug_id']) ? (int)$_POST['warehouse_drug_id'] : 0;
        $pharmacy_drug_id  = isset($_POST['pharmacy_drug_id']) ? (int)$_POST['pharmacy_drug_id'] : 0;
        $requested_qty     = isset($_POST['requested_qty']) ? (int)$_POST['requested_qty'] : 0;

        if ($req_id <= 0 || $warehouse_drug_id <= 0 || $pharmacy_drug_id <= 0 || $requested_qty <= 0) {
            throw new Exception('Fulfill Error: Compulsory logistical parameters identifiers are missing.');
        }

        $con->beginTransaction();

        // 1. Lock warehouse row to prevent multi-terminal double asset distribution errors
        $checkWarehouse = $con->prepare("SELECT drug_name, quantity_in_warehouse FROM main_medical_store WHERE id = ? FOR UPDATE");
        $checkWarehouse->execute([$warehouse_drug_id]);
        $warehouseData = $checkWarehouse->fetch(PDO::FETCH_ASSOC);
        
        if (!$warehouseData) {
            throw new Exception("Target medication item cannot be mapped inside warehouse vault records.");
        }

        $warehouseQty = (int)$warehouseData['quantity_in_warehouse'];
        if ($warehouseQty < $requested_qty) {
            throw new Exception("Stock Insufficient inside Main Warehouse Depot reserves.");
        }

        // 2. Subtract from Main Warehouse Depot balance
        $deductWarehouse = $con->prepare("UPDATE main_medical_store SET quantity_in_warehouse = quantity_in_warehouse - ? WHERE id = ?");
        $deductWarehouse->execute([$requested_qty, $warehouse_drug_id]);

        // 3. Increment the active front Pharmacy Store shelf balance
        $addPharmacy = $con->prepare("UPDATE pharmacy_store SET quantity_in_store = quantity_in_store + ? WHERE id = ?");
        $addPharmacy->execute([$requested_qty, $pharmacy_drug_id]);

        // 4. Update request voucher status tracker to Approved
        $approveReq = $con->prepare("UPDATE pharmacy_requisitions SET status = 'Approved' WHERE id = ?");
        $approveReq->execute([$req_id]);

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_approve_msg = "The head storekeeper looked at the order paper and agreed to send " . $requested_qty . " units of " . $warehouseData['drug_name'] . " out of the main vault straight onto the active pharmacy room shelves.";
        recordSystemActivityLog($con, 'Pharmacy', 'UPDATE', $friendly_approve_msg);

        $con->commit();
        echo json_encode(getMainStoreRequisitionsQueue($con));
        exit;
    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}

if (isset($_POST['pharmacy_inventory_fetch']) && $_POST['pharmacy_inventory_fetch'] === 'true') {
    echo json_encode(fetchPharmacyInventoryDataPack($con));
    exit;
}
// INTERCEPTOR C: ADJUST AN INDIVIDUAL MEDICATION PRICING RATE & TRIGGER THRESHOLD
if (isset($_POST['action_update_drug_metrics']) && $_POST['action_update_drug_metrics'] === 'true') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $drug_id   = isset($_POST['edit_drug_id']) ? (int)$_POST['edit_drug_id'] : 0;
        $price     = isset($_POST['edit_price']) ? (float)$_POST['edit_price'] : -1.00;
        $threshold = isset($_POST['edit_threshold']) ? (int)$_POST['edit_threshold'] : -1;

        if ($drug_id <= 0 || $price < 0 || $threshold < 0) {
            throw new Exception('Update Rejected: Received invalid structural numbers properties input attributes values.');
        }

        // Get drug name for the friendly audit log text
        $nameStmt = $con->prepare("SELECT drug_name FROM pharmacy_store WHERE id = ? LIMIT 1");
        $nameStmt->execute([$drug_id]);
        $drug_name_string = $nameStmt->fetchColumn() ?: "Unknown Medicine";

        // Apply transactional update
        $updQuery = "UPDATE pharmacy_store SET selling_price = ?, min_threshold_qty = ? WHERE id = ?";
        $updStmt = $con->prepare($updQuery);
        $updStmt->execute([$price, $threshold, $drug_id]);

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_store_msg = "The pharmacist changed the rules for " . $drug_name_string . " so that one unit now costs GHS " . number_format($price, 2) . " and the system will give a warning when only " . $threshold . " boxes are left on the shelf.";
        recordSystemActivityLog($con, 'Pharmacy', 'UPDATE', $friendly_store_msg);

        // Output success coupled with a clean recalculated data pack refresh
        echo json_encode(array_merge(
            ['status' => 'success', 'message' => 'Shelf inventory configuration values synchronized successfully.'], 
            fetchPharmacyInventoryDataPack($con)
        ));
        exit;
    } catch (\Throwable $err) {
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}


// ROUTER ACTION 1: FETCH ACTIVE ADMISSIONS WORKSPACE LAYOUT PACKS
if (isset($_POST['ward_admission_fetch']) && $_POST['ward_admission_fetch'] === 'true') { 
    echo json_encode(getWardAdmissionsQueue($con));
    exit;
}

// ROUTER ACTION 2: ASSIGN A PHYSICAL BED AND ADMIT PATIENT TO WARD
if (isset($_POST['action_allocate_bed']) && $_POST['action_allocate_bed'] === 'true') {
    try {
        $admission_id = (int)$_POST['admission_id'];
        $bed_id       = (int)$_POST['bed_id'];
        $notes        = trim($_POST['nursing_notes'] ?? '');

        if (!$admission_id || !$bed_id) throw new Exception('Allocation Error: Missing bed or admission token.');

        $con->beginTransaction();

        // 1. Verify and lock the chosen bed to block dual booking race conditions
        $bedCheck = $con->prepare("SELECT b.bed_number, w.ward_name FROM ward_beds b JOIN ward_catalog w ON b.ward_id = w.id WHERE b.id = ? FOR UPDATE");
        $bedCheck->execute([$bed_id]);
        $bedData = $bedCheck->fetch(PDO::FETCH_ASSOC);
        
        // Fetch patient name for friendly audit logs
        $ptCheck = $con->prepare("SELECT p.name FROM ward_admissions wa JOIN patients p ON wa.patient_id = p.id WHERE wa.id = ? LIMIT 1");
        $ptCheck->execute([$admission_id]);
        $patient_name_string = $ptCheck->fetchColumn() ?: "A sick patient";

        if (!$bedData) throw new Exception('Target bed space is already occupied or under maintenance.');

        // 2. Bind the patient file to the bed space row
        $updateAdm = $con->prepare("UPDATE ward_admissions SET bed_id = ?, admission_status = 'Admitted', nursing_notes = ?, admitted_at = CURRENT_TIMESTAMP() WHERE id = ?");
        $updateAdm->execute([$bed_id, $notes, $admission_id]);

        // 3. Mark the bed status as Occupied
        $updateBed = $con->prepare("UPDATE ward_beds SET bed_status = 'Occupied' WHERE id = ?");
        $updateBed->execute([$bed_id]);

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_admit_msg = "The nurse took " . $patient_name_string . " into the " . $bedData['ward_name'] . " and made them lie down on bed space " . $bedData['bed_number'] . " so they can sleep and get well.";
        recordSystemActivityLog($con, 'Triage', 'UPDATE', $friendly_admit_msg);

        $con->commit();
        echo json_encode(array_merge(['status' => 'success', 'message' => 'Patient successfully admitted and bed allocated safely.'], getWardAdmissionsQueue($con)));
        exit;
    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}

// ROUTER ACTION 3: CHRONO TREATMENT CHECKOUT DISCHARGE
if (isset($_POST['action_discharge_patient']) && $_POST['action_discharge_patient'] === 'true') { 
    try {
        $admission_id = (int)$_POST['admission_id'];
        if (!$admission_id) throw new Exception('Missing active admission index tracker.');

        $con->beginTransaction();

        // Retrieve bound bed and patient metadata details for friendly logs
        $metaCheck = $con->prepare("
            SELECT p.name as pt_name, wb.bed_number, wc.ward_name, wa.bed_id 
            FROM ward_admissions wa 
            JOIN patients p ON wa.patient_id = p.id 
            LEFT JOIN ward_beds wb ON wa.bed_id = wb.id 
            LEFT JOIN ward_catalog wc ON wb.ward_id = wc.id 
            WHERE wa.id = ? LIMIT 1
        ");
        $metaCheck->execute([$admission_id]);
        $metaData = $metaCheck->fetch(PDO::FETCH_ASSOC);
        
        $patient_name_string = $metaData['pt_name'] ?? "A patient";
        $bed_id = (int)($metaData['bed_id'] ?? 0);

        // 2. Clear out the ward admissions case note tracking files indicators
        $updateAdm = $con->prepare("UPDATE ward_admissions SET admission_status = 'Discharged', discharged_at = CURRENT_TIMESTAMP() WHERE id = ?");
        $updateAdm->execute([$admission_id]);

        // 3. Free up the physical bed space container row back to Available status
        if ($bed_id > 0) {
            $updateBed = $con->prepare("UPDATE ward_beds SET bed_status = 'Available' WHERE id = ?");
            $updateBed->execute([$bed_id]);
        }

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_discharge_msg = $patient_name_string . " is now strong and healthy, so the nurse allowed them to get up from bed " . ($metaData['bed_number'] ?? 'space') . " and go home to their family.";
        recordSystemActivityLog($con, 'Triage', 'UPDATE', $friendly_discharge_msg);

        $con->commit();
        echo json_encode(array_merge(['status' => 'success', 'message' => 'Patient discharged cleanly and bed space freed back to pool list.'], getWardAdmissionsQueue($con)));
        exit;
    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}

// B. CONTROLLER SWITCH: HANDLE AJAX COMMITS AND POOL DATA LOOKUPS
if (isset($_POST['cashier_billing_fetch']) && $_POST['cashier_billing_fetch'] === 'true') { 
    echo json_encode(getPendingCashierQueue($con));
    exit;
}


// ====================================================================
// MASTER CONTROLLER ROUTER: EXECUTE CASHIER PAYMENT SETTLEMENT
// ====================================================================
if (isset($_POST['action_process_payment']) && $_POST['action_process_payment'] === 'true') {
    try {
        $selected_bill_ids = isset($_POST['bill_ids']) && is_array($_POST['bill_ids']) ? $_POST['bill_ids'] : [];
        $consultation_id   = isset($_POST['consultation_id']) ? (int)$_POST['consultation_id'] : 0;
        $cashier_id        = $_SESSION['user_id'] ?? 1;

        if (empty($selected_bill_ids) || $consultation_id <= 0) {
            throw new Exception('Payment Aborted: Selected invoices or master consultation references are missing.');
        }

        $con->beginTransaction();

        // 1. CALL THE INFLOW FUNCTION FIRST: Pulls the outstanding balances and logs them into cash_flow_ledger
        $ledgerSweep = logPatientPaymentToCashFlowLedger($con, $selected_bill_ids, $consultation_id, $cashier_id);
        if ($ledgerSweep['status'] === 'error') {
            throw new Exception("Financial Ledger Error: " . $ledgerSweep['message']);
        }

        // 2. SETTLE BILLS: Flip selected billing row payment statuses from 'Unpaid' to 'Paid'
        $placeholders = implode(',', array_fill(0, count($selected_bill_ids), '?'));
        $updateQuery  = "UPDATE billing SET payment_status = 'Paid', cashier_id = ? WHERE id IN ($placeholders) AND payment_status = 'Unpaid'";
        
        $updateStmt = $con->prepare($updateQuery);
        $executionParams = array_merge([$cashier_id], array_map('intval', $selected_bill_ids));
        $updateStmt->execute($executionParams);

        // Fetch patient name to format the friendly audit trailing summary string
        $ptStmt = $con->prepare("SELECT p.name FROM consultations c JOIN patients p ON c.patient_id = p.id WHERE c.id = ? LIMIT 1");
        $ptStmt->execute([$consultation_id]);
        $patient_name_string = $ptStmt->fetchColumn() ?: "A patient";

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $amount_collected = isset($ledgerSweep['amount']) ? (float)$ledgerSweep['amount'] : 0.00;
        $friendly_pay_msg = "The cashier collected a total sum of GHS " . number_format($amount_collected, 2) . " from " . $patient_name_string . " at the front desk counter and marked all their treatment paper bills as paid.";
        recordSystemActivityLog($con, 'Accounts', 'UPDATE', $friendly_pay_msg);

        $con->commit();

        // 3. RE-FETCH FRESH LEDGER OUTSTANDINGS DATASET FOR IMMEDIATE FRONTEND REBUILDS
        $refreshedQueue = getPendingCashierQueue($con);
        
        echo json_encode([
            'status'  => 'success',
            'message' => 'Payment processed successfully. ' . ($ledgerSweep['status'] === 'success' ? 'Ledger accounts updated.' : ''),
            'data'    => $refreshedQueue['data']
        ]);
        exit;

    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { 
            $con->rollBack(); 
        }
        error_log("Cashier checkout process exception: " . $err->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed on server: ' . $err->getMessage()]);
        exit;
    }
}


// B. CONTROLLER SWITCH: ROUTE DATA TRANSITIONS ON POST COMMITS
if (isset($_POST['nurse_injection_fetch']) && $_POST['nurse_injection_fetch'] === 'true') { 
    echo json_encode(getPendingInjectionsQueue($con));
    exit;
}

if (isset($_POST['action_administer_injection']) && $_POST['action_administer_injection'] === 'true') { 
    try {
        $prescription_id = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : null;
        if (!$prescription_id) throw new Exception('Invalid prescription reference.');

        $con->beginTransaction();

        // Pull data properties for creating a very friendly clear log message text
        $infoStmt = $con->prepare("
            SELECT p.name as pt_name, ps.drug_name 
            FROM prescriptions pr 
            JOIN patients p ON pr.patient_id = p.id 
            JOIN pharmacy_store ps ON pr.drug_id = ps.id 
            WHERE pr.id = ? LIMIT 1
        ");
        $infoStmt->execute([$prescription_id]);
        $infoRow = $infoStmt->fetch(PDO::FETCH_ASSOC);
        $patient_name = $infoRow['pt_name'] ?? "A patient";
        $medicine_name = $infoRow['drug_name'] ?? "the injection fluid";

        // Flip clinical indicator status parameters to completed state
        $updateStmt = $con->prepare("UPDATE prescriptions SET status = 'Dispensed' WHERE id = :id");
        $updateStmt->execute([':id' => $prescription_id]);

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $friendly_inject_msg = "The nurse carefully put " . $medicine_name . " into a syringe needle and gave the injection to " . $patient_name . " at the treatment room so they can feel better soon.";
        recordSystemActivityLog($con, 'Nurse', 'UPDATE', $friendly_inject_msg);

        $con->commit();

        // Pull fresh list arrays for immediate UI rebuilds
        $refreshedQueue = getPendingInjectionsQueue($con);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Injection fluid administered and logged successfully in history chart metrics.',
            'data'    => $refreshedQueue['data']
        ]);
        exit;
    } catch (\Throwable $err) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $err->getMessage()]);
        exit;
    }
}

// ====================================================================
// MASTER CONTROLLER ROUTER: SUBMIT PHARMACY DISPENSATION CHECKOUT
// ====================================================================
if (isset($_POST['action_dispense_meds']) && $_POST['action_dispense_meds'] === 'true') { 
    try {
        if (!$con) {
            throw new Exception('Database transactional link connection dropped.');
        }

        // 1. EXTRACT DATA POST METRICS
        $consultation_id  = isset($_POST['p_consultation_id']) ? (int)$_POST['p_consultation_id'] : null;
        $patient_id       = isset($_POST['p_patient_id']) ? (int)$_POST['p_patient_id'] : null;
        $prescription_ids = isset($_POST['r_prescription_ids']) && is_array($_POST['r_prescription_ids']) ? $_POST['r_prescription_ids'] : [];
        $drug_ids         = isset($_POST['r_drug_ids']) && is_array($_POST['r_drug_ids']) ? $_POST['r_drug_ids'] : [];

        if (!$consultation_id || !$patient_id || empty($prescription_ids)) {
            throw new Exception('Fulfill Error: Compulsory clinical tracking parameters are missing.');
        }

        // 2. OPEN DATABASE CORE TRANSACTION
        $con->beginTransaction();

        $dispensed_drugs_list = [];

        // 3. ATOMIC DEDUCTION LOOP PIPELINE
        for ($i = 0; $i < count($prescription_ids); $i++) {
            $presc_id = (int)$prescription_ids[$i];
            $drug_id  = (int)$drug_ids[$i];

            if ($presc_id <= 0 || $drug_id <= 0) continue;

            // A. INVENTORY BOUNDARY CHECK: Lock the specific stock row to prevent race conditions
            $stockCheckStmt = $con->prepare("SELECT drug_name, quantity_in_store FROM pharmacy_store WHERE id = :id FOR UPDATE");
            $stockCheckStmt->execute([':id' => $drug_id]);
            $drugStockItem = $stockCheckStmt->fetch(PDO::FETCH_ASSOC);

            if (!$drugStockItem) {
                throw new Exception("Stock Anomaly: Drug item ID {$drug_id} cannot be mapped inside store ledger indices.");
            }

            $currentStockQty = (int)$drugStockItem['quantity_in_store'];
            if ($currentStockQty <= 0) {
                throw new Exception("Depletion Warning: '{$drugStockItem['drug_name']}' is entirely out of stock. Cannot dispense.");
            }

            // Keep track of the medicine name for the activity audit trail
            $dispensed_drugs_list[] = $drugStockItem['drug_name'];

            // B. DECREMENT QUANTITY: Deduct exactly 1 unit
            $deductInventoryStmt = $con->prepare("UPDATE pharmacy_store SET quantity_in_store = quantity_in_store - 1 WHERE id = :id");
            $deductInventoryStmt->execute([':id' => $drug_id]);

            // C. CHANGE PRESCRIPTION STATUS: Flip state indicators from 'Pending' right over to 'Dispensed'
            $updatePrescriptionStatusStmt = $con->prepare("UPDATE prescriptions SET status = 'Dispensed' WHERE id = :id");
            $updatePrescriptionStatusStmt->execute([':id' => $presc_id]);
        }

        // Fetch patient name to finalize the friendly log text summary
        $ptFinder = $con->prepare("SELECT name FROM patients WHERE id = ? LIMIT 1");
        $ptFinder->execute([$patient_id]);
        $patient_name_string = $ptFinder->fetchColumn() ?: "A patient";

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $comma_separated_drugs = implode(', ', $dispensed_drugs_list);
        $friendly_dispense_msg = "The pharmacist counted the drug tablets, put them inside small white envelopes, and handed over [" . $comma_separated_drugs . "] safely to " . $patient_name_string . " at the pharmacy window.";
        recordSystemActivityLog($con, 'Pharmacy', 'UPDATE', $friendly_dispense_msg);

        // 4. COMMIT ALL MUTATIONS TO THE INVENTORY DATABASE SKELETON
        $con->commit();

        // 5. RE-FETCH REFRESHED QUEUE RECORDS FOR THE PHARMACIST WORKSPACE CONTAINER
        $refreshedQueueResponse = getPendingPharmacyQueue($con);
        $freshQueueDataset = $refreshedQueueResponse['data'];

        echo json_encode([
            'status'  => 'success',
            'message' => 'Medication items successfully issued, stock levels decremented, and patient file completed.',
            'data'    => $freshQueueDataset
        ]);
        exit;

    } catch (\Throwable $caughtException) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        error_log("Pharmacy Dispatch Core Crash Anomaly: " . $caughtException->getMessage());
        echo json_encode([
            'status'  => 'error',
            'message' => 'The dispensation pipeline failed on server: ' . $caughtException->getMessage()
        ]);
        exit;
    }
}

// ====================================================================
// UNIFIED MASTER CONTROLLER ROUTER: PHARMACY DISPENSATION MODULE QUEUE
// ====================================================================
if (isset($_POST['pharmacy_dispense']) && $_POST['pharmacy_dispense'] === 'true') { 
    try {
        if (!$con) {
            throw new Exception('Database transactional link connection dropped.');
        }

        $queueDataResult = getPendingPharmacyQueue($con); 
        echo json_encode($queueDataResult);
        exit;

    } catch (\Throwable $caughtException) {
        error_log("Pharmacy System Controller Runtime Error: " . $caughtException->getMessage());
        echo json_encode([
            'status'  => 'error',
            'message' => 'An entry lookup failure occurred inside pharmacy ledger streams: ' . $caughtException->getMessage(),
            'data'    => []
        ]);
        exit;
    }
}


// Check for the laboratory reports processing execution request flag
if (isset($_POST['manage_lab_results']) && $_POST['manage_lab_results'] === 'true') { 

    try {
        if (!$con) {
            throw new Exception('Database link connection pipeline dropped.');
        }

        // 1. CAPTURE INPUT METRICS VALUES
        $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : null;
        $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
        $lab_result = trim($_POST['lab_result'] ?? '');
        
        // Capture optional Base64 parameters strings sent from front-end AJAX
        $base64_string = isset($_POST['lab_base64_file']) ? trim($_POST['lab_base64_file']) : '';
        $raw_file_name = isset($_POST['lab_file_name']) ? trim($_POST['lab_file_name']) : '';
        
        $technician_id = $_SESSION['user_id'] ?? 1;

        if (!$request_id || !$patient_id || empty($lab_result)) {
            throw new Exception('Compulsory laboratory analytical findings field input cannot be blank.');
        }

        // 2. OPTIONAL BACKEND DECODING AND ASSET SYSTEM GENERATION PIPELINE
        $saved_file_web_url = null;

        if (!empty($base64_string) && !empty($raw_file_name)) {
            // Extract extension type safely from filename parameter
            $nameComponents = explode(".", $raw_file_name);
            $fileExtension  = strtolower(end($nameComponents));
            $allowedExtensions = ['png', 'jpeg', 'jpg', 'pdf'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception('Payload Error: Extension classification pattern rejected by file system rules.');
            }

            // Execute decoding conversion process step natively
            $decodedBinaryData = base64_decode($base64_string);
            if ($decodedBinaryData === false) {
                throw new Exception('Corrupted Data: Base64 cryptographic stream decoding failed.');
            }

            // Create target system storage directory structure path if unallocated using your predefined uploadDir
            $targetUploadDirectory = rtrim($uploadDir, '/') . '/lab_reports/';
            if (!is_dir($targetUploadDirectory)) {
                mkdir($targetUploadDirectory, 0755, true);
            }

            // Generate hashed unique file name signatures to insulate index records data overrides
            $uniqueHashedName = md5(time() . $raw_file_name) . '.' . $fileExtension;
            $absoluteFileSystemPath = $targetUploadDirectory . $uniqueHashedName;

            // Write the decoded binaries data direct to server disk space files
            if (file_put_contents($absoluteFileSystemPath, $decodedBinaryData) !== false) {
                // Map the full public access location using your predefined uploadUrl variable string
                $saved_file_web_url = rtrim($uploadUrl, '/') . '/lab_reports/' . $uniqueHashedName;
            } else {
                throw new Exception('Write Error: Failed to execute file creation on internal server storage drive.');
            }
        }

        // 3. OPEN DATABASE CORE MUTATION TRANSACTION
        $con->beginTransaction();

        // Fetch patient name and test name descriptors to construct the friendly log summary
        $infoStmt = $con->prepare("
            SELECT p.name as pt_name, lc.test_name 
            FROM lab_requests lr 
            JOIN patients p ON lr.patient_id = p.id 
            JOIN lab_catalog lc ON lr.lab_catalog_id = lc.id 
            WHERE lr.id = ? LIMIT 1
        ");
        $infoStmt->execute([$request_id]);
        $infoRow = $infoStmt->fetch(PDO::FETCH_ASSOC);
        $patient_name_string = $infoRow['pt_name'] ?? "A sick patient";
        $test_name_string    = $infoRow['test_name'] ?? "the requested test";

        // 4. SAVE LAB RESULTS DATA FIELDS AND SET STATUS CODE TO 'Completed'
        $updateLabQuery = "UPDATE lab_requests SET 
                                lab_result = :result, 
                                attached_file = :attachment, -- Save the public web file URL string here (or null)
                                status = 'Completed', 
                                technician_id = :tech_id 
                           WHERE id = :req_id";
                           
        $labStmt = $con->prepare($updateLabQuery);
        $labStmt->execute([
            ':result'     => $lab_result,
            ':attachment' => $saved_file_web_url, 
            ':tech_id'    => $technician_id,
            ':req_id'     => $request_id
        ]);

        // 5. AUTOMATED RE-QUEUE TRIGGER: ROUTE BACK TO DOCTOR
        $vitalsStmt = $con->prepare("SELECT id FROM opd_vitals WHERE patient_id = :pid AND doctor_status = 'Sent to Lab' ORDER BY id DESC LIMIT 1");
        $vitalsStmt->execute([':pid' => $patient_id]);
        $target_vitals_id = $vitalsStmt->fetchColumn();

        if ($target_vitals_id) {
            $queueBackStmt = $con->prepare("UPDATE opd_vitals SET doctor_status = 'Pending' WHERE id = :vid");
            $queueBackStmt->execute([':vid' => $target_vitals_id]);
        }

        // CALL IMMUTABLE ACTIVITY LOG HOOK (Friendly Class 4 Description)
        $with_attachment_text = !empty($saved_file_web_url) ? " and uploaded a clean picture scan document sheet file" : "";
        $friendly_lab_msg = "The laboratory scientist tested the blood sample for " . $patient_name_string . ", typed the [" . $test_name_string . "] findings answer inside the computer box" . $with_attachment_text . ", and sent their paper card back to the doctor's room waiting line.";
        recordSystemActivityLog($con, 'Laboratory', 'UPDATE', $friendly_lab_msg);

        $con->commit();

        // 6. FETCH REFRESHED LAB QUEUE FOR REALTIME DATATABLES CONTAINER ENGINE REBUILDS
        $freshLabResponse = getPendingLabRequests($con);
        $refreshedLabQueueDataset = $freshLabResponse['data'];

        echo json_encode([
            'status'  => 'success',
            'message' => 'Laboratory test results compiled and signed cleanly. Document decrypted and saved successfully. Patient returned to doctor waiting room lines.',
            'data'    => $refreshedLabQueueDataset
        ]);
        exit;

    } catch (\Throwable $caughtException) {
        if (isset($con) && $con->inTransaction()) {
            $con->rollBack();
        }
        error_log( $caughtException->getMessage());
        echo json_encode([
            'status'  => 'error',
            'message' => $caughtException->getMessage()
        ]);
        exit;
    }
}

if (isset($_POST['manage_consultation']) && $_POST['manage_consultation'] === 'true') {
    try {
        if (!$con) throw new Exception('Database connection pipeline dead.');
        // 1. CAPTURE & SANITIZE FORM INPUT METRICS
        $patient_id         = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null; $vitals_id = isset($_POST['vitals_id']) ? (int)$_POST['vitals_id'] : null;
        $consultation_id    = isset($_POST['consultation_id']) ? (int)$_POST['consultation_id'] : null; $complaints = trim($_POST['complaints'] ?? '');     $diagnosis = trim($_POST['diagnosis'] ?? '');
        $treatment_complete = isset($_POST['treatment_complete']) && $_POST['treatment_complete'] === 'true';
        $doctor_id          = $_SESSION['user_id'] ?? 1; $consultation_fee   = 50.00;  
        // 2. RUN MANDATORY SAFETY CHECKS (ROUTING CHECKS FIRST)
        if (!$patient_id || !$vitals_id) throw new Exception('Invalid patient or vitals reference identifier.');
        // DYNAMIC SERVER-SIDE CONDITION VALIDATION MATCHING FRONTEND RULES
        if ($treatment_complete) {
            if (empty($complaints) || empty($diagnosis)) throw new Exception('Complaints and Diagnosis are strictly mandatory when treatment is marked complete.');
        } else {
            if (empty($complaints) && empty($lab_test_ids) && empty($drug_ids)) throw new Exception('Please fill out complaints, order a lab test, or confirm treatment.');
        }

        // 3. START DATABASE TRANSACTION MUTATION LOCKS
        $con->beginTransaction();
        
        // Fetch patient name immediately for our friendly activity logging text
        $ptFinder = $con->prepare("SELECT name FROM patients WHERE id = ? LIMIT 1");
        $ptFinder->execute([$patient_id]);
        $patient_name_string = $ptFinder->fetchColumn() ?: "A sick patient";

        // 4. WRITE OR UPDATE THE BASE CONSULTATION CASE NOTE RECORD
        $admission_status = trim($_POST['admission_status'] ?? 'Outpatient');
        $active_consultation_id = null;

        if ($consultation_id && $consultation_id > 0) {
            $verifyStmt = $con->prepare("SELECT id FROM consultations WHERE id = :cid LIMIT 1");
            $verifyStmt->execute([':cid' => $consultation_id]);
            
            if ($verifyStmt->fetch()) {
                // UPDATE PORTAL LAYER: Apply changes directly onto the pre-made row
                $updateStmt = $con->prepare("UPDATE consultations SET complaints = :complaints, diagnosis = :diagnosis, disposition = :disposition, doctor_id = :doc_id WHERE id = :cid");
                $updateStmt->execute([':complaints'  => $complaints,':diagnosis'=> $diagnosis,':disposition' => $admission_status,':doc_id'=> $doctor_id,':cid'=> $consultation_id]);
                $active_consultation_id = $consultation_id;
            }
        }
        // FALLBACK LAYER: Execute a fresh insertion query statement if empty row records
        if ($active_consultation_id === null) {
            $consultStmt = $con->prepare("INSERT INTO consultations (patient_id, vitals_id, complaints, diagnosis, disposition, doctor_id) VALUES (:pid, :vid, :complaints, :diagnosis, :disposition, :doc_id)");
            $consultStmt->execute([':pid'=> $patient_id, ':vid' => $vitals_id,':complaints'  => $complaints, ':diagnosis'   => $diagnosis, ':disposition' => $admission_status, ':doc_id' => $doctor_id]);
            $active_consultation_id = (int)$con->lastInsertId();
        }
        // 5. FETCH INSURANCE TYPE TO DETERMINE BILLING TREATMENT ELECTIONS
        $insStmt = $con->prepare("SELECT insurance_type FROM patients WHERE id = :pid LIMIT 1");
        $insStmt->execute([':pid' => $patient_id]);
        $patientProfile = $insStmt->fetch(PDO::FETCH_ASSOC);
        $insurance_type = $patientProfile['insurance_type'] ?? 'None';
        $conBill = addPatientBillingInvoice($con, $patient_id, (int) $active_consultation_id, $insurance_type, 'Consultation', $active_consultation_id, $consultation_fee);
        if ($conBill['status'] === 'error') throw new Exception($conBill['message']);
        
        // 6. PROCESS CONDITIONAL ROUTING: MULTIPLE LAB TESTS INTAKE PIPELINE 
        $lab_test_ids = isset($_POST['lab_test_ids']) && is_array($_POST['lab_test_ids']) ? $_POST['lab_test_ids'] : [];
        if (!empty($lab_test_ids)) {
            foreach ($lab_test_ids as $lab_id_raw) {
                $lab_id = (int)$lab_id_raw;
                if ($lab_id <= 0) continue;
                $labCatalogStmt = $con->prepare("SELECT cost FROM lab_catalog WHERE id = :id LIMIT 1");
                $labCatalogStmt->execute([':id' => $lab_id]);
                $labItem = $labCatalogStmt->fetch(PDO::FETCH_ASSOC);
                $lab_cost = $labItem['cost'] ?? 0.00;
                $labStmt = $con->prepare("INSERT INTO lab_requests (consultation_id, patient_id, lab_catalog_id, status) VALUES (:cid, :pid, :lab_id, 'Pending')");
                $labStmt->execute([':cid' => $active_consultation_id, ':pid' => $patient_id, ':lab_id' => $lab_id]);
                $new_lab_request_id = $con->lastInsertId();
                $labBill = addPatientBillingInvoice($con, $patient_id, (int) $active_consultation_id ,$insurance_type, 'Lab Fee', (int)$new_lab_request_id, (float)$lab_cost);
                if ($labBill['status'] === 'error') throw new Exception($labBill['message']);
            }
        }
        $drug_ids            = isset($_POST['drug_ids']) && is_array($_POST['drug_ids']) ? $_POST['drug_ids'] : [];
        $dosage_instructions = isset($_POST['dosage_instructions']) && is_array($_POST['dosage_instructions']) ? $_POST['dosage_instructions'] : [];
        if ($active_consultation_id && $active_consultation_id > 0) {
            $purgeInvoicesStmt = $con->prepare("DELETE FROM billing WHERE item_type = 'Drugs' AND consultation_id = :cid");
            $purgeInvoicesStmt->execute([':cid' => $active_consultation_id]);
            $purgePrescriptionsStmt = $con->prepare("DELETE FROM prescriptions WHERE consultation_id = :cid");
            $purgePrescriptionsStmt->execute([':cid' => $active_consultation_id]);
        }
        if (!empty($drug_ids)) {
            for ($i = 0; $i < count($drug_ids); $i++) {
                $d_id   = (int)$drug_ids[$i];
                $dosage = trim($dosage_instructions[$i] ?? '');
                if ($d_id <= 0 || empty($dosage)) continue;
                $drugStoreStmt = $con->prepare("SELECT selling_price FROM pharmacy_store WHERE id = :id LIMIT 1");
                $drugStoreStmt->execute([':id' => $d_id]);
                $drug_cost = $drugStoreStmt->fetchColumn() ?: 0.00;
                $prescStmt = $con->prepare("INSERT INTO prescriptions (consultation_id, patient_id, drug_id, dosage_instruction, status) VALUES (:cid, :pid, :drug_id, :dosage, 'Pending')");
                $prescStmt->execute([':cid'=> $active_consultation_id,':pid'=> $patient_id,':drug_id' => $d_id,':dosage'  => $dosage]);
                $new_prescription_id = (int)$con->lastInsertId();
                $drugBill = addPatientBillingInvoice($con, $patient_id, (int)$active_consultation_id, $insurance_type, 'Drugs', (int)$new_prescription_id, (float)$drug_cost);
                if ($drugBill['status'] === 'error') throw new Exception($drugBill['message']);
            }
        }
        
        // 8. UPDATE ACTIVE TRIAGE STATUS CONDITIONALLY BASED ON CHECKBOX
        if ($treatment_complete) {
            $statusStmt = $con->prepare("UPDATE opd_vitals SET doctor_status = 'Attended' WHERE id = :vid");
            $statusStmt->execute([':vid' => $vitals_id]);
            $outputMessage = "Consultation charts signed cleanly. Patient case file successfully closed.";
            
            // Friendly Class 4 Description text string
            $friendly_con_msg = "The doctor sat with " . $patient_name_string . " in the consultation room, checked their complaints, wrote the final treatment answer, and closed their card completely.";
        } else {
            $statusStmt = $con->prepare("UPDATE opd_vitals SET doctor_status = 'Sent to Lab' WHERE id = :vid");
            $statusStmt->execute([':vid' => $vitals_id]);
            $outputMessage = "Lab requests dispatched successfully. Patient folder moved to laboratory processing status.";
            
            // Friendly Class 4 Description text string
            $friendly_con_msg = "The doctor wrote a request note for " . $patient_name_string . " to go to the laboratory room to test their blood and check what is making them sick.";
        }
        
        // CALL IMMUTABLE ACTIVITY LOG HOOK INSIDE MUTATION CHAIN
        recordSystemActivityLog($con, 'Consultation', 'UPDATE', $friendly_con_msg);

        $con->commit();
        $queueResponse = getWaitingQueue($con);
        if ($queueResponse['status'] === 'error') throw new Exception($queueResponse['message']);
        $freshRemainingQueueDataset = $queueResponse['data'];
        // 10. REPLAY FINTECH SUCCESS FEEDBACK STRUCTURE 
        echo json_encode(['status'  => 'success','message' => $outputMessage, 'data'    => $freshRemainingQueueDataset]);
        exit;
    } catch (\Throwable $caughtException) {
        if (isset($con) && $con->inTransaction()) { $con->rollBack(); }
        error_log("Consultation error exception failure: " . $caughtException->getMessage());
        echo json_encode(['status' => 'error', 'message' => $caughtException->getMessage()]);
        exit;
    }
}


if (isset($_POST['record_vitals']) && $_POST['record_vitals'] === 'true') {

    try {
        if (!$con) throw new Exception('Database connection pipeline dead.');
        // Check if we are running an update operation or a fresh save entry
        $is_vitals_update = isset($_POST['edit_vitals']) && $_POST['edit_vitals'] === 'true';

        // 1. CAPTURE & SANITIZE TRIAGE FORM PROPERTIES
        $target_vitals_id = isset($_POST['vitals_id']) ? (int)$_POST['vitals_id'] : null;
        $patient_id       = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
        $bp               = trim($_POST['bp'] ?? '');
        $pulse            = isset($_POST['pulse']) ? (int)$_POST['pulse'] : 0;
        $temperature      = isset($_POST['temperature']) ? (float)$_POST['temperature'] : 0.0;
        $weight           = isset($_POST['weight']) ? (float)$_POST['weight'] : 0.0;
        $spo2             = isset($_POST['spo2']) ? (int)$_POST['spo2'] : 0;
        
        $recorded_by      = $_SESSION['user_id'] ?? 1;
        if (!$patient_id || empty($bp) || $pulse <= 0 || $temperature <= 0 || $weight <= 0 || $spo2 <= 0) throw new Exception('Compulsory clinical triage observation properties cannot be left blank.');
        if (!strpos($bp, '/')) throw new Exception('Blood Pressure entry format must follow standard Systolic/Diastolic expressions (e.g. 120/80).');
        
        // Fetch patient name for friendly audit logs
        $checkPatient = $con->prepare("SELECT name FROM patients WHERE id = :id LIMIT 1");
        $checkPatient->execute([':id' => $patient_id]);
        $patientRow = $checkPatient->fetch(PDO::FETCH_ASSOC);
        if (!$patientRow) throw new Exception('Target patient folder log trace routing dead.');
        $patient_name_string = $patientRow['name'];

        if ($is_vitals_update) {
            if (!$target_vitals_id) throw new Exception('Missing target triage record row modifier key code index.');
            
            $stmt = $con->prepare("UPDATE opd_vitals SET bp = :bp, pulse = :pulse, temperature = :temperature, weight = :weight, spo2 = :spo2, recorded_by = :recorded_by WHERE id = :id AND patient_id = :patient_id");
            $stmt->execute([':bp' => $bp, ':pulse' => $pulse, ':temperature' => $temperature, ':weight' => $weight, ':spo2' => $spo2, ':recorded_by' => $recorded_by, ':id' => $target_vitals_id, ':patient_id'  => $patient_id ]);
            
            // Friendly Class 4 Description text string
            $friendly_vitals_msg = "The nurse went back to change the hotness body numbers, weight, and blood pressure records for " . $patient_name_string . " to fix a mistake.";
            recordSystemActivityLog($con, 'Triage', 'UPDATE', $friendly_vitals_msg);
            
            $outputMessage = "Patient triage chart biometrics modified and updated successfully.";
        } else {
            $stmt = $con->prepare("INSERT INTO opd_vitals (patient_id, bp, pulse, temperature, weight, spo2, recorded_by) VALUES (:patient_id, :bp, :pulse, :temperature, :weight, :spo2, :recorded_by)");
            $stmt->execute([':patient_id'  => $patient_id,':bp' => $bp, ':pulse' => $pulse,':temperature' => $temperature, ':weight' => $weight, ':spo2' => $spo2,':recorded_by' => $recorded_by]);
            
            // Friendly Class 4 Description text string
            $friendly_vitals_msg = "The nurse used the clinic tools to check how hot " . $patient_name_string . "'s body is, how heavy they are, and how their blood is pumping at the front desk.";
            recordSystemActivityLog($con, 'Triage', 'INSERT', $friendly_vitals_msg);
            
            $outputMessage = "Patient vitals observation parameters logged inside medical archives successfully.";
        }
         // 5. CALL THE REUSABLE FUNCTION TO FETCH FRESH DATASET LIST
        $response = getAllPatients($con);
        if ($response['status'] === 'error') throw new Exception($response['message']);
        $refreshedPatientDataset = $response['data']; 
        // 4. RETURN SUCCESS STATUS ARRAY BACK TO FRONTEND AJAX
        echo json_encode([ 'status'  => 'success', 'message' => $outputMessage, 'data' => $refreshedPatientDataset]);
        exit;

    } catch (\Throwable $caughtException) {
        error_log("OPD Vitals engine processing anomaly exception caught: " . $caughtException->getMessage());
        echo json_encode(['status'  => 'error', 'message' => $caughtException->getMessage()]);
        exit;
    }
}


// Check for the master control flag
if (isset($_POST['manage_patient']) && $_POST['manage_patient'] === 'true') {

    try {
        if (!$con) throw new Exception('Database connection pipeline dead.');
        $is_update = isset($_POST['edit_patient']) && $_POST['edit_patient'] === 'true';

        // 1. SANITIZE AND CAPTURE SERIALIZED INPUT METRICS
        $target_id         = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
        $name              = trim($_POST['name'] ?? '');
        $is_student        = trim($_POST['is_student'] ?? 'No');
        $reference_number  = trim($_POST['reference_number'] ?? '');
        $date_of_birth     = trim($_POST['date_of_birth'] ?? '');
        $gender            = trim($_POST['gender'] ?? '');
        $marital_status    = trim($_POST['marital_status'] ?? 'Single');
        $contact           = trim($_POST['contact'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $relationship      = trim($_POST['relationship_to_contact'] ?? '');
        $ghana_card        = trim($_POST['ghana_card_number'] ?? '');
        $insurance_type    = trim($_POST['insurance_type'] ?? 'None');
        $insurance_number  = trim($_POST['insurance_number'] ?? '');
        
        // 2. SECURE DATA VALIDATION RUNS
        if (empty($name) || empty($date_of_birth) || empty($gender) || empty($contact) || empty($emergency_contact)) throw new Exception('Compulsory compliance entry properties cannot be left blank.');
        if ($is_student === 'Yes' && empty($reference_number)) throw new Exception('A valid student reference number is required for student accounts.');
        $dob_timestamp = strtotime($date_of_birth);
        if (!$dob_timestamp || $dob_timestamp > time()) throw new Exception('Invalid date of birth provided.');
        if ($is_student !== 'Yes') $reference_number = null;
        $ghana_card       = !empty($ghana_card) ? $ghana_card : null;
        $insurance_number = ($insurance_type !== 'None' && !empty($insurance_number)) ? $insurance_number : null;
        
        $con->beginTransaction();

        // Prevent duplicate student numbers
        if ($reference_number !== null) {
            $queryStr = $is_update ? "SELECT id FROM patients WHERE reference_number = :ref AND id != :id LIMIT 1" : "SELECT id FROM patients WHERE reference_number = :ref LIMIT 1";
            
            $checkRef = $con->prepare($queryStr);
            $bindParams = [':ref' => $reference_number];
            if ($is_update) $bindParams[':id'] = $target_id;
            
            $checkRef->execute($bindParams);
            if ($checkRef->fetch()) throw new Exception('A folder matching this Student Reference Number already exists.');
        }

        // Prevent duplicate Ghana Cards
        if ($ghana_card !== null) {
            $queryStr = $is_update ? "SELECT id FROM patients WHERE ghana_card_number = :card AND id != :id LIMIT 1" : "SELECT id FROM patients WHERE ghana_card_number = :card LIMIT 1";
            $checkCard = $con->prepare($queryStr);
            $bindParams = [':card' => $ghana_card];
            if ($is_update) $bindParams[':id'] = $target_id;
            
            $checkCard->execute($bindParams);
            if ($checkCard->fetch()) throw new Exception('This Ghana Card Number is already bound to another profile folder.');
        }

        // 4. BRANCH ENGINE: RUN UPDATE ROUTINE OR RUN NEW INSERT ROUTINE
        if ($is_update) {
            if (!$target_id) throw new Exception('Missing target patient modifier reference key index.'); 
            
            $updateStmt = $con->prepare("UPDATE patients SET name = :name, reference_number = :ref, date_of_birth = :dob, gender = :gender, marital_status = :marital, contact = :contact, emergency_contact = :emerg, relationship_to_contact = :rel, ghana_card_number = :card, insurance_type = :ins_type, insurance_number = :ins_num WHERE id = :id");
            $updateStmt->execute([':name' => $name, ':ref' => $reference_number, ':dob'=> $date_of_birth, ':gender' => $gender, ':marital'=> $marital_status,':contact'  => $contact, ':emerg'=> $emergency_contact, ':rel'=> $relationship, ':card'=> $ghana_card,':ins_type' => $insurance_type, ':ins_num'  => $insurance_number, ':id'=> $target_id]);
            
            // Friendly Class 4 Description text string
            $friendly_patient_msg = "The front desk worker changed the information for " . $name . " inside their patient file to fix mistakes in their phone number, home details, or family contact.";
            recordSystemActivityLog($con, 'Records', 'UPDATE', $friendly_patient_msg);
            
            $outputMessage = "Patient profile folder files modified and updated successfully.";

        } else {
            // Allocate a brand-new incremental folder token code string
            $folderResponse = generateUniqueFolderNumber($con);
            if ($folderResponse['status'] === 'error') throw new Exception($folderResponse['message']);
            $generatedFolderNumber = $folderResponse['data'];
            
            $insertStmt = $con->prepare("INSERT INTO patients (name, reference_number, folder_number, date_of_birth, gender, contact, emergency_contact, relationship_to_contact, ghana_card_number, marital_status, insurance_type, insurance_number) VALUES (:name, :ref, :folder, :dob, :gender, :contact, :emerg, :rel, :card, :marital, :ins_type, :ins_num)");
            $insertStmt->execute([':name' => $name, ':ref' => $reference_number, ':folder'   => $generatedFolderNumber,':dob' => $date_of_birth, ':gender'=> $gender,':contact'  => $contact, ':emerg' => $emergency_contact, ':rel'=> $relationship, ':card' => $ghana_card, ':marital'  => $marital_status, ':ins_type' => $insurance_type, ':ins_num'  => $insurance_number]);
            
            // Friendly Class 4 Description text string
            $identity_type_text = ($is_student === 'Yes') ? "a school student" : "a visitor";
            $friendly_patient_msg = "The front desk worker wrote down the name of a new person called " . $name . " who is " . $identity_type_text . ", and gave them a fresh clinic paper folder numbered " . $generatedFolderNumber . ".";
            recordSystemActivityLog($con, 'Records', 'INSERT', $friendly_patient_msg);
            
            $outputMessage = "Patient profile registered successfully. Assigned Folder allocation string: {$generatedFolderNumber}";
        }
        
        // Commit transaction data changes safely inside the MySQL logs
        $con->commit();

        // 5. CALL THE REUSABLE FUNCTION TO FETCH FRESH DATASET LIST
        $response = getAllPatients($con);
        if ($response['status'] === 'error') throw new Exception($response['message']);
        $refreshedPatientDataset = $response['data']; 
        
        // 6. RETURN SUCCESS RESPONSE PAYLOAD
        echo json_encode(['status'  => 'success','message' => $outputMessage, 'data'    => $refreshedPatientDataset]);
        exit;
        
    } catch (\Throwable $caughtException) {
        // Roll back transaction changes cleanly if pipeline errors happen mid-execution
        if (isset($con) && $con->inTransaction()) {
            $con->rollBack();
        }

        error_log("Patient folder write operational abort exception: " . $caughtException->getMessage());
        
        echo json_encode([
            'status'  => 'error',
            'message' => $caughtException->getMessage()
        ]);
        exit;
    }
}
    // =========================================================================
    // SYSTEM GATEWAY: SECURE CLINIC & STUDENT HEALTH PORTAL ACCREDITATION
    // =========================================================================
    if (isset($_POST['Systemlogin'], $_POST['enc_payload']) && $_POST['Systemlogin'] === 'true' && !empty($_POST['enc_payload'])) {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $encPayload = $_POST['enc_payload'];

            // 1. EXECUTE CRYPTOGRAPHIC DECRYPTION HOOK
            $decrypted = decodeLogin($encPayload, $login_encript);
            
            if ($decrypted === false || !is_array($decrypted)) { 
                throw new \Exception('Cryptographic Handshake Failure: Invalid or corrupted transaction payload token received.');
            } 
            error_log('decode: '. print_r($decrypted, true)); 
        
            // Extract, sanitize, and bind credential metrics safely from decrypted payload
            $usernameField = isset($decrypted['user_id']) ? trim((string)$decrypted['user_id']) : '';
            $passwordField = isset($decrypted['password']) ? (string)$decrypted['password'] : '';
            
            // 2. High-Frequency Boundary Inputs Validations
            if ($usernameField === '' || $passwordField === '') throw new Exception('Access Denied: Compulsory identification credentials cannot be blank.');
            
            // 3. RETRIEVE RECORD WITH WRITE MUTATION ROW LOCK (FOR UPDATE)
            // COMPLIANT SETUP: Using your upgraded schema column structures ('staff_id' and 'status')
            $authStmt = $con->prepare("SELECT id, staff_id, password, full_name, role, status FROM users WHERE staff_id = :staff_id LIMIT 1 FOR UPDATE");
            $authStmt->execute([':staff_id' => $usernameField]);
            $staffUserRow = $authStmt->fetch(PDO::FETCH_ASSOC);
            
            // ANTI-BRUTE FORCE WALL GUARD: Keep mismatch errors vague
            if (!$staffUserRow) throw new Exception('Invalid username or password. Please try again.');
            
            // 🧠 UPDATED PRIVILEGE COMPLIANCE BARRIER: Lock access using your new schema column 'status'
            if ($staffUserRow['status'] !== 'Active') throw new Exception("Access Revoked: Your account is currently suspended. Please contact the administrator.");
            
            // 4. VERIFY CRYPTOGRAPHIC SECURE PASS HASHES (NATIVE PASSWORD VERIFY LINK)
            if (!password_verify($passwordField, $staffUserRow['password'])) throw new Exception('Invalid username or password. Please try again.');
            
            // 5. SECURE STATE INITIALIZATION: WIPE AND GENERATE NEW SESSION IDs
            if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
            
            // Initialize global session memory matching your active validation parameters
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = (int)$staffUserRow['id'];
            $_SESSION['staff_id']       = (string)$staffUserRow['staff_id']; // Updated context key variable names
            $_SESSION['full_name']      = (string)$staffUserRow['full_name'];
            $_SESSION['role']           = (string)$staffUserRow['role'];
            $_SESSION['last_activity']  = time();
            
            $compiledFullName = $staffUserRow['full_name'];
            $successNarrativeText = "User accreditation cleared successfully. Personnel [{$compiledFullName}] logged into system under role profile authority [{$staffUserRow['role']}].";
            
            // 6. ✅ LOG AUTH ACTIVITY FOOTPRINT
            error_log("[SECURITY AUDIT - LOGIN] " . json_encode([
                'user_id'     => $staffUserRow['id'],
                'staff_id'    => $staffUserRow['staff_id'],
                'role'        => $staffUserRow['role'],
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
                'description' => $successNarrativeText
            ]));

            // CALL IMMUTABLE DATABASE ACTIVITY LOG HOOK (Friendly Class 4 Description)
            $friendly_login_msg = $compiledFullName . " typed their secret password keys on the login screen and opened their computer dashboard to work as a " . $staffUserRow['role'] . ".";
            recordSystemActivityLog($con, 'Auth', 'LOGIN', $friendly_login_msg);

            // Dynamic Role-Based Routing Architecture based on your Sidebar Layout configuration
            $redirect = '../staff/index.php';
            
            // 7. Dispatch success JSON array package back to frontend
            echo json_encode(['redirect' => $redirect,'status'   => 'success', 'message'  => "Welcome back, {$compiledFullName}! Authentication credentials successfully verified.", 'role'=> $staffUserRow['role']]);
            exit;

        } catch (\Throwable $innerException) {
            // UNIFIED RECOVERY CATCH GATEWAY
            $logMsg = " ❌ Security Accreditation Login Route Exception Failure: {$innerException->getMessage()} in {$innerException->getFile()} on line {$innerException->getLine()}";
            error_log($logMsg);
            
            echo json_encode([
                'status'  => 'error', 
                'message' => $innerException->getMessage()
            ]);
            exit;
        }
    }



 
  } catch (PDOException $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level PDOException: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[DB02] Database error occurred. ']);
    exit;
} catch (TypeError $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level TypeError: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[TE02] Type mismatch error occurred.']);
    exit;
} catch (Error $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level Error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[E02]System error occurred.' . $msg]);
    exit;
} catch (Exception $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level Exception: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level Throwable: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[TH02]Fatal system error occurred.']);
    exit;
} finally {
    // ✅ This code runs regardless of success or exception
    // For example, log the script execution
    // $msg = " ✅ Script execution completed at " . date('Y-m-d H:i:s');
    // error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    // error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);

    // Optional: clean up resources
    if (isset($con)) {
        $con = null; // Close PDO connection
    }
}
 
  
 // If no known action matched:
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown action or missing flag.']);
exit;  