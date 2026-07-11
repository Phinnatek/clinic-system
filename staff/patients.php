<?php
try {
    require_once 'includes/header.php'; 
    if (!$con) throw new Exception('Database connection failed');

    // Call the updated status function matrix
    $response = getAllPatients($con);

    if ($response['status'] === 'error' && empty($response['data'])) throw new Exception($response['message']);
    $patientsList = $response['data'];
        
    // Define array lookup configurations for form generation loops
    $genderOptions = ['Male', 'Female'];
    $maritalOptions = ['Single', 'Married', 'Divorced', 'Widowed'];
    $insuranceOptions = [
        'None'    => 'None (Cash-Paying)',
        'Public'  => 'Public (NHIS)',
        'Private' => 'Private Health'
    ];

} catch (Throwable $e) {
    error_log('Patient List Page Initialization Exception: ' . $e->getMessage());
    echo '<div class="alert alert-danger m-4">System Initializer Crash: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <!-- Header Component Section Header Area -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Records Desk /</span> Patient Profiles</h4>
                <p class="text-muted small mb-0">Manage student medical folder logs and healthcare registrations</p>
            </div>
            <?php if(isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Records'])): ?>
            <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                <i class="bx bx-user-plus me-2 fs-5"></i> Register New Patient
            </button>
            <?php endif; ?>
        </div>

        <!-- Main Data Presentation Core Component Card -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                   <!-- MANDATORY REQUIREMENTS ID SPECIFICATION TO HOOK DATATABLES PLUGIN ENGINE -->
                    <table id="DataTable" class="table table-striped table-hover dt-responsive nowrap w-100 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>S/N</th>
                                <th>Folder No.</th>
                                <th>Full Name</th>
                                <th>Gender</th>
                                <th>Tel:</th>
                                <th>Age</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($patientsList)): ?>
                                <?php $sr=1; foreach ($patientsList as $patient): 
                                    // Encode the raw associative row payload matrix directly into a safe transport token string
                                    $encodedData = base64_encode(json_encode($patient));
                                ?>
                                    <tr>
                                        <td><?php echo $sr++; ?></td>
                                        <td class="fw-bold text-primary"><?php echo htmlspecialchars($patient['folder_number']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($patient['gender']); ?></span></td>
                                        <td>
                                            <?php echo htmlspecialchars($patient['contact']); ?>
                                            <br><small class="text-muted">Emerg: <?php echo htmlspecialchars($patient['emergency_contact']); ?></small>
                                        </td>
                                        <td><?php echo (int)$patient['calculated_age']; ?> yrs</td>
                                        <td class="text-center align-middle">
                                            <div class="d-flex align-items-center justify-content-center gap-1">

                                                <!-- MORE BUTTON (Sneat Info Tone) -->
                                                <button type="button" class="btn btn-sm btn-outline-info btn-view-more" data-payload="<?php echo $encodedData; ?>" title="See More Info">
                                                    <i class="bx bx-show-alt me-1"></i> More
                                                </button>

                                                <!-- EDIT BUTTON (Sneat Warning Tone) -->
                                                <button type="button" class="btn btn-sm btn-outline-warning btn-edit-patient" data-payload="<?php echo $encodedData; ?>" title="Edit Patient Info">
                                                    <i class="bx bx-edit-alt me-1"></i> Edit
                                                </button>

                                                <!-- VITALS BUTTON (Sneat Danger Tone) -->
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-capture-vitals" data-payload="<?php echo $encodedData; ?>" title="Check Heart & Weight">
                                                    <i class="bx bx-pulse me-1"></i> Vitals 
                                                </button>

                                                <!-- DOCTOR CONSULT BUTTON (Sneat Solid Primary) -->
                                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Doctor'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary btn-consulting-room" data-payload="<?php echo $encodedData; ?>" title="Doctor Room">
                                                        <i class="bx bx-plus-medical me-1"></i> Consult
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ========================================== -->
<!-- MODAL: FINTECH EXTENDED KYC DISPLAY PANEL   -->
<!-- ========================================== -->
<div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header bg-light border-bottom px-4 py-3">
                <div>
                    <span class="text-xs text-uppercase tracking-wider text-muted fw-bold" style="font-size:0.68rem; letter-spacing: 0.05em;">Extended Health Registry Record</span>
                    <h5 class="modal-title fw-bold text-dark mt-1" id="m_display_name">Patient Folder Information</h5>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #fafbfc;">
                
                <!-- Section 1: Academic & Demographic Metadata -->
                <div class="mb-4">
                    <h6 class="text-primary fw-bold small text-uppercase mb-2" style="font-size:0.75rem; letter-spacing: 0.05em;">Identification Matrix</h6>
                    <div class="bg-white p-3 border rounded-3 d-flex flex-column gap-2 shadow-sm">
                        <div class="d-flex justify-content-between"><span class="text-muted small">Folder Allocation:</span> <span class="fw-bold text-dark" id="m_folder"></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Student Ref No:</span> <span class="fw-bold text-dark" id="m_student_ref"></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Ghana Card ID:</span> <span class="fw-bold text-dark" id="m_ghana_card"></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Date of Birth:</span> <span class="fw-bold text-dark" id="m_dob"></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Civil Status:</span> <span class="fw-bold text-dark" id="m_marital"></span></div>
                    </div>
                </div>

                <!-- Section 2: Telecom Communications Logs -->
                <div class="mb-4">
                    <h6 class="text-primary fw-bold small text-uppercase mb-2" style="font-size:0.75rem; letter-spacing: 0.05em;">Communications & Emergency Kin</h6>
                    <div class="bg-white p-3 border rounded-3 d-flex flex-column gap-2 shadow-sm">
                        <div class="d-flex justify-content-between"><span class="text-muted small">Primary Mobile:</span> <span class="fw-bold text-dark" id="m_contact"></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Emergency Kin Contact:</span> <span class="fw-bold text-danger" id="m_emergency"></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted small">Kin Relationship:</span> <span class="fw-bold text-dark" id="m_relationship"></span></div>
                    </div>
                </div>

                <!-- Section 3: Insurance Coverage Underwriting -->
                <div>
                    <h6 class="text-primary fw-bold small text-uppercase mb-2" style="font-size:0.75rem; letter-spacing: 0.05em;">Healthcare Underwriting Privilege</h6>
                    <div class="bg-white p-3 border rounded-3 shadow-sm" id="m_insurance_box">
                        <!-- Dynamic Insurance Badge Injection Node Area -->
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-1 bg-light">
                <button type="button" class="btn btn-outline-secondary w-100 py-2" data-bs-dismiss="modal" style="border-radius:8px; font-weight:600;">Dismiss View</button>
            </div>
        </div>
    </div>
</div>

 <style>
  /* Fintech Aesthetic Overrides */
  .modal-content-fintech {
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.08);
    border-radius: 16px;
    background: #ffffff;
  }
  .step-pill {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    padding: 6px 16px;
    border-radius: 20px;
    background: #f1f5f9;
    color: #64748b;
    transition: all 0.3s ease;
  }
  .step-pill.active {
    background: #e0f2fe;
    color: #0369a1;
  }
  .step-pill.completed {
    background: #dcfce7;
    color: #15803d;
  }
  .form-group-premium {
    position: relative;
  }
  .form-group-premium label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
  }
  .form-control-premium {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.9rem;
    color: #0f172a;
    background-color: #f8fafc;
    transition: all 0.2s ease-in-out;
  }
  .form-control-premium:focus {
    background-color: #ffffff;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
    outline: none;
  }
  .form-control-premium:disabled {
    background-color: #f1f5f9;
    color: #94a3b8;
    border-color: #e2e8f0;
  }
  .input-icon-wrapper {
    position: relative;
  }
  .input-icon-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.15rem;
  }
  .input-icon-wrapper .form-control-premium {
    padding-left: 38px;
  }
  .segmented-control {
    display: flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
  }
  .segmented-control input[type="radio"] {
    display: none;
  }
  .segmented-control label {
    flex: 1;
    text-align: center;
    padding: 8px 12px;
    margin: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .segmented-control input[type="radio"]:checked + label {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.05);
  }
  .wizard-step {
    display: none;
    animation: fadeInStep 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }
  .wizard-step.active {
    display: block;
  }
  @keyframes fadeInStep {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
<!-- ========================================== -->
<!-- MODAL: ADD NEW PATIENT REGISTRATION FORM -->
<!-- ========================================== -->
<div class="modal fade" id="addPatientModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-content-fintech">
            
            <!-- Fintech Header & Step Progress Bar Tracking Matrix -->
            <div class="modal-header border-0 px-4 pt-4 pb-2 d-flex flex-column align-items-start">
                <div class="d-flex justify-content-between align-items-center w-100 mb-3">
                    <div>
                        <span class="text-xs text-uppercase tracking-wider text-primary fw-bold" style="font-size:0.72rem;">KYC Clearance Portal</span>
                        <h4 class="fw-bold text-slate-900 mb-0 mt-1" style="color: #0f172a;">Enroll Medical Identity</h4>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Progress Indicators Matrix -->
                <div class="d-flex gap-2 w-100 border-bottom pb-3">
                    <span class="step-pill active" id="pill-step-1">01 BASE BIO</span>
                    <span class="step-pill" id="pill-step-2">02 KIN & ID</span>
                    <span class="step-pill" id="pill-step-3">03 UNDERWRITING</span>
                </div>
            </div>

            <form id="patientRegistrationForm" method="POST" autocomplete="off">
                <div class="modal-body px-4 py-3">
                    
                    <!-- ========================================== -->
                    <!-- STEP 1: BASE BIOGRAPHICAL DATA -->
                    <!-- ========================================== -->
                    <div class="wizard-step active" id="wizard-step-1">
                        <div class="row g-3">
                            <div class="col-md-12 form-group-premium">
                                <label><i class="bx bx-user-circle"></i> Full Legal Patient Name</label>
                                <input type="text" name="name" class="form-control form-control-premium" placeholder="Enter first, middle, and last name" required />
                            </div>
                            
                            <div class="col-md-6 form-group-premium">
                                <label><i class="bx bx-buildings"></i> Is Patient a Student?</label>
                                <div class="segmented-control">
                                    <input type="radio" id="is_student_no" name="is_student" value="No" checked>
                                    <label for="is_student_no">No (External / Staff)</label>
                                    
                                    <input type="radio" id="is_student_yes" name="is_student" value="Yes">
                                    <label for="is_student_yes">Yes (Student)</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 form-group-premium opacity-0 transition-all duration-300" id="studentRefWrapper" style="pointer-events: none;">
                                <label><i class="bx bx-id-card"></i> Student Reference Number</label>
                                <input type="text" id="reference_number" name="reference_number" class="form-control form-control-premium" placeholder="e.g., 20491823" />
                            </div>

                            <div class="col-md-4 form-group-premium">
                                <label><i class="bx bx-calendar"></i> Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control form-control-premium" required />
                            </div>
                            <div class="col-md-4 form-group-premium">
                                <label><i class="bx bx-git-repo-forked"></i> Biological gender</label>
                                <select name="gender" class="form-select form-control-premium" required>
                                    <option value="" selected disabled>Select...</option>
                                    <?php foreach ($genderOptions as $gender): ?>
                                        <option value="<?php echo $gender; ?>"><?php echo $gender; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-group-premium">
                                <label><i class="bx bx-heart"></i> Marital Status</label>
                                <select name="marital_status" class="form-select form-control-premium" required>
                                    <?php foreach ($maritalOptions as $status): ?>
                                        <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 2: NEXT OF KIN & GOVERNMENT IDENTITY -->
                    <!-- ========================================== -->
                    <div class="wizard-step" id="wizard-step-2">
                        <div class="row g-3">
                            <div class="col-md-4 form-group-premium">
                                <label><i class="bx bx-phone"></i> Primary Mobile Number</label>
                                <input type="tel" name="contact" class="form-control form-control-premium" placeholder="024XXXXXXX" required />
                            </div>
                            <div class="col-md-4 form-group-premium">
                                <label><i class="bx bx-phone-call"></i> Emergency Next of Kin</label>
                                <input type="tel" name="emergency_contact" class="form-control form-control-premium" placeholder="055XXXXXXX" required />
                            </div>
                            <div class="col-md-4 form-group-premium">
                                <label><i class="bx bx-group"></i> Relationship to Contact</label>
                                <input type="text" name="relationship_to_contact" class="form-control form-control-premium" placeholder="e.g., Parent" required />
                            </div>

                            <div class="col-md-12 form-group-premium">
                                <label><i class="bx bx-fingerprint"></i> Ghana Card Number (ECOWAS ID)</label>
                                <input type="text" name="ghana_card_number" class="form-control form-control-premium" placeholder="GHA-XXXXXXXXX-X" />
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- STEP 3: HEALTHCARE UNDERWRITING MATRIX -->
                    <!-- ========================================== -->
                    <div class="wizard-step" id="wizard-step-3">
                        <div class="row g-3">
                            <div class="col-md-6 form-group-premium">
                                <label><i class="bx bx-shield-quarter"></i> Insurance Category Matrix</label>
                                <select name="insurance_type" id="insurance_type" class="form-select form-control-premium" required>
                                    <?php foreach ($insuranceOptions as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 form-group-premium">
                                <label><i class="bx bx-hash"></i> Insurance Policy Tracking String</label>
                                <input type="text" name="insurance_number" id="insurance_number" class="form-control form-control-premium" placeholder="No Policy Applied" disabled />
                            </div>
                        </div>
                    </div>

                </div>
                
                <!-- Unified Wizard Action Engine Button Controls -->
                <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-light px-4 py-2" id="btn-wizard-prev" style="border: 1px solid #cbd5e1; display: none;">Back</button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary px-4 py-2" id="btn-wizard-next">Continue</button>
                        <button type="submit" class="btn btn-success px-4 py-2" id="btn-wizard-submit" style="display: none;">Complete Secure Enrollment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ========================================== -->
<!-- MODAL: ADVANCED SNEAT SPLIT TRIAGE PANEL   -->
<!-- ========================================== -->
<div class="modal fade" id="opdVitalsModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Changed to lg for split layout -->
        <div class="modal-content border-0">
            <div class="modal-header border-bottom">
                <div>
                    <small class="text-danger fw-bold text-uppercase d-block mb-1">OPD Triage Station</small>
                    <h5 class="modal-title fw-bold text-dark" id="vitals_patient_title">Record Vital Signs</h5>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="row g-4">
                    
                    <!-- LEFT COLUMN: TODAY'S DATA INTAKE FORM (Visible to All Roles) -->
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold text-primary mb-3"><i class="bx bx-edit"></i> Today's Observations Form</h6>
                        <form id="saveVitalsForm" method="POST" autocomplete="off">
                            <input type="hidden" name="patient_id" id="vitals_patient_id" />
                            <input type="hidden" name="record_vitals" value="true" />
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Blood Pressure (BP)</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="bx bx-heart text-danger"></i></span>
                                        <input type="text" name="bp" id="c_bp_field" class="form-control" placeholder="e.g., 120/80" required  maxlength="7" autocomplete="off"/>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Pulse Rate</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="bx bx-pulse text-danger"></i></span>
                                        <input type="number" name="pulse" class="form-control" placeholder="72" required />
                                        <span class="input-group-text">BPM</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Temp</label>
                                    <input type="number" step="0.1" name="temperature" class="form-control" placeholder="36.5" required />
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Weight</label>
                                    <input type="number" step="0.1" name="weight" class="form-control" placeholder="65.0" required />
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">SpO2</label>
                                    <input type="number" name="spo2" class="form-control" placeholder="98" required />
                                </div>
                                <div class="col-12 text-end pt-2">
                                    <button type="submit" class="btn btn-danger w-100 fw-semibold">Commit Today's Metrics</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- RIGHT COLUMN: HISTORICAL RECORD ACCORDION (DOCTOR ONLY PORTAL GATEWAY) -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-secondary mb-3"><i class="bx bx-history"></i> Medical Chart History</h6>
                        
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Doctor'): ?>
                            <!-- Dynamic Accordion Target Node Area -->
                            <div class="accordion accordion-header-primary" id="vitalsHistoryAccordion">
                                <div class="text-center text-muted py-4" id="vitals_history_loader">
                                    <div class="spinner-border spinner-border-sm text-secondary mb-2"></div>
                                    <p class="small mb-0">Reading patient charts history matrix...</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Secure lock screen banner block displayed to Nurses / Records clerks -->
                            <div class="alert alert-light border d-flex align-items-center gap-2 mt-2" style="border-radius:8px;">
                                <i class="bx bx-lock-alt text-warning fs-4"></i>
                                <div class="small text-muted">Access Restricted. Medical timeline data charts can only be viewed under active Doctor login authority profiles.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer bg-light border-top-0 py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Dismiss View</button>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php';?>

<script>
    $(document).ready(function() {
  // ====================================================================
// SECURE INLINE BLOOD PRESSURE (BP) AUTO-PADDING CHAR-MASK CONTROLLER
// ====================================================================
$(document).on('input keyup', '#c_bp_field', function(e) {
    let inputField = $(this);
    let rawValue = inputField.val().replace(/[^0-9/]/g, ''); // Strip illegal text
    
    // A. Direct exit check on backspace to protect native deletion workflows
    if (e.type === 'keyup' && e.key === 'Backspace') {
        return; 
    }

    // Split entry to evaluate Systolic (Left) and Diastolic (Right) chambers independently
    let parts = rawValue.split('/');
    let systolic = parts[0] || '';
    let diastolic = parts[1] !== undefined ? parts[1] : null;

    // B. PROCESS SYSTOLIC SIDE (Left side before the slash)
    if (diastolic === null) {
        // Trigger condition: If they manually type '/' after exactly 2 digits, pad with a leading 0
        if (e.type === 'keyup' && e.key === '/' && systolic.length === 2) {
            systolic = '0' + systolic;
            rawValue = systolic + '/';
        }
        // Trigger condition: If they type a 4th consecutive digit without a slash, format the prefix
        else if (systolic.length === 4) {
            let lastTypedDigit = systolic.charAt(3);
            systolic = systolic.substring(0, 3);
            diastolic = lastTypedDigit;
            rawValue = systolic + '/' + diastolic;
        }
    } 
    // C. PROCESS DIASTOLIC SIDE (Right side after the slash has been initiated)
    else {
        // Strict boundary: Diastolic reading cannot exceed a maximum length of 3 digits
        if (diastolic.length > 3) {
            diastolic = diastolic.substring(0, 3);
        }
        rawValue = systolic + '/' + diastolic;
    }

    // D. CHRONO-SUBMIT BLUR CLEANUP: Zero-pads the diastolic side automatically on blur/completion
    // We bind a temporary single-execution listener to catch when focus leaves or field maxes out
    if (diastolic !== null && diastolic.length === 2 && rawValue.length === 6) {
        // If they pause or finish typing exactly 2 diastolic digits, pad it cleanly to 3 digits
        inputField.off('blur.padding').on('blur.padding', function() {
            let latestVal = $(this).val().split('/');
            if (latestVal[1] && latestVal[1].length === 2) {
                $(this).val(latestVal[0] + '/0' + latestVal[1]);
            }
        });
    }

    // E. Hard cap maximum threshold limit check to guard data arrays structure
    if (rawValue.length > 7) {
        rawValue = rawValue.substring(0, 7);
    }

    // Write the clean, padded, masked value right back into the form canvas box
    inputField.val(rawValue);
});


 

// ==========================================
// 1. OPEN TRIAGE MODAL AND INSTANTLY GENERATE HISTORY ACCORDION FROM PAYLOAD
// ==========================================
$('#DataTable').on('click', '.btn-capture-vitals', function(e) {
    e.preventDefault();
    
    var rawPayload = $(this).attr('data-payload');
    if (!rawPayload) return;

    try {
        var decodedJSON = atob(rawPayload);
        var patient = JSON.parse(decodedJSON);

        // Reset previous form inputs first
        var form = $('#saveVitalsForm')[0];
        if (form) form.reset();
        
        // Remove old hidden update input flags
        $('#saveVitalsForm').find('input[name="edit_vitals"]').remove();
        $('#saveVitalsForm').find('input[name="vitals_id"]').remove();

        // Bind core reference parameters
        $('#vitals_patient_id').val(patient.id);
        $('#vitals_patient_title').html('Triage Module: ' + $('<div>').text(patient.name).html() + ' <span class="text-muted">(' + patient.folder_number + ')</span>');
        
        // Extract his total vitals list from our database query array properties
        var historyList = patient.vitals_history || [];

        // CHECK IF THE PATIENT HAS A TRIAGE LOG TAKEN TODAY FOR PRE-FILLS
if (historyList && historyList.length > 0) {
    var todaysLatestRecord = null;
    var currentDateString = new Date().toDateString();

    console.log('Total history records found: ', historyList.length);

    // Loop through all records to isolate entries matching today
    historyList.forEach(function(record) {
        if (!record.created_at) return;

        var recordDateString = new Date(record.created_at).toDateString();
        
        if (recordDateString === currentDateString) {
            // If it is the first record matching today, store it
            if (todaysLatestRecord === null) {
                todaysLatestRecord = record;
            } else {
                // If multiple records exist for today, pick the newest one based on ID or Timestamp
                var existingTime = new Date(todaysLatestRecord.created_at).getTime();
                var newTime = new Date(record.created_at).getTime();
                
                // Compare by timestamp (or change to record.id if sorting by incremental primary keys)
                if (newTime > existingTime) {
                    todaysLatestRecord = record;
                }
            }
        }
    });

    // If an active record matching today was found, run the pre-fill UI script
    if (todaysLatestRecord !== null) {
        console.log('Isolated Today\'s Latest Record: ', todaysLatestRecord);
        
        var $form = $('#saveVitalsForm');
        
        // Populate the entry fields immediately for easy modifications
        $form.find('input[name="bp"]').val(todaysLatestRecord.bp);
        $form.find('input[name="pulse"]').val(todaysLatestRecord.pulse);
        $form.find('input[name="temperature"]').val(todaysLatestRecord.temperature);
        $form.find('input[name="weight"]').val(todaysLatestRecord.weight);
        $form.find('input[name="spo2"]').val(todaysLatestRecord.spo2);
        
        // Avoid duplicate inputs: Clean previous hidden control fields before appending fresh ones
        $form.find('input[name="vitals_id"], input[name="edit_vitals"]').remove();
        
        // Append secure state control hooks to guide the backend update process
        $form.append(`<input type="hidden" name="vitals_id" value="${todaysLatestRecord.id}" />`);
        $form.append(`<input type="hidden" name="edit_vitals" value="true" />`);
    } else {
        console.log('No triage records found matching today\'s date.');
        // Clean old hidden edit inputs to ensure the form behaves as a clean creation request
        $('#saveVitalsForm').find('input[name="vitals_id"], input[name="edit_vitals"]').remove();
    }
}


        // GENERATE ACCORDION OFFLINE (EXCLUSIVE DOCTOR ACCREDITED GATEWAY ONLY)
        var currentUserRole = '<?php echo $_SESSION["role"] ?? ""; ?>';
        if (currentUserRole === 'Doctor') {
            var $accordion = $('#vitalsHistoryAccordion');
            $accordion.empty();
            
            if (historyList.length > 0) {
                var accordionMarkup = '';
                
                historyList.forEach(function(item, index) {
                    var showClass = (index === 0) ? 'show' : '';
                    var collapsedClass = (index === 0) ? '' : 'collapsed';
                    
                    // Format date cleanly to Ghanaian time standards
                    var formattedDate = new Date(item.created_at).toLocaleString('en-GH', { 
                        day: 'numeric', 
                        month: 'short', 
                        year: 'numeric', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });

                    accordionMarkup += `
                        <div class="card accordion-item border shadow-none mb-2" style="border-radius:6px;">
                            <h2 class="accordion-header" id="heading-${item.id}">
                                <button type="button" class="accordion-button ${collapsedClass} py-2.5 small fw-bold text-dark" data-bs-toggle="collapse" data-bs-target="#collapse-${item.id}" aria-expanded="${index === 0}" aria-controls="collapse-${item.id}">
                                    <i class="bx bx-calendar text-primary me-2"></i> ${formattedDate}
                                </button>
                            </h2>
                            <div id="collapse-${item.id}" class="accordion-collapse collapse ${showClass}" aria-labelledby="heading-${item.id}" data-bs-parent="#vitalsHistoryAccordion">
                                <div class="accordion-body px-3 py-2 bg-light-subtle">
                                    <div class="row g-2 text-dark small">
                                        <div class="col-6"><b>BP:</b> ${$('<div>').text(item.bp).html()}</div>
                                        <div class="col-6"><b>Pulse:</b> ${item.pulse} BPM</div>
                                        <div class="col-4"><b>Temp:</b> ${item.temperature} °C</div>
                                        <div class="col-4"><b>Weight:</b> ${item.weight} Kg</div>
                                        <div class="col-4"><b>SpO2:</b> ${item.spo2} %</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $accordion.append(accordionMarkup);
            } else {
                $accordion.append('<div class="alert alert-light text-center small text-muted border py-3"><i class="bx bx-folder-open d-block fs-4 mb-1"></i> No previous triage observation charts logged.</div>');
            }
        }

        // Open the Sneat modal layout smoothly and instantly
        $('#opdVitalsModal').modal('show');

    } catch (failure) {
        console.error("Vitals parsing handoff crash: ", failure);
        Swal.fire('Payload Error', 'Unable to parse matching patient file parameters.', 'error');
    }
});


// ==========================================
// 2. AJAX TRIAGE OBSERVATION FORM SUBMISSION ROUTINE
// ==========================================
$('#saveVitalsForm').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    var isEditVitals = $(form).find('input[name="edit_vitals"]').val() === 'true';
    
    let activeBpValue = $('#c_bp_field').val().trim();
    
    // Safety check verification pattern: must look like 3 digits, a slash, and 2-3 diastolic digits
    if (activeBpValue.length > 0 && !/^\d{2,3}\/\d{2,3}$/.test(activeBpValue)) {
        e.preventDefault();
        $('#c_bp_field').addClass('is-invalid');
        
        Swal.fire({
            title: 'Invalid BP Format',
            text: 'Please enter a clinically realistic Blood Pressure reading (e.g., 120/80 or 130/100).',
            icon: 'error',
            confirmButtonColor: '#ff3e1d'
        });
        return false;
    }
    
    $('#c_bp_field').removeClass('is-invalid');
    Swal.fire({
        title: isEditVitals ? 'Update Triage Data?' : 'Commit Triage Data?',
        text: 'Save these recorded biometric readings to the patient directory history?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff3e1d', // Sneat Danger Red Accent
        cancelButtonColor: '#8592a3',  // Sneat Secondary Muted Slate
        confirmButtonText: 'Yes, Save Observations'
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Writing Entry...',
            html: 'Encrypting triage observation charts securely...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: server_url, // Points directly to your shared core URL variable
            type: 'POST',
            data: $(form).serialize() + '&record_vitals=true', // Appends parameter to trigger target isset block
            dataType: 'json',
            success: function(response) {
                Swal.close();

                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Biometrics Logged',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    updatePatientListUI(response.data);
                        $('#opdVitalsModal').modal('hide');
                        form.reset();
                        
                        // Drop temp state variables completely on completion
                        $(form).find('input[name="edit_vitals"]').remove();
                        $(form).find('input[name="vitals_id"]').remove();
                } else {
                    Swal.fire('Processing Denied', response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                Swal.fire('Pipeline Anomaly', 'An error occurred while saving the observations data.', 'error');
            }
        });
    });
});

    var currentStep = 1;
    var totalSteps = 3;

    // 1. FINTECH STEP NAVIGATION CONTROL LOGIC
    function updateWizardUI() {
        $('.wizard-step').removeClass('active');
        $('#wizard-step-' + currentStep).addClass('active');

        // Manage progress pill styling states
        for (var i = 1; i <= totalSteps; i++) {
            var $pill = $('#pill-step-' + i);
            if (i < currentStep) {
                $pill.removeClass('active').addClass('completed');
            } else if (i === currentStep) {
                $pill.removeClass('completed').addClass('active');
            } else {
                $pill.removeClass('active completed');
            }
        }

        // Toggle button displays
        if (currentStep === 1) {
            $('#btn-wizard-prev').hide();
        } else {
            $('#btn-wizard-prev').show();
        }

        if (currentStep === totalSteps) {
            $('#btn-wizard-next').hide();
            $('#btn-wizard-submit').show();
        } else {
            $('#btn-wizard-next').show();
            $('#btn-wizard-submit').hide();
        }
    }

    // Next Button Client Verification Interceptor
    $('#btn-wizard-next').on('click', function() {
        var currentInputs = $('#wizard-step-' + currentStep).find('input[required], select[required]');
        var valid = true;

        currentInputs.each(function() {
            if (!this.checkValidity()) {
                this.reportValidity();
                valid = false;
                return false;
            }
        });

        if (valid && currentStep < totalSteps) {
            currentStep++;
            updateWizardUI();
        }
    });

    // Previous Step Button Click
    $('#btn-wizard-prev').on('click', function() {
        if (currentStep > 1) {
            currentStep--;
            updateWizardUI();
        }
    });

    // 2. SEGMENTED CONTROL: INTERACTIVE STUDENT REF FIELD TOGGLE
    $('input[name="is_student"]').on('change', function() {
        if ($(this).val() === 'Yes') {
            $('#studentRefWrapper').removeClass('d-none opacity-0').css('pointer-events', 'auto');
            $('#reference_number').attr('required', true);
        } else {
            $('#studentRefWrapper').addClass('d-none opacity-0').css('pointer-events', 'none');
            $('#reference_number').removeAttr('required').val('');
        }
    });

    // 3. INSURANCE INPUT INTERLOCK SWITCH
    $('#insurance_type').on('change', function() {
        if ($(this).val() === 'None') {
            $('#insurance_number').val('').attr('disabled', true).removeAttr('required');
        } else {
            $('#insurance_number').removeAttr('disabled').attr('required', true).attr('placeholder', 'Enter policy tracker number');
        }
    });
});
$(document).ready(function() {
    // EVENT INTERCEPTOR: CAPTURE AND PARSE ENCODED PATIENT ROW MATRIX
$('#DataTable').on('click', '.btn-view-more', function(e) {
    e.preventDefault();
    
    // 1. Fetch the raw Base64 property string attached to the action link node element
    var rawPayload = $(this).attr('data-payload');
    if (!rawPayload) return;

    try {
        // 2. Decode the transport string token layer using secure window algorithms
        var decodedJSON = atob(rawPayload);
        var patient = JSON.parse(decodedJSON);

        // 3. Bind core demographic fields to structural UI layout IDs
        $('#m_display_name').text(patient.name);
        $('#m_folder').text(patient.folder_number);
        $('#m_student_ref').text(patient.reference_number ? patient.reference_number : 'N/A (External Profile)');
        $('#m_ghana_card').text(patient.ghana_card_number ? patient.ghana_card_number : 'Not Registered / Exempt');
        
        // Format date structure beautifully
        if(patient.date_of_birth) {
            var dobObj = new Date(patient.date_of_birth);
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $('#m_dob').html(dobObj.getDate() + ' ' + months[dobObj.getMonth()] + ' ' + dobObj.getFullYear() + ' <small class="text-muted">('+patient.calculated_age+' yrs)</small>');
        }
        
        $('#m_marital').text(patient.marital_status);
        $('#m_contact').text(patient.contact);
        $('#m_emergency').text(patient.emergency_contact);
        $('#m_relationship').text(patient.relationship_to_contact);

        // 4. Generate the dynamic Insurance Coverage presentation frame
        var insWrapper = $('#m_insurance_box');
        insWrapper.removeClass('bg-danger-subtle bg-success-subtle border-danger border-success');
        
        if (patient.insurance_type === 'None') {
            insWrapper.addClass('bg-danger-subtle border-danger-subtle p-3 rounded-3 d-flex align-items-center gap-2');
            insWrapper.html('<i class="bx bx-shield-x text-danger fs-4"></i> <div><div class="fw-bold text-danger small">Cash-Paying Account</div><div class="text-muted" style="font-size:0.75rem;">No valid public or private insurance cover tied to this folder.</div></div>');
        } else {
            insWrapper.addClass('bg-success-subtle border-success-subtle p-3 rounded-3 d-flex align-items-center gap-2');
            insWrapper.html('<i class="bx bx-shield-quarter text-success fs-4"></i> <div><div class="fw-bold text-success small">'+patient.insurance_type+' Provider Cover Active</div><div class="text-muted fw-semibold" style="font-size:0.75rem;">Policy Str: '+patient.insurance_number+'</div></div>');
        }

        // 5. Instantly reveal the slide-out overlay interface modal frame view
        $('#patientDetailsModal').modal('show');

    } catch (failure) {
        console.error("KYC Decryption Handshake Exception Breakdown: ", failure);
        Swal.fire('Data Corruption Error', 'Unable to decrypt secure patient data properties string cleanly.', 'error');
    }
});


    // [Keep your previous step-navigation and toggles code unchanged here]

    // ==========================================
    // FINTECH-GRADE CLIENT VALIDATION ENGINE & AJAX SUBMISSION
    // ==========================================
    $('#patientRegistrationForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = this;
        var formData = $(this).serialize();
        
        // 1. CHECK IF MODAL IS IN EDIT MODE OR SAVE MODE
        var isEditMode = $(form).find('input[name="edit_patient"]').val() === 'true';
        
        // 2. FINAL COMPREHENSIVE NATIVE CLIENT VALIDATION RUN
        if (!form.checkValidity()) {
            form.reportValidity();
            return false;
        }

        // 3. EXTRA FINTECH VALIDATION: CUSTOM FIELD CHECKS (GHANA CARD & MOBILE PHONES)
        var phone = $('input[name="contact"]').val().trim();
        var emergPhone = $('input[name="emergency_contact"]').val().trim();
        var ghanaCard = $('input[name="ghana_card_number"]').val().trim();
        var patientName = $('input[name="name"]').val().trim();

        // Validate Ghanaian phone lengths
        if (phone.length < 10 || emergPhone.length < 10) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please enter a valid 10-digit mobile number for contacts.',
                icon: 'warning',
                confirmButtonColor: '#0ea5e9'
            });
            return false;
        }

        // Prevent registration if personal and emergency contacts match
        if (phone === emergPhone) {
            Swal.fire({
                title: 'Security Notice',
                text: 'Primary phone and emergency contact numbers cannot be identical.',
                icon: 'warning',
                confirmButtonColor: '#0ea5e9'
            });
            return false;
        }

        // 4. DYNAMIC SECURITY CONFIRMATION TEXTS
        var alertTitle = isEditMode ? 'Verify File Modifications' : 'Verify Registration Details';
        var alertHtml = isEditMode 
            ? `You are about to save changes to the folder of <br><b class="text-primary">${patientName}</b>.<br><br><small class="text-muted">Ensure all edited values match patient records.</small>`
            : `You are about to register a secure profile folder for <br><b class="text-primary">${patientName}</b>.<br><br><small class="text-muted">Ensure all demographic properties comply with KYC regulations.</small>`;
        var confirmBtnText = isEditMode ? 'Yes, Save Changes' : 'Yes, Execute Enrollment';
        var progressLoaderHtml = isEditMode ? 'Updating patient data file records...' : 'Securing transaction and generating folder allocation token...';

        // 5. DYNAMIC SECURITY CONFIRMATION PROMPT
        Swal.fire({
            title: alertTitle,
            html: alertHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981', // Emerald Success Green
            cancelButtonColor: '#cbd5e1', // Slate Gray
            confirmButtonText: confirmBtnText,
            cancelButtonText: 'Review Details',
            background: '#ffffff',
            customClass: {
                popup: 'border-radius-12'
            }
        }).then((result) => {
            // Drop out cleanly if user decides to double check fields
            if (!result.isConfirmed) return;

            // 6. TRIGGER ASYNCHRONOUS TRANSACTION PROGRESS LOADER
            Swal.fire({
                title: 'Encrypting Payload',
                html: progressLoaderHtml,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading(); // Renders the high-fidelity native rolling spinner
                }
            });

            // 7. BUILD DATA PAYLOAD BASED ON PORTAL MODE
            var finalizedData = formData;
            if (!isEditMode) {
                finalizedData += '&save_patient=true'; // Append only for brand new files
            }
            // 7. BUILD DATA PAYLOAD BASED ON PORTAL MODE
            var finalizedData = formData + '&manage_patient=true';


            // 8. SECURE AJAX NETWORK TRANSPORT ROUTINE
            $.ajax({
                url: server_url, // Points to your server-side database handler
                type: 'POST',
                data: finalizedData,
                dataType: 'json',
                success: function(response) {
                    // Close the loading modal layer cleanly
                    Swal.close();

                    if (response.status === 'success') {
                        // Success alert with an automated refresh callback hook
                        var successTitle = isEditMode ? 'Changes Applied' : 'Enrollment Confirmed';
                        
                        Swal.fire({
                            title: successTitle,
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#10b981',
                            allowOutsideClick: false
                        });
                        
                        $('#addPatientModal').modal('hide');
                        updatePatientListUI(response.data); // Empties tbody and fills fresh records
                        form.reset();
                        $('input[name="is_student"]:first').prop('checked', true).trigger('change');
                    } else {
                        // Server validation failure alert (e.g., duplicated Folder No. / Ghana Card)
                        Swal.fire({
                            title: 'Transaction Aborted',
                            text: response.message,
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error("AJAX Pipeline failure: " + error);
                    
                    Swal.fire({
                        title: 'Network Timeout',
                        text: 'An unexpected connection or database pipeline crash occurred. Please check system logs.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
        });
    });
});

// EVENT INTERCEPTOR: CATCH PATIENT DATA AND CONVERT REGISTRATION MODAL INTO EDIT MODE
$('#DataTable').on('click', '.btn-edit-patient', function(e) {
    e.preventDefault();
    
    var rawPayload = $(this).attr('data-payload');
    if (!rawPayload) return;

    try {
        // 1. Decode the base64 row payload string safely
        var decodedJSON = atob(rawPayload);
        var patient = JSON.parse(decodedJSON);

        // 2. Target your existing Add Modal and Form elements
        var $modal = $('#addPatientModal');
        var $form = $('#patientRegistrationForm');

        // Reset old values and return to step 1 layout cleanly
        $form[0].reset();
        currentStep = 1; // Resets your multi-step wizard back to page 1
        
        // 3. SWITCH MODAL TEXT TO PREMIUM FINTECH UPDATE MODE
        $modal.find('.modal-title').text('Update Patient Registration File');
        $modal.find('#btn-wizard-submit').text('Apply Secure Modifications');
        
        // 4. APPEND TARGET PATIENT ID KEY FOR THE SERVER-SIDE PROCESSING ROUTINE
        // Removes any old hidden IDs first to prevent conflict collisions
        $form.find('input[name="patient_id"]').remove();
        $form.find('input[name="edit_patient"]').remove();
        
        $form.append(`<input type="hidden" name="patient_id" value="${patient.id}" />`);
        $form.append(`<input type="hidden" name="edit_patient" value="true" />`);

        // 5. MAP AND INJECT CORE PATIENT BIOGRAPHICAL METRICS INTO FORM INPUTS
        $form.find('input[name="name"]').val(patient.name);
        $form.find('input[name="date_of_birth"]').val(patient.date_of_birth);
        $form.find('select[name="gender"]').val(patient.gender);
        $form.find('select[name="marital_status"]').val(patient.marital_status);
        $form.find('input[name="contact"]').val(patient.contact);
        $form.find('input[name="emergency_contact"]').val(patient.emergency_contact);
        $form.find('input[name="relationship_to_contact"]').val(patient.relationship_to_contact);
        $form.find('input[name="ghana_card_number"]').val(patient.ghana_card_number || '');
        $form.find('select[name="insurance_type"]').val(patient.insurance_type).trigger('change');
        $form.find('input[name="insurance_number"]').val(patient.insurance_number || '');

        // 6. DETECT AND TOGGLE IS_STUDENT RADIO BUTTON CONFIGURATION
        if (patient.reference_number) {
            $('#is_student_yes').prop('checked', true).trigger('change');
            $('#reference_number').val(patient.reference_number);
        } else {
            $('#is_student_no').prop('checked', true).trigger('change');
        }

        // 7. Reset Wizard Progress Bars UI View Frame Layouts Natively
        if (typeof updateWizardUI === "function") {
            updateWizardUI();
        }

        // 8. Open the modal smoothly
        $modal.modal('show');

    } catch (error) {
        console.error("Edit form field load failure: ", error);
        Swal.fire('Processing Error', 'Unable to extract patient attributes cleanly for editing.', 'error');
    }
});

// RESET TRIGGER: CLEAN UP THE MODAL STATUS WHEN DISMISSED OR CLOSED
$('#addPatientModal').on('hidden.bs.modal', function () {
    var $form = $('#patientRegistrationForm');
    
    // Change everything back to original Add New mode titles
    $(this).find('.modal-title').text('New Patient Medical Directory Entry');
    $(this).find('#btn-wizard-submit').text('Complete Secure Enrollment');
    
    // Drop the dynamic edit hidden flags out of the form
    $form.find('input[name="patient_id"]').remove();
    $form.find('input[name="edit_patient"]').remove();
    
    $form[0].reset();
    $('#is_student_no').prop('checked', true).trigger('change');
    $('#insurance_type').val('None').trigger('change');
    currentStep = 1;
    if (typeof updateWizardUI === "function") updateWizardUI();
});

/**
 * Flushes the existing table rows via .empty() and rebuilds the tbody with a fresh dataset.
 * Follows strict Sneat Dashboard styles and layouts.
 * 
 * @param {Array} patientsArray - The full array of patient records returned from the server response
 */
function updatePatientListUI(patientsArray) {
    // 1. Get the tbody element and wipe it clean
    var $tbody = $('#DataTable').find('tbody');
    $tbody.empty();

    // 2. If no data comes back, show a simple empty message row
    if (!patientsArray || patientsArray.length === 0) {
        $tbody.append('<tr><td colspan="7" class="text-center text-muted py-4"><i class="bx bx-folder-open fs-3 d-block mb-2"></i> No patient logs found inside the directory matrix.</td></tr>');
        return;
    }

    // 3. Get the active logged-in user role from PHP session variable safely
    var userRole = '<?php echo $_SESSION["role"] ?? ""; ?>';
    
    var counter = 1;
    var rowsMarkup = '';

    // 4. Loop through every patient in the fresh array list
    patientsArray.forEach(function(patient) {
        // Safe string encoding to build the Base64 payload data attribute token
        var stringified = JSON.stringify(patient);
        var base64Payload = btoa(unescape(encodeURIComponent(stringified)));

        // Clean out dirty entries to keep data outputs text-safe (Anti-XSS guard)
        var cleanFolder = $('<div>').text(patient.folder_number).html();
        var cleanName = $('<div>').text(patient.name).html();
        var cleangender = $('<div>').text(patient.gender).html();
        var cleanContact = $('<div>').text(patient.contact).html();
        var cleanEmergency = $('<div>').text(patient.emergency_contact).html();
        var cleanAge = parseInt(patient.calculated_age) || 0;

        // 5. Check if the user is a doctor to show the blue Consult button
        var doctorButton = '';
        if (userRole === 'Doctor') {
            doctorButton = `
                <button type="button" class="btn btn-sm btn-primary btn-consulting-room" data-payload="${base64Payload}" title="Doctor Room">
                    <i class="bx bx-plus-medical me-1"></i> Consult
                </button>
            `;
        }

        // 6. Build the row template markup string block matching your exact columns
        rowsMarkup += `
            <tr>
                <td>${counter++}</td>
                <td class="fw-bold text-primary">${cleanFolder}</td>
                <td>${cleanName}</td>
                <td><span class="badge bg-secondary">${cleangender}</span></td>
                <td>
                    ${cleanContact}
                    <br><small class="text-muted">Emerg: ${cleanEmergency}</small>
                </td>
                <td>${cleanAge} yrs</td>
                <td class="text-center align-middle">
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <!-- MORE BUTTON (Sneat Info) -->
                        <button type="button" class="btn btn-sm btn-outline-info btn-view-more" data-payload="${base64Payload}" title="See More Info">
                            <i class="bx bx-show-alt me-1"></i> More
                        </button>

                        <!-- EDIT BUTTON (Sneat Warning) -->
                        <button type="button" class="btn btn-sm btn-outline-warning btn-edit-patient" data-payload="${base64Payload}" title="Edit Patient Info">
                            <i class="bx bx-edit-alt me-1"></i> Edit
                        </button>

                        <!-- VITALS BUTTON (Sneat Danger) -->
                        <button type="button" class="btn btn-sm btn-outline-danger btn-capture-vitals" data-payload="${base64Payload}" title="Check Heart & Weight">
                            <i class="bx bx-pulse me-1"></i> Vitals 
                        </button>

                        <!-- DOCTOR CONSULT BUTTON (Sneat Primary) -->
                        ${doctorButton}
                    </div>
                </td>
            </tr>
        `;
    });

    // 7. Inject the freshly compiled list rows back to the table DOM layer
    $tbody.append(rowsMarkup);
}

</script>