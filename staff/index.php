<?php require_once 'includes/header.php';

// Safe authentication guard check
$user_role = $_SESSION['role'] ?? 'Doctor'; 
$staff_name = $_SESSION['full_name'] ?? 'Medical Professional';

// Initialize generic baseline counter buckets
$stats = ['title_1' => 'Metrics 1', 'count_1' => 0, 'title_2' => 'Metrics 2', 'count_2' => 0, 'title_3' => 'Metrics 3', 'count_3' => 0, 'accent' => 'primary'];
$queue_title = "General Waiting Queue";
$queue_data = [];

try {
    // SWITCH BOARD: Dynamically alter stats definitions and select queries by session role
    switch ($user_role) {
        case 'Doctor':
            $stats = ['title_1' => 'Awaiting Consultation', 'count_1' => (int)$con->query("SELECT COUNT(*) FROM opd_vitals WHERE doctor_status = 'Pending'") ->fetchColumn(), 'title_2' => 'Cases Attended Today', 'count_2' => (int)$con->query("SELECT COUNT(*) FROM opd_vitals WHERE doctor_status = 'Attended' AND DATE(updated_at) = CURRENT_DATE") ->fetchColumn(), 'title_3' => 'Total Inpatients', 'count_3' => (int)$con->query("SELECT COUNT(*) FROM ward_admissions WHERE admission_status = 'Admitted'") ->fetchColumn(), 'accent' => 'primary'];
            $queue_title = "Doctor's Consultation Waiting Board";
            $qStmt = $con->query("SELECT p.name, p.folder_number, v.id as tracking_id, 'Consultation' as description, DATE_FORMAT(v.created_at, '%h:%i %p') as time_logged FROM opd_vitals v JOIN patients p ON v.patient_id = p.id WHERE v.doctor_status = 'Pending' ORDER BY v.id ASC");
            $queue_data = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'Nurse':
            $stats = ['title_1' => 'Pending Triage (Vitals)', 'count_1' => (int)$con->query("SELECT COUNT(*) FROM patient_queue WHERE status = 'Waiting'") ->fetchColumn(), 'title_2' => 'Pending Injections', 'count_2' => (int)$con->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'Pending' AND dosage_instruction LIKE '[INJECTION]%'") ->fetchColumn(), 'title_3' => 'Occupied Beds', 'count_3' => (int)$con->query("SELECT COUNT(*) FROM ward_beds WHERE bed_status = 'Occupied'") ->fetchColumn(), 'accent' => 'danger'];
            $queue_title = "Triage & Injection Waiting Queue";
            $qStmt = $con->query("SELECT p.name, p.folder_number, pq.id as tracking_id, 'Vitals Triage' as description, DATE_FORMAT(pq.created_at, '%h:%i %p') as time_logged FROM patient_queue pq JOIN patients p ON pq.patient_id = p.id WHERE pq.status = 'Waiting' ORDER BY pq.id ASC");
            $queue_data = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'Pharmacist':
            $stats = ['title_1' => 'Pending Rx Fulfillments', 'count_1' => (int)$con->query("SELECT COUNT(DISTINCT consultation_id) FROM prescriptions WHERE status = 'Pending' AND dosage_instruction NOT LIKE '[INJECTION]%'") ->fetchColumn(), 'title_2' => 'Low Stock Items', 'count_2' => (int)$con->query("SELECT COUNT(*) FROM pharmacy_store WHERE quantity_in_store <= min_threshold_qty") ->fetchColumn(), 'title_3' => 'Pending Restock Requests', 'count_3' => (int)$con->query("SELECT COUNT(*) FROM pharmacy_requisitions WHERE status = 'Pending'") ->fetchColumn(), 'accent' => 'warning'];
            $queue_title = "Pharmacy Dispensing Waiting Board";
            $qStmt = $con->query("SELECT p.name, p.folder_number, pr.consultation_id as tracking_id, 'Prescription' as description, DATE_FORMAT(pr.created_at, '%h:%i %p') as time_logged FROM prescriptions pr JOIN patients p ON pr.patient_id = p.id WHERE pr.status = 'Pending' AND pr.dosage_instruction NOT LIKE '[INJECTION]%' GROUP BY pr.consultation_id, p.name, p.folder_number, pr.created_at ORDER BY pr.consultation_id ASC");
            $queue_data = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'Lab Technician':
            $stats = ['title_1' => 'Pending Lab Requests', 'count_1' => (int)$con->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'Pending'") ->fetchColumn(), 'title_2' => 'Completed Labs Today', 'count_2' => (int)$con->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'Completed' AND DATE(updated_at) = CURRENT_DATE") ->fetchColumn(), 'title_3' => 'Cataloged Tests', 'count_3' => (int)$con->query("SELECT COUNT(*) FROM lab_catalog") ->fetchColumn(), 'accent' => 'info'];
            $queue_title = "Laboratory Samples Collection Board";
            $qStmt = $con->query("SELECT p.name, p.folder_number, lr.id as tracking_id, lc.test_name as description, DATE_FORMAT(lr.created_at, '%h:%i %p') as time_logged FROM lab_requests lr JOIN patients p ON lr.patient_id = p.id JOIN lab_catalog lc ON lr.lab_catalog_id = lc.id WHERE lr.status = 'Pending' ORDER BY lr.id ASC");
            $queue_data = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'Cashier':
            $stats = ['title_1' => 'Unpaid Invoices', 'count_1' => (int)$con->query("SELECT COUNT(DISTINCT consultation_id) FROM billing WHERE payment_status = 'Unpaid'") ->fetchColumn(), 'title_2' => 'Total Inflow Today', 'count_2' => (float)$con->query("SELECT COALESCE(SUM(amount), 0.00) FROM cash_flow_ledger WHERE transaction_type = 'Inflow' AND DATE(created_at) = CURRENT_DATE") ->fetchColumn(), 'title_3' => 'Total Outflow Today', 'count_3' => (float)$con->query("SELECT COALESCE(SUM(amount), 0.00) FROM cash_flow_ledger WHERE transaction_type = 'Outflow' AND DATE(created_at) = CURRENT_DATE") ->fetchColumn(), 'accent' => 'success'];
            $queue_title = "Billing Ledger Accounts Desk";
            $qStmt = $con->query("SELECT p.name, p.folder_number, b.consultation_id as tracking_id, CONCAT(COUNT(b.id), ' Unpaid Item(s)') as description, DATE_FORMAT(b.created_at, '%h:%i %p') as time_logged FROM billing b JOIN patients p ON b.patient_id = p.id WHERE b.payment_status = 'Unpaid' GROUP BY b.consultation_id, p.name, p.folder_number, b.created_at ORDER BY b.consultation_id ASC");
            $queue_data = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default: // Admin or General Layout Fallback
            $stats = ['title_1' => 'Active Users Online', 'count_1' => (int)$con->query("SELECT COUNT(*) FROM users") ->fetchColumn(), 'title_2' => 'Total Patient Records', 'count_2' => (int)$con->query("SELECT COUNT(*) FROM patients") ->fetchColumn(), 'title_3' => 'System Database Health', 'count_3' => 100, 'accent' => 'secondary'];
            $queue_title = "Global System Activity Monitor";
            break;
    }
} catch (\Throwable $e) {
    error_log("Dashboard query failure: " . $e->getMessage());
}
?> 

 

<div class="container-fluid p-3">
    <!-- WELCOME SHEET HERO CARD WITH DYNAMIC ROLE LABELS -->
    <div class="card shadow-none border bg-white mb-3" style="border-radius:8px;">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="fw-bold text-dark mb-0">Welcome Back, <?php echo htmlspecialchars($staff_name); ?>!</h4>
                <small class="text-muted">Terminal Desk Role: <span class="badge bg-label-<?php echo $stats['accent']; ?> text-capitalize fw-bold"><?php echo htmlspecialchars($user_role); ?> Room</span></small>
            </div>
            <div class="text-end font-monospace small text-secondary">
                <i class="bx bx-calendar-event"></i> System Date: <?php echo date('d-M-Y'); ?> | <i class="bx bx-time"></i> Active
            </div>
        </div>
    </div>

    <!-- DYNAMIC TRIPLE STATS BLOCKS OVERLAY GRID -->
    <div class="row g-2 mb-3">
        <!-- Metric Box 1 -->
        <div class="col-md-4">
            <div class="card p-2 border shadow-none bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-<?php echo $stats['accent']; ?> p-1 rounded"><i class="bx bx-pulse fs-4"></i></div>
                <div>
                    <h6 class="mb-0 small text-muted font-monospace text-uppercase" style="font-size:0.68rem;"><?php echo $stats['title_1']; ?></h6>
                    <h4 class="mb-0 fw-bold font-monospace"><?php echo is_numeric($stats['count_1']) ? number_format($stats['count_1']) : $stats['count_1']; ?></h4>
                </div>
            </div>
        </div>
        <!-- Metric Box 2 -->
        <div class="col-md-4">
            <div class="card p-2 border shadow-none bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-<?php echo $stats['accent']; ?> p-1 rounded"><i class="bx bx-bar-chart-alt-2 fs-4"></i></div>
                <div>
                    <h6 class="mb-0 small text-muted font-monospace text-uppercase" style="font-size:0.68rem;"><?php echo $stats['title_2']; ?></h6>
                    <h4 class="mb-0 fw-bold font-monospace"><?php echo is_numeric($stats['count_2']) ? (is_float($stats['count_2']) ? 'GHS ' . number_format($stats['count_2'], 2) : number_format($stats['count_2'])) : $stats['count_2']; ?></h4>
                </div>
            </div>
        </div>
        <!-- Metric Box 3 -->
        <div class="col-md-4">
            <div class="card p-2 border shadow-none bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-<?php echo $stats['accent']; ?> p-1 rounded"><i class="bx bx-grid-alt fs-4"></i></div>
                <div>
                    <h6 class="mb-0 small text-muted font-monospace text-uppercase" style="font-size:0.68rem;"><?php echo $stats['title_3']; ?></h6>
                    <h4 class="mb-0 fw-bold font-monospace"><?php echo is_numeric($stats['count_3']) ? (is_float($stats['count_3']) ? 'GHS ' . number_format($stats['count_3'], 2) : number_format($stats['count_3'])) : $stats['count_3']; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- DYNAMIC WAITING QUEUE DIRECT LIST PANEL -->
    <div class="card shadow-none border">
        <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-navigation text-<?php echo $stats['accent']; ?> me-1"></i> <?php echo htmlspecialchars($queue_title); ?></h6>
            <span class="badge bg-label-<?php echo $stats['accent']; ?> font-monospace"><?php echo count($queue_data); ?> Pending Entry Lines</span>
        </div>
        <div class="card-body p-2">
            <?php if (empty($queue_data)): ?>
                <div class="text-center text-muted py-5 small"><i class="bx bx-check-circle text-success fs-2 d-block mb-1"></i> All clear! No patient entries waiting under your department checklist.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 font-monospace text-xs" style="font-size: 0.76rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-2">Patient Name Target</th>
                                <th>Folder Code</th>
                                <th>Status/Assignment Description</th>
                                <th>Arrival Clock</th>
                                <th class="text-center">Action Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queue_data as $row): ?>
                                <tr>
                                    <td class="ps-2 fw-semibold text-dark"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td class="text-secondary fw-bold"><?php echo htmlspecialchars($row['folder_number']); ?></td>
                                    <td><span class="badge bg-label-secondary border py-0 px-1 text-dark"><?php echo htmlspecialchars($row['description']); ?></span></td>
                                    <td class="text-muted"><i class="bx bx-time text-xs"></i> <?php echo htmlspecialchars($row['time_logged']); ?></td>
                                    <td class="text-center">
                                        <!-- DYNAMIC ROUTING WORKSPACE BUTTONS BASED ON THE ROLE -->
                                        <?php if ($user_role === 'Doctor'): ?>
                                            <a href="consulting_room.php" class="btn btn-xs btn-primary py-0.5 px-2 fw-bold"><i class="bx bx-folder-open me-0.5"></i> Attend Patient</a>
                                        <?php elseif ($user_role === 'Nurse'): ?>
                                            <a href="triage_station.php" class="btn btn-xs btn-danger py-0.5 px-2 fw-bold"><i class="bx bx-heart me-0.5"></i> Take Vitals</a>
                                        <?php elseif ($user_role === 'Pharmacist'): ?>
                                            <a href="pharmacy_dispense.php" class="btn btn-xs btn-warning py-0.5 px-2 fw-bold"><i class="bx bx-capsule me-0.5"></i> Open Rx Basket</a>
                                        <?php elseif ($user_role === 'Lab Technician'): ?>
                                            <a href="laboratory_suite.php" class="btn btn-xs btn-info py-0.5 px-2 fw-bold"><i class="bx bx-test-tube me-0.5"></i> Run Test Samples</a>
                                        <?php elseif ($user_role === 'Cashier'): ?>
                                            <a href="cashier_billing.php" class="btn btn-xs btn-success py-0.5 px-2 fw-bold"><i class="bx bx-credit-card me-0.5"></i> Checkout Invoice</a>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Viewer Mode Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
 
<?php require_once 'includes/footer.php';?>
 