 <?php
try {
    require_once 'includes/header.php'; 
    if (!$con) throw new Exception('Database connection failed');

    $labCatalog = $con->query("SELECT id, test_name FROM lab_catalog ORDER BY test_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $drugCatalog = $con->query("SELECT id, drug_name FROM pharmacy_store WHERE quantity_in_store > 0 ORDER BY drug_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $queueResponse = getWaitingQueue($con);
    if ($queueResponse['status'] === 'error') throw new Exception($queueResponse['message']);
    $waitingPatients = $queueResponse['data'];
} catch (Throwable $e) {
    error_log('Doctor Portal Initializer Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger m-4">System Init Failure: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 p-2.5">
        
        <!-- Welcome Banner Segment Layout Header Area -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0.5"><span class="text-muted fw-light">Clinical Rooms /</span> Consulting Suite</h4>
                <p class="text-muted small mb-0">Review active triage parameters, check laboratory orders, and attend to patients</p>
            </div>
        </div>

        <!-- ACTIVE WAITING QUEUE PRESENTATION COMPONENT MATRIX -->
        <div class="card mb-3">
            <!-- FIXED HEADER: Integrated live asynchronous reset trigger button inline with layout -->
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-primary mb-0"><i class="bx bx-list-check me-1"></i> Today's Waiting Queue</h6>
                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2.5 font-monospace fw-semibold shadow-sm" id="btn_refresh_consulting_queue">
                    <i class="bx bx-refresh me-0.5"></i> Refresh Queue
                </button>
            </div>
            
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="DataTable" class="table table-sm table-striped table-hover align-middle w-100 font-monospace text-xs" style="font-size:0.75rem;">
                        <thead class="table-light">
                            <tr>
                                <th  >S/N</th>
                                <th class="ps-2">Folder No.</th>
                                <th>Patient Name</th>
                                <th>Gender</th>
                                <th>Age</th>
                                <th>BP Line</th>
                                <th>Temp</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($waitingPatients)): ?>
                                <?php $sn=1; foreach ($waitingPatients as $p): 
                                    $encodedData = base64_encode(json_encode($p));
                                ?>
                                    <tr>
                                        <td><?php echo $sn++; ?>  </td>
                                        <td class="ps-2 fw-bold text-primary">#<?php echo htmlspecialchars($p['folder_number']); ?></td>
                                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><span class="badge bg-label-secondary py-0 px-1 text-xs"><?php echo htmlspecialchars($p['gender']); ?></span></td>
                                        <td><?php echo $p['calculated_age']; ?> yrs</td>
                                        <td class="text-danger fw-bold"><?php echo htmlspecialchars($p['bp']); ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($p['temperature']); ?> °C</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-primary py-0.5 px-2 fw-bold btn-attend-patient" data-payload="<?php echo $encodedData; ?>">
                                                <i class="bx bx-plus-medical me-0.5"></i> Attend
                                            </button>
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
<!-- MODAL: DOCTOR CONSULTATION & RECORD SUITE  -->
<!-- ========================================== -->
<div class="modal fade" id="consultationModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary border-bottom py-3">
                <h5 class="modal-title fw-bold text-white" id="modal_patient_title">Clinical Diagnosis Room</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="consultationForm" method="POST" autocomplete="off">
    <!-- Hidden inputs to track patient and triage indexes safely -->
    <input type="hidden" name="patient_id" id="c_patient_id" />
    <input type="hidden" name="vitals_id" id="c_vitals_id" />
    <input type="hidden" name="consultation_id" value="" id="consultation_id" />
    <input type="hidden" name="manage_consultation" value="true" />

      <div class="modal-body p-4 bg-light">
    <!-- MASTER CONTAINER ROW -->
    <div class="row g-3" id="consultation_workspace_row">
        
        <!-- COLUMN 1 (Left): VISIT ARCHIVES SIDEBAR PANEL (NATIVE BOOTSTRAP col-md-2) -->
        <div class="col-md-2 transition-all px-2" id="history_column_wrapper">
            <div class="card shadow-none border h-100" style="min-height: 540px;">
                <!-- HEADER AREA -->
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2" id="history_header_area">
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary border-0 p-0" id="btn_toggle_history_pane" title="Minimize History Panel">
                            <i class="bx bx-chevron-left fs-4" id="toggle_pane_icon"></i>
                        </button>
                        <h6 class="fw-bold text-secondary mb-0"><i class="bx bx-calendar me-1"></i> Archives</h6>
                    </div>
                    <a href="#" id="btnPrintFullHistory" target="_blank" class="btn btn-xs btn-outline-primary fw-bold">
                        <i class="bx bx-printer"></i>
                    </a>
                </div>
                
                <!-- DYNAMIC MENUS LIST CONTAINER -->
                <div class="card-body p-2 transition-all" id="visit_dates_timeline_group" style="max-height: 480px; overflow-y: auto;">
                    <!-- JavaScript populates clickable chronological list badges groups here -->
                </div>

                <!-- VERTICAL EXPAND TRIGGER STRIP (Hidden by default, shows when minimized) -->
                <div id="collapsed_open_trigger_strip" class="d-none text-center pt-3 cursor-pointer h-100" title="Expand History Panel" style="background-color: #fcfdfe; border-radius: 8px;">
                    <button type="button" class="btn btn-sm btn-primary p-1 btn-icon rounded-circle mb-3 shadow">
                        <i class="bx bx-chevron-right fs-4"></i>
                    </button>
                    <div class="text-uppercase font-monospace text-muted vertical-text-label" style="font-size: 0.62rem; letter-spacing: 0.1em; writing-mode: vertical-lr;">VISIT HISTORY</div>
                </div>
            </div>
        </div>

        <!-- COLUMN 2 (Middle): BIOMETRICS TRIAGE & COMPLETED LAB CHARTS (NATIVE col-md-4) -->
        <div class="col-md-4 transition-all" id="middle_metrics_column">
            <!-- Vitals Capture/Review Display Card -->
            <div class="card shadow-none border mb-3">
                <h6 class="card-header fw-bold bg-white border-bottom text-danger d-flex justify-content-between align-items-center py-2.5">
                    <span><i class="bx bx-heart me-1"></i> Vital Signs Chart</span>
                    <span id="historical_vitals_date_badge" class="badge bg-label-danger font-monospace" style="font-size:0.7rem;">LIVE TODAY</span>
                </h6>
                <div class="card-body pt-3">
                    <div class="row g-3 text-dark small font-monospace" style="font-size:0.82rem; font-weight:600;">
                        <div class="col-6">BP: <span id="v_bp" class="text-danger fw-bold">--/--</span></div>
                        <div class="col-6">Pulse: <span id="v_pulse">--</span> BPM</div>
                        <div class="col-4">Temp: <span id="v_temp">--</span> °C</div>
                        <div class="col-4">Weight: <span id="v_weight">--</span> Kg</div>
                        <div class="col-4">SpO2: <span id="v_spo2">--</span> %</div>
                    </div>
                </div>
            </div>

            <!-- Dispatched Laboratory Findings Card -->
            <div class="card shadow-none border" style="min-height: 310px;">
                <h6 class="card-header fw-bold bg-white border-bottom text-info py-2.5">
                    <i class="bx bx-test-tube me-1"></i> Laboratory Outcomes
                </h6>
                <div class="card-body pt-2" id="v_today_labs_container" style="max-height: 270px; overflow-y: auto;">
                    <!-- Dynamic lab findings text blocks get drawn here -->
                </div>
            </div>
        </div>

         <!-- COLUMN 3 (Right): 3-STEP INTEGRATED EVALUATION, RX & DISPOSITION SUITE -->
<div class="col-md-6 transition-all" id="right_checksheet_column">
    
    <!-- STEP PROGRESS TABS NAVIGATION PILLS (HIDDEN VISUALLY, INTERNALLY DRIVEN BY SCRIPT) -->
    <ul class="nav nav-pills d-none" id="wizardTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" id="step1-tab" data-bs-toggle="tab" data-bs-target="#wizardStep1" type="button" role="tab">Step 1</button></li>
        <li class="nav-item"><button class="nav-link" id="step2-tab" data-bs-toggle="tab" data-bs-target="#wizardStep2" type="button" role="tab">Step 2</button></li>
        <li class="nav-item"><button class="nav-link" id="step3-tab" data-bs-toggle="tab" data-bs-target="#wizardStep3" type="button" role="tab">Step 3</button></li>
    </ul>

    <!-- MAIN WIZARD STEPS WINDOW CONTENT LAYER -->
    <div class="tab-content p-0 border-0 shadow-none bg-transparent">
        
        <!-- ==================================================================== -->
        <!-- STEP 1: CLINICAL EVALUATION & LAB ORDERS                              -->
        <!-- ==================================================================== -->
        <div class="tab-pane fade show active" id="wizardStep1" role="tabpanel">
            <div class="card shadow-none border h-100">
                <h6 class="card-header fw-bold bg-white border-bottom text-success py-2.5 d-flex justify-content-between align-items-center">
                    <span><i class="bx bx-edit me-1"></i> Step 1: Clinical Evaluation & Labs</span>
                    <small class="badge bg-label-success text-xs font-monospace">PAGE 1 OF 3</small>
                </h6>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Patient Complaints</label>
                            <textarea name="complaints" id="c_complaints" class="form-control" rows="3" placeholder="Type clinical symptoms observations..."></textarea>
                            <div class="invalid-feedback" id="complaints_feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Diagnosis</label>
                            <textarea name="diagnosis" id="c_diagnosis" class="form-control" rows="3" placeholder="Type working diagnostics outcomes..."></textarea>
                            <div class="invalid-feedback" id="diagnosis_feedback"></div>
                        </div>
                        <div class="col-12 border p-2.5 rounded bg-light-subtle mt-2">
                            <label class="form-label fw-semibold text-info mb-1"><i class="bx bx-test-tube me-1"></i> Order Lab Tests</label>
                            <select name="lab_test_ids[]" id="c_lab_test_ids" class="form-select" multiple>
                                <?php foreach ($labCatalog as $lab): ?>
                                    <option value="<?php echo $lab['id']; ?>"><?php echo htmlspecialchars($lab['test_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top py-2.5 px-3 text-end">
                    <button type="button" class="btn btn-primary btn-sm px-4 btn-wizard-next-step2">
                        Next: Oral Medicines <i class="bx bx-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================================================================== -->
        <!-- STEP 2: ORAL MEDICATIONS BASKET PANEL (TABLETS / CAPSULES ONLY)       -->
        <!-- ==================================================================== -->
        <div class="tab-pane fade" id="wizardStep2" role="tabpanel">
            <div class="card shadow-none border">
                <h6 class="card-header fw-bold bg-white border-bottom text-warning py-2.5 d-flex justify-content-between align-items-center">
                    <span><i class="bx bx-capsule me-1"></i> Step 2: Oral Medication Basket</span>
                    <small class="badge bg-label-warning text-xs font-monospace">PAGE 2 OF 3</small>
                </h6>
                <div class="card-body pt-3">
                    <div class="row g-2 mb-2 align-items-end">
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Select Oral Drug</label>
                            <select id="c_drug_id" class="form-select form-select-sm">
                                <option value="" selected disabled>-- Choose Medicine From List --</option>
                                <?php foreach ($drugCatalog as $drug): ?>
                                    <option value="<?php echo $drug['id']; ?>"><?php echo htmlspecialchars($drug['drug_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small text-muted mb-0">Amount</label>
                            <input type="text" id="dose_qty" class="form-control form-control-sm" placeholder="e.g. 2 tabs" />
                        </div>
                        <div class="col-4">
                            <label class="form-label small text-muted mb-0">Frequency</label>
                            <select id="dose_freq" class="form-select form-select-sm">
                                <option value="" selected disabled>-- Pick --</option>
                                <option value="Once daily">Once daily (1x)</option>
                                <option value="Twice daily">Twice daily (2x)</option>
                                <option value="3 times daily">3 times daily (3x)</option>
                                <option value="Every 4 hours">Every 4 hours</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small text-muted mb-0">Duration</label>
                            <input type="text" id="dose_duration" class="form-control form-control-sm" placeholder="e.g. 5 days" />
                        </div>
                        <div class="col-2">
                            <button type="button" id="btn_add_oral_to_basket" class="btn btn-sm btn-warning w-100 p-1 py-1.5"><i class="bx bx-plus"></i> Add</button>
                        </div>
                    </div>  
                    
                         <!-- ORAL TABLE -->
<div id="oral_debug_box">
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.75rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:8%">#</th>
                    <th style="width:32%">Medication</th>
                    <th style="width:40%">Dosage</th>
                    <th style="width:10%">Type</th>
                    <th style="width:10%" class="text-center">Drop</th>
                </tr>
            </thead>
            <tbody id="oral_debug_tbody"></tbody>
        </table>
    </div>
</div>
                </div>
                <div class="card-footer bg-white border-top py-2.5 px-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 btn-wizard-back-step1"><i class="bx bx-chevron-left me-1"></i> Back</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 btn-wizard-next-step3">Next: Injections & Disposition <i class="bx bx-chevron-right ms-1"></i></button>
                </div>
            </div>
        </div>

        <!-- ==================================================================== -->
        <!-- STEP 3: INJECTIONS & ADMISSION STATUS DISPOSITION                     -->
        <!-- ==================================================================== --> 
                 <div class="tab-pane fade" id="wizardStep3" role="tabpanel">
            <div class="card shadow-none border">
                <h6 class="card-header fw-bold bg-white border-bottom text-danger py-2.5 d-flex justify-content-between align-items-center">
                    <span><i class="bx bx-shield-plus me-1"></i> Step 3: Injections & Care Disposition</span>
                    <small class="badge bg-label-danger text-xs font-monospace">PAGE 3 OF 3</small>
                </h6>
                <div class="card-body pt-3">
                    
                    <!-- Injection Segment Panel -->
                    <div class="mb-3 border p-2.5 rounded bg-light-subtle">
                        <label class="form-label fw-semibold text-danger small mb-1"><i class="bx bx-injection me-1"></i> Emergency Clinical Injections</label>
                        <div class="row g-2 align-items-end">
                            <div class="col-12">
                                <select id="c_inject_id" class="form-select form-select-sm">
                                    <option value="" selected disabled>-- Choose Injectable / Ampoule From List --</option>
                                    <?php foreach ($drugCatalog as $drug): ?>
                                        <option value="<?php echo $drug['id']; ?>"><?php echo htmlspecialchars($drug['drug_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label text-muted small scale-xs mb-0">Volume/Vol</label>
                                <input type="text" id="inj_qty" class="form-control form-control-sm" placeholder="e.g. 2ml" />
                            </div>
                            <div class="col-4">
                                <label class="form-label text-muted small scale-xs mb-0">Route</label>
                                <select id="inj_route" class="form-select form-select-sm">
                                    <option value="" selected disabled>-- Select Route --</option>
                                    <option value="Intramuscular (IM)">Intramuscular (IM)</option>
                                    <option value="Intravenous (IV)">Intravenous (IV)</option>
                                    <option value="Subcutaneous (SC)">Subcutaneous (SC)</option>
                                    <option value="Slow IV Infusion">Slow IV Infusion</option>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label text-muted small scale-xs mb-0">Duration</label>
                                <input type="text" id="inj_duration" class="form-control form-control-sm" placeholder="e.g. Stat / 2 days" />
                            </div>
                            <div class="col-2">
                                <button type="button" id="btn_add_inject_to_basket" class="btn btn-sm btn-danger w-100 p-1 py-1.5"><i class="bx bx-plus"></i></button>
                            </div>
                        </div> 

<!-- INJECTION TABLE -->
<div id="inject_debug_box" class="mt-3">
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.75rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:8%">#</th>
                    <th style="width:32%">Medication</th>
                    <th style="width:40%">Dosage</th>
                    <th style="width:10%">Type</th>
                    <th style="width:10%" class="text-center">Drop</th>
                </tr>
            </thead>
            <tbody id="inject_debug_tbody"></tbody>
        </table>
    </div>
</div>
                    </div>

                    <!-- Admission Care Disposition Status -->
                    <div class="border p-2.5 rounded bg-light-subtle">
                        <label class="form-label fw-semibold text-dark small mb-1"><i class="bx bx-bed me-1"></i> Patient Care Disposition Status</label>
                        <select name="admission_status" id="c_admission_status" class="form-select form-select-sm">
                            <option value="Outpatient" selected>Outpatient Care (Discharge / Send Home)</option>
                            <option value="Inpatient">Inpatient Care (Admit to Ward)</option>
                        </select>
                    </div>

                </div>
                <div class="card-footer bg-white border-top py-2.5 px-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 btn-wizard-back-step2"><i class="bx bx-chevron-left me-1"></i> Back</button>
                    <span class="small font-monospace text-muted"><i class="bx bx-check-shield"></i> Basket Armed</span>
                </div>
            </div>
        </div>

    </div>
</div>


    </div>
</div>

     <div class="modal-footer border-top bg-white py-3 px-4 d-flex justify-content-between align-items-center">
        <!-- CONFIRMATION CHECKBOX (Not natively required anymore; handled via JS/PHP) -->
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="chk_treatment_complete" />
            <label class="form-check-label fw-bold text-danger cursor-pointer" for="chk_treatment_complete">
                <i class="bx bx-check-shield text-danger me-1"></i> I confirm treatment is complete for this patient.
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close Room</button>
            <button type="submit" class="btn btn-primary fw-bold px-4">Save & Close Case File</button>
        </div>
    </div>
</form>

        </div>
    </div>
</div> 
     
<style>
  /* Smooth animation interpolation for columns scaling layout sizes */
  .transition-all {
    transition: all 0.35s cubic-bezier(0.25, 1, 0.5, 1) !important;
  }
  
  /* Class selector that shrinks column 1 down to a micro strip footprint */
  .pane-shrunk-narrow-strip {
    width: 50px !important;
    flex: 0 0 50px !important;
    max-width: 50px !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
  }

  /* Text label rotation mapping */
  .vertical-text-label {
    transform: rotate(180deg);
    display: inline-block;
    white-space: nowrap;
    margin: 0 auto;
    font-weight: 700;
  }
 
    /* Blends the Choices.js wrapper form control seamlessly into Sneat style standards */
.choices__inner {
    border: 1px solid #d9dee3 !important;
    background-color: #fff !important;
    border-radius: 0.375rem !important;
    padding: 0.25rem 0.5rem !important;
    min-height: 38px !important;
}
.choices__input {
    background-color: transparent !important;
    font-size: 0.85rem !important;
}

</style>
<?php require_once 'includes/footer.php';?>
 <script>
    
    // Declare a global reference container for the Choices engine instance
var labChoicesInstance = null;

$(document).ready(function() {
    
    // --- 1. WIZARD STEP DIRECTIONAL NAVIGATION CONTROLLERS ---
    $(document).on('click', '.btn-wizard-next-step2', function(e) {
        e.preventDefault();
        var tab = new bootstrap.Tab(document.querySelector('#step2-tab'));
        tab.show();
    });

    $(document).on('click', '.btn-wizard-back-step1', function(e) {
        e.preventDefault();
        var tab = new bootstrap.Tab(document.querySelector('#step1-tab'));
        tab.show();
    });

    $(document).on('click', '.btn-wizard-next-step3', function(e) {
        e.preventDefault();
        var tab = new bootstrap.Tab(document.querySelector('#step3-tab'));
        tab.show();
    });

    $(document).on('click', '.btn-wizard-back-step2', function(e) {
        e.preventDefault();
        var tab = new bootstrap.Tab(document.querySelector('#step2-tab'));
        tab.show();
    });

    
    // Reset workflow layer to Step 1 instantly when opening another patient folder
    $('#DataTable').on('click', '.btn-attend-patient', function() {
        var step1Trigger = document.querySelector('#step1-tab');
        if (step1Trigger) {
            var tabInstance = bootstrap.Tab.getInstance(step1Trigger) || new bootstrap.Tab(step1Trigger);
            tabInstance.show();
        }
        
        // Empty out arrays baskets rows UI memory elements cleanly
        $('.prescription-item-row').remove();
        $('#empty_oral_placeholder, #empty_inject_placeholder').show();
    });
    
});

    // EVENT INTERCEPTOR: COMPACT TOGGLE AND NATIVE GRID WIDENING SYSTEM ROUTINE
$(document).on('click', '#btn_toggle_history_pane, #collapsed_open_trigger_strip', function(e) {
    e.preventDefault();
    
    var $wrapper = $('#history_column_wrapper');
    var $headerArea = $('#history_header_area');
    var $timelineGroup = $('#visit_dates_timeline_group');
    var $collapsedStrip = $('#collapsed_open_trigger_strip');
    
    // Target the primary sheets element panel column container
    var $rightChecksheetCol = $('#right_checksheet_column');

    if (!$wrapper.hasClass('pane-shrunk-narrow-strip')) {
        // --- ACTION 1: CLOSE / MINIMIZE TO THE LEFT ---
        $headerArea.addClass('d-none');
        $timelineGroup.addClass('d-none');
        
        // Remove native col-md-2 structure from sidebar and replace it with micro strip class
        $wrapper.removeClass('col-md-2').addClass('pane-shrunk-narrow-strip');
        $collapsedStrip.removeClass('d-none'); // Reveal the vertical open strip button

        // Dynamic adjustment: Drop col-md-6 from column 3 and expand out to a full col-md-8 layout instantly
        // This ensures Middle Column (col-md-4) + Right Column (col-md-8) = 12 total grid units!
        $rightChecksheetCol.removeClass('col-md-6').addClass('col-md-7');
    } else {
        // --- ACTION 2: OPEN / EXPAND BACK OUT ---
        $collapsedStrip.addClass('d-none');
        
        // Drop the narrow strip override and restore native Bootstrap col-md-2 allocation
        $wrapper.removeClass('pane-shrunk-narrow-strip').addClass('col-md-2');
        $headerArea.removeClass('d-none');
        $timelineGroup.removeClass('d-none');

        // Restore baseline check sheet column space: Shift column 3 right back from col-md-8 down to col-md-6
        // This brings back original layout balance: col-md-2 (Left) + col-md-4 (Middle) + col-md-6 (Right) = 12 units!
        $rightChecksheetCol.removeClass('col-md-7').addClass('col-md-6');
    }
});

// SYSTEM CLEAN UP INITIALIZER GATEWAY: FORCE EXPANDED OPEN LIFECYCLE WHEN OPENING ANOTHER DIRECTORY ACCOUNT
$('#DataTable').on('click', '.btn-attend-patient', function() {
    $('#history_header_area').removeClass('d-none');
    $('#visit_dates_timeline_group').removeClass('d-none');
    $('#collapsed_open_trigger_strip').addClass('d-none');
    
    // Re-enforce native grid class states assignments
    $('#history_column_wrapper').removeClass('pane-shrunk-narrow-strip').addClass('col-md-2');
    $('#right_checksheet_column').removeClass('col-md-8').addClass('col-md-6');
});

$(document).ready(function() {
    
    // Initialize Choices.js with clean single-token rules
    var labSelectElement = document.getElementById('c_lab_test_ids');
    if (labSelectElement) {
        labChoicesInstance = new Choices(labSelectElement, {
            removeItemButton: true,      // Renders the tiny "x" icon button to drop tags
            maxItemCount: 15,            // Ceiling constraint limit
            searchEnabled: true,         // Adds high-fidelity fast filter text input
            duplicateItemsAllowed: false,
            placeholder: true,
            placeholderValue: 'Choose tests...'
            // REMOVED: classNames configuration block has been removed to stop space token crashes
        });
    }

    // RESET LAYER: WIPE OUT PREVIOUS TAGS SELECTIONS WHEN A PATIENT PROFILE RE-LOADS
    // Put this management line directly inside your active ".btn-attend-patient" click script:
    if (labChoicesInstance) {
        labChoicesInstance.removeActiveItems(); // Drops old tags instantly
    }
});

   // DOSAGE SPLIT STRUCTURAL STRINGS COMPILER ENGINE
function compileDosageString() {
    var qty = $('#dose_qty').val().trim();
    var freq = $('#dose_freq').val();
    var duration = $('#dose_duration').val().trim();

    if (qty !== '' && duration !== '') {
        // Automatically builds: "2 tabs x 3 times daily for 5 days"
        var compiled = qty + ' x ' + freq + ' for ' + duration;
        $('#compiled_dosage_instruction').val(compiled);
    } else {
        $('#compiled_dosage_instruction').val('');
    }
}

// Bind live text change listeners to compile inputs instantly
$('#dose_qty, #dose_freq, #dose_duration').on('input change', function() {
    compileDosageString();
});



// ====================================================================
// UTILITY ENGINES: DROPDOWN ALPHABETIZERS & SERIAL REINDEXERS
// ====================================================================

function alphabetizeDropdownOptions(selectElementId) {
    var $select = $(selectElementId);
    var $options = $select.find('option');
    var $first = $options.first();

    var $sorted = $options
        .not(':first')
        .sort(function (a, b) {
            return $(a).text().localeCompare($(b).text());
        });

    $select.empty().append($first).append($sorted).val('');
}

function recalculateTableSerialBadges(tbodySelector) {
    $(tbodySelector).find('tr.prescription-item-row').each(function (idx) {
        $(this).find('td:first .badge').text(idx + 1);
    });
} 

 
// ====================================================================
// MASTER DROPDOWN SNAPSHOTS
// ====================================================================

window.originalOralOptions = $('#c_drug_id').html();
window.originalInjectOptions = $('#c_inject_id').html();

window.activePrescriptionsBasket = [];
// ====================================================================
// DROPDOWN SYNCHRONIZER
// ====================================================================

function syncPrescriptionDropdowns() {

    // Restore original options
    $('#c_drug_id').html(window.originalOralOptions);
    $('#c_inject_id').html(window.originalInjectOptions);

    // Remove already-selected drugs from dropdowns
    (window.activePrescriptionsBasket || []).forEach(function(item) {
        var isInjection = String(item.dosage || '').toUpperCase().includes('[INJECTION]');
        var selectId = isInjection ? '#c_inject_id' : '#c_drug_id';
        $(selectId).find(`option[value="${item.drug_id}"]`).remove();
    });

    alphabetizeDropdownOptions('#c_drug_id');
    alphabetizeDropdownOptions('#c_inject_id');
}
// ====================================================================
// TABLE RENDERER
// ====================================================================

function refreshPrescriptionTablesUI() {

    var $oralTbody = $('#oral_debug_tbody').empty();
    var $injectTbody = $('#inject_debug_tbody').empty();

    var oralSerial = 0;
    var injectSerial = 0;
console.log('Rendering basket:',window.activePrescriptionsBasket.length);
    (window.activePrescriptionsBasket || []).forEach(function(item) {
        console.log(item);

        var isInjection = String(item.dosage || '').toUpperCase().includes('[INJECTION]');
        var serial = isInjection ? ++injectSerial : ++oralSerial;

        var cleanName = $('<div>').text(item.drug_name || 'Unknown Medication').html();

        var cleanDosage = $('<div>').text(item.dosage || '').html();

        var row = `
            <tr class="prescription-item-row"
                data-uid="${item.uid}">

                <td>
                    <span class="badge bg-dark">
                        ${serial}
                    </span>

                    <input type="hidden"
                           name="drug_ids[]"
                           value="${item.drug_id}">

                    <input type="hidden"
                           name="dosage_instructions[]"
                           value="${cleanDosage}">
                </td>

                <td class="fw-semibold text-dark">
                    ${cleanName}
                </td>

                <td class="font-monospace ${isInjection ? 'text-danger fw-semibold' : 'text-primary'}">
                    ${cleanDosage}
                </td>

                <td>
                    <span class="badge ${isInjection ? 'bg-danger' : 'bg-success'}">
                        ${isInjection ? 'INJ' : 'ORAL'}
                    </span>
                </td>

                <td class="text-center">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-drop-basket-item-global"
                            data-uid="${item.uid}">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>

            </tr>
        `;

        (isInjection ? $injectTbody : $oralTbody).append(row);
        
    });

    if (!$oralTbody.children().length) {
        $oralTbody.html(`
            <tr id="empty_oral_placeholder">
                <td colspan="5"
                    class="text-center text-muted py-3">
                    No oral tablets prescribed for this session yet.
                </td>
            </tr>
        `);
    }

    if (!$injectTbody.children().length) {
        $injectTbody.html(`
            <tr id="empty_inject_placeholder">
                <td colspan="5"
                    class="text-center text-muted py-3">
                    No clinical injections ordered.
                </td>
            </tr>
        `);
    }

    syncPrescriptionDropdowns();
}
$(document).on('click', '#btn_add_oral_to_basket', function(e) {

    e.preventDefault();

    var drugId = $('#c_drug_id').val();
    var drugName = $('#c_drug_id option:selected').text();

    var qty = $('#dose_qty').val().trim();
    var freq = $('#dose_freq').val();
    var duration = $('#dose_duration').val().trim();

    if (!drugId || !qty || !freq || !duration) {
        return;
    }

    window.activePrescriptionsBasket.push({
        uid: Date.now() + '_' + Math.random(),
        drug_id: drugId,
        drug_name: drugName,
        dosage: `${qty} x ${freq} for ${duration}`
    });
    refreshPrescriptionTablesUI();
    $('#c_drug_id').val('');
    $('#dose_qty').val('');
    $('#dose_duration').val('');
});


$(document).on('click', '#btn_add_inject_to_basket', function(e) {

    e.preventDefault();

    var drugId = $('#c_inject_id').val();
    var drugName = $('#c_inject_id option:selected').text();

    var volume = $('#inj_qty').val().trim();
    var route = $('#inj_route').val();
    var duration = $('#inj_duration').val().trim();

    if (!drugId || !volume || !route || !duration) {
        return;
    }

    window.activePrescriptionsBasket.push({
        uid: Date.now() + '_' + Math.random(),
        drug_id: drugId,
        drug_name: drugName,
        dosage: `[INJECTION] ${volume} via ${route} for ${duration}`
    });

    refreshPrescriptionTablesUI();

    $('#c_inject_id').val('');
    $('#inj_qty').val('');
    $('#inj_route').val('');
    $('#inj_duration').val('');
});
$(document).on('click', '.btn-drop-basket-item-global', function(e) {

    e.preventDefault();

    var uid = $(this).data('uid');

    window.activePrescriptionsBasket =
        window.activePrescriptionsBasket.filter(function(item) {
            return item.uid !== uid;
        });

    refreshPrescriptionTablesUI();
});   
// ====================================================================
// SNEAT DASHBOARD SYSTEM MASTER ENGINE: DOCTOR CLINICAL CONSULTATION SUITE
// ====================================================================

// 1. EVENT INTERCEPTOR: OPEN CONSULTATION MODAL AND GENERATE CHRONOLOGICAL HISTORICAL PORTFOLIO
$('#DataTable').on('click', '.btn-attend-patient', function(e) {
    e.preventDefault();
    var rawPayload = $(this).attr('data-payload');
    if (!rawPayload) return;
    

    try {
        // Decode transport parameter string token algorithm layer safely
        var decoded = atob(rawPayload);
        var patient = JSON.parse(decoded);

        // Wipe out text parameters left inside input fields cleanly
        var formElement = $('#consultationForm')[0];
        if (formElement) formElement.reset();

        // Bind core reference patient data parameter metrics onto hidden tracking layers
        $('#c_patient_id').val(patient.id);
        $('#c_vitals_id').val(patient.vital_id);
        $('#modal_patient_title').html('<i class="bx bx-user me-2"></i> Patient Name: ' + $('<div>').text(patient.name).html() + ' <small class="text-white-50">(' + patient.folder_number + ' • ' + patient.calculated_age + ' yrs old)</small>');

        // Map Live Triage parameters structural metrics elements onto summary labels
        $('#v_bp').text(patient.bp || 'N/A');
        $('#v_pulse').text(patient.pulse || '0');
        $('#v_temp').text(patient.temperature || '0.0');
        $('#v_weight').text(patient.weight || '0.0');
        $('#v_spo2').text(patient.spo2 || '0');

        // Remove any old consultation hidden tracking variables first
        $('#consultationForm').find('input[name="consultation_id"]').remove();
// ====================================================================
// CONSOLIDATED WORKSPACE PIPELINE: LOAD CONSULTATION INTO GLOBAL BASKET
// ====================================================================
if (patient.today_consultation_id) {
    try { 
        var $form = $('#consultationForm');
        $form.find('input[name="consultation_id"]').remove();
        $form.append(` <input type="hidden" name="consultation_id" value="${patient.today_consultation_id}">`);

        // ------------------------------------------------------------
        // CONSULTATION DETAILS
        // ------------------------------------------------------------
        $('#c_complaints').val(patient.today_complaints || '');
        $('#c_diagnosis').val(patient.today_diagnosis || '');
        $('#c_admission_status').val(
            patient.today_admission_status || 'Outpatient'
        );

      
        // ------------------------------------------------------------
        // RESET TABLES
        // ------------------------------------------------------------
        $('#oral_debug_tbody').empty();
        $('#inject_debug_tbody').empty();

        // ------------------------------------------------------------
        // RESET GLOBAL PRESCRIPTION BASKET
        // ------------------------------------------------------------
        window.activePrescriptionsBasket = [];

        // ------------------------------------------------------------
        // LOAD EXISTING PRESCRIPTIONS INTO MEMORY
        // ------------------------------------------------------------
        (patient.today_prescriptions || []).forEach(function (item) {

            var dosage = String(item.dosage_instruction || item.dosage || '').trim();
            if (!item.drug_id || !dosage) return;
            window.activePrescriptionsBasket.push({
                uid: 'RX_' + Date.now() + '_' + Math.random().toString(36).substring(2, 10), drug_id: item.drug_id,
                drug_name: item.drug_name || 'Unknown Medication',dosage: dosage});});

        // ------------------------------------------------------------
        // REBUILD TABLES + DROPDOWNS FROM MEMORY
        // ------------------------------------------------------------
        console.log('ORAL TBODY:', $('#oral_debug_tbody').length);
console.log('INJECT TBODY:', $('#inject_debug_tbody').length);
console.log('BASKET:', window.activePrescriptionsBasket);

setTimeout(function () {
    refreshPrescriptionTablesUI();
    syncPrescriptionDropdowns();
}, 100); 

    } catch (err) {
        console.error(err);
        var errorRow = `<tr><td colspan="5"class="text-center text-danger py-3"> Error: ${err.message}</td></tr>`;
        $('#oral_debug_tbody').html(errorRow);
        $('#inject_debug_tbody').html(errorRow);
    }
}
        



        // CONFIGURE THE EXTERNAL PRINTING PATH ROUTING LINK WITH THE ACTIVE PATIENT REFERENCE INDEX
        $('#btnPrintFullHistory').attr('href', 'print_history.php?patient_id=' + patient.id); 
        var $todayLabsContainer = $('#v_today_labs_container');
        $todayLabsContainer.empty();

        var todayDateString = new Date().toDateString();
        var todayLabsMarkup = '';
        var todayLabsCount = 0;

        if (patient.medical_history && patient.medical_history.length > 0) {
            var todayConsults = patient.medical_history.filter(function(h) {
                return new Date(h.created_at).toDateString() === todayDateString;
            });

            todayConsults.forEach(function(c) {
                if (c.labs && c.labs.length > 0) {
                    c.labs.forEach(function(lab) { 
                        
                        todayLabsCount++;
                        
                        var statusBadge = (lab.status === 'Completed') 
                            ? '<span class="badge bg-success-subtle text-success py-1 font-monospace text-xs">READY</span>' 
                            : '<span class="badge bg-warning-subtle text-warning py-1 font-monospace text-xs">PENDING</span>';
                        
                        // NEW FINTECH BUTTON: OPENS ATTACHED FILE DIRECTLY IN A NEW TAB
                        var openFileButton = '';
                        if (lab.lab_result && lab.attached_file) {
                            openFileButton = `
                                <div class="mt-2 text-end">
                                    <a href="${lab.attached_file}" target="_blank" class="btn btn-xs btn-outline-info">
                                        <i class="bx bx-show me-1"></i> Open Document in New Tab
                                    </a>
                                </div>
                            `;
                        }

                        var findingsText = (lab.lab_result) 
                            ? `<div class="mt-1 p-2 bg-light border-start border-info rounded text-dark small font-monospace" style="font-size:0.76rem;">
                                    <b>Findings:</b> ${$('<div>').text(lab.lab_result).html()}
                                    ${openFileButton}
                               </div>` 
                            : '<div class="text-muted small mt-1 italic" style="font-size:0.75rem;">Waiting for lab technician input...</div>';

                        todayLabsMarkup += `
                            <div class="p-2 border-bottom last-border-none mb-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small"><i class="bx bx-chevron-right text-info"></i> ${$('<div>').text(lab.test_name).html()}</span>
                                    ${statusBadge}
                                </div>
                                ${findingsText}
                            </div>
                        `;
                    });
                }
            });
        }

        if (todayLabsCount > 0) {
            $todayLabsContainer.append(todayLabsMarkup);
        } else {
            $todayLabsContainer.append('<div class="text-center text-muted small py-3"><i class="bx bx-info-circle d-block mb-1 fs-4 text-secondary"></i> No laboratory tests ordered for today\'s session yet.</div>');
        }


        // GENERATE DYNAMIC CHRONOLOGICAL HISTORY TIMELINE ACCORDION OFFLINE
        var $accordion = $('#historyAccordion');
        $accordion.empty();
         // ====================================================================
        // FINTECH-GRADE MEDICAL CHART TIMELINE GENERATOR
        // ==================================================================== 

        var pastVisits = patient.vitals_history || [];
        var consultHistory = patient.medical_history || [];

        // 1. ISOLATE TODAY'S ACTIVE TRIAGE PARAMETERS AT THE TOP OF THE TIMELINE
        var todayDateString = new Date().toDateString();
        var hasTriageToday = false;

        pastVisits.forEach(function(visit) {
            if (new Date(visit.created_at).toDateString() === todayDateString) {
                hasTriageToday = true;
            }
        });

        if (hasTriageToday) {
            var latestTriage = pastVisits[0]; // First element via SQL DESC ordering matrix
            var todayBanner = `
                <div class="card bg-label-primary border border-primary-subtle mb-3" style="border-radius: 10px; background-color: #e0f2fe;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary text-uppercase font-monospace text-xs" style="font-size: 0.68rem; letter-spacing: 0.05em;">01 ACTIVE SESSION TODAY</span>
                            <small class="text-primary fw-bold"><i class="bx bx-time-five"></i> Live Triage</small>
                        </div>
                        <div class="row g-2 text-dark font-monospace" style="font-size: 0.82rem; font-weight:600;">
                            <div class="col-6"><i class="bx bx-heart text-danger me-1"></i> BP: <span class="text-danger fw-bold">${$('<div>').text(latestTriage.bp).html()}</span></div>
                            <div class="col-6"><i class="bx bx-pulse text-danger me-1"></i> Pulse: ${parseInt(latestTriage.pulse)} BPM</div>
                            <div class="col-4"><i class="bx bx-thermometer text-warning me-1"></i> Temp: ${parseFloat(latestTriage.temperature)}°C</div>
                            <div class="col-4"><i class="bx bx-dumbbell text-secondary me-1"></i> Wt: ${parseFloat(latestTriage.weight)}Kg</div>
                            <div class="col-4"><i class="bx bx-cloud text-info me-1"></i> SpO2: ${parseInt(latestTriage.spo2)}%</div>
                        </div>
                    </div>
                </div>
            `;
            $accordion.append(todayBanner);
        }

        // 2. CHRONOLOGICAL ACCORDION TIMELINE LOOP FOR PAST HISTORICAL VISIT PARAMS
        if (pastVisits.length > 0) {
            var accordionMarkup = '';
            var displayedHistoricalCount = 0;

            pastVisits.forEach(function(visit, idx) {
                var visitDateObj = new Date(visit.created_at);
                
                // Skip today's active triage row parameter from doubling up in history lists below
                if (visitDateObj.toDateString() === todayDateString) return;

                displayedHistoricalCount++;
                
                // Keep the first true historical visit record pane open automatically
                var showClass = (displayedHistoricalCount === 1) ? 'show' : '';
                var collapsedClass = (displayedHistoricalCount === 1) ? '' : 'collapsed';
                
                var visitDate = visitDateObj.toLocaleDateString('en-GH', {
                    day: 'numeric', month: 'short', year: 'numeric' 
                });

                // Assemble Triage Block Row Component
                var vitalsSection = `
                    <div class="mb-2 p-2 bg-white border rounded shadow-xs">
                        <small class="text-danger fw-bold d-block mb-1 font-monospace" style="font-size: 0.72rem;"><i class="bx bx-heart"></i> TRIAGE RECORD</small>
                        <div class="row g-1 text-muted" style="font-size: 0.78rem;">
                            <div class="col-6">BP: <b class="text-dark">${$('<div>').text(visit.bp).html()}</b></div>
                            <div class="col-6">Pulse: <b class="text-dark">${parseInt(visit.pulse)} BPM</b></div>
                            <div class="col-4">Temp: <b class="text-dark">${parseFloat(visit.temperature)}°C</b></div>
                            <div class="col-4">Weight: <b class="text-dark">${parseFloat(visit.weight)}Kg</b></div>
                            <div class="col-4">SpO2: <b class="text-dark">${parseInt(visit.spo2)}%</b></div>
                        </div>
                    </div>
                `;

                // Subquery Filters Scans: Intercept matching consultations recorded on this timestamp day
                var medicalNotes = '';
                var labsSection = '';
                var drugsSection = '';

                if (consultHistory && consultHistory.length > 0) {
                    var matchedConsults = consultHistory.filter(function(h) {
                        return new Date(h.created_at).toDateString() === visitDateObj.toDateString();
                    });

                    if (matchedConsults.length > 0) {
                        matchedConsults.forEach(function(c) {
                            // Extract evaluation text strings notes
                            medicalNotes = `
                                <div class="mb-2 p-2 bg-white border rounded shadow-xs">
                                    <small class="text-success fw-bold d-block mb-1 font-monospace" style="font-size: 0.72rem;"><i class="bx bx-spreadsheet"></i> CLINICAL NOTES</small>
                                    <div class="mb-1 text-dark" style="font-size: 0.8rem;"><b>Complaints:</b> ${$('<div>').text(c.complaints || 'N/A').html()}</div>
                                    <div class="text-primary fw-semibold" style="font-size: 0.8rem;"><b>Diagnosis:</b> ${$('<div>').text(c.diagnosis || 'N/A').html()}</div>
                                </div>
                            `;

                            // NEW FINTECH ADDITION: PARSE SUB-NESTED LABORATORY JOINS METRICS PAYLOAD
                            if (c.labs && c.labs.length > 0) {
                                labsSection = `
                                    <div class="mb-2 p-2 bg-white border rounded shadow-xs">
                                        <small class="text-info fw-bold d-block mb-1 font-monospace" style="font-size: 0.72rem;"><i class="bx bx-test-tube"></i> LABORATORY ORDERS</small>
                                        <ul class="list-unstyled mb-0 ps-0" style="font-size: 0.78rem;">
                                `;
                                c.labs.forEach(function(lab) {
                                    var statusBadge = (lab.status === 'Completed') 
                                        ? '<span class="badge bg-label-success bg-success-subtle text-success py-0.5 px-1 font-monospace">READY</span>' 
                                        : '<span class="badge bg-label-warning bg-warning-subtle text-warning py-0.5 px-1 font-monospace">PENDING</span>';
                                    
                                    // Add Click-to-View anchor attachment link if file parameter token exists in data nodes
                                    var fileAnchor = (lab.lab_result && lab.attached_file) 
                                        ? `<br><a href="${lab.attached_file}" target="_blank" class="btn btn-xs btn-link p-0 mt-1 text-info fw-bold"><i class="bx bx-paperclip small"></i> View Scanned Report</a>` 
                                        : '';

                                    var resultText = (lab.lab_result) 
                                        ? `<div class="mt-1 p-1 bg-light border-start border-info rounded text-dark text-xs font-monospace" style="font-size: 0.74rem;"><b>Result:</b> ${$('<div>').text(lab.lab_result).html()}${fileAnchor}</div>` 
                                        : '';

                                    labsSection += `
                                        <li class="mb-1 border-bottom pb-1 last-border-none">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-dark fw-semibold"><i class="bx bx-chevron-right text-info"></i> ${$('<div>').text(lab.test_name).html()}</span>
                                                ${statusBadge}
                                            </div>
                                            ${resultText}
                                        </li>
                                    `;
                                });
                                labsSection += `</ul></div>`;
                            }

                            // NEW FINTECH ADDITION: PARSE SUB-NESTED DRUG OUTCOMES DISPENSATION RECORDS 
                            if (c.drugs && c.drugs.length > 0) {
                                drugsSection = `
                                    <div class="mb-2 p-2 bg-white border rounded shadow-xs">
                                        <small class="text-warning fw-bold d-block mb-1 font-monospace" style="font-size: 0.72rem;"><i class="bx bx-capsule"></i> PHARMACY DISPATCHES</small>
                                        <ul class="list-unstyled mb-0 ps-0" style="font-size: 0.78rem;">
                                `;
                                c.drugs.forEach(function(drug) {
                                    var dispenseBadge = (drug.status === 'Dispensed') 
                                        ? '<span class="text-success fw-bold font-monospace text-xs"><i class="bx bx-check-circle"></i> Issued</span>' 
                                        : '<span class="text-muted font-monospace text-xs"><i class="bx bx-time"></i> Unpaid/Queue</span>';

                                    drugsSection += `
                                        <li class="d-flex justify-content-between align-items-start mb-1 border-bottom pb-1 last-border-none">
                                            <div>
                                                <div class="text-dark fw-semibold"><i class="bx bx-chevron-right text-warning"></i> ${$('<div>').text(drug.drug_name).html()}</div>
                                                <small class="text-muted d-block ps-3" style="font-size: 0.74rem;">Dosage: ${$('<div>').text(drug.dosage).html()}</small>
                                            </div>
                                            ${dispenseBadge}
                                        </li>
                                    `;
                                });
                                drugsSection += `</ul></div>`;
                            }
                        });
                    }
                }

                // Compile subcomponents layout inside expanding cards tracking block frameworks
                accordionMarkup += `
                    <div class="accordion-item card border shadow-none mb-2" style="border-radius:8px;">
                        <h2 class="accordion-header" id="heading-group-${idx}">
                            <button type="button" class="accordion-button ${collapsedClass} py-2.5 px-3 small fw-bold text-dark" data-bs-toggle="collapse" data-bs-target="#visit-group-${idx}" aria-expanded="${displayedHistoricalCount === 1}" aria-controls="visit-group-${idx}">
                                <i class="bx bx-calendar text-primary me-2"></i> Archive Visit: ${visitDate}
                            </button>
                        </h2>
                        <div id="visit-group-${idx}" class="accordion-collapse collapse ${showClass}" aria-labelledby="heading-group-${idx}" data-bs-parent="#historyAccordion">
                            <div class="accordion-body px-2 py-2 bg-light-subtle">
                                ${vitalsSection}
                                ${medicalNotes}
                                ${labsSection}
                                ${drugsSection}
                            </div>
                        </div>
                    </div>
                `;
            });
            $accordion.append(accordionMarkup);
        }

        // Handle empty scenario if no historical data tracks are active
        if (!hasTriageToday && (!pastVisits || pastVisits.length === 0 || displayedHistoricalCount === 0)) {
            $accordion.append('<div class="text-center text-muted small py-4"><i class="bx bx-folder-open d-block fs-3 mb-1"></i> No prior clinical record files logged inside system archives for this patient.</div>');
        }

        // Open the consultation workspace portal smoothly
        $('#consultationModal').modal('show');

    } catch (err) {
        console.error("Clinical timeline framework compilation failure exception caught: ", err);
        Swal.fire('Handoff Error', 'Unable to trace historic portfolio metrics data values.', 'error');
    }
});


// 2. FORM ENGINE CONTROLLER: POST CONSULTATION LOG NOTES AND FLUSH DYNAMIC WAITING QUEUE LISTS
$('#consultationForm').on('submit', function(e) {
    e.preventDefault();
    var form = this;

    // A. CLEAR ALL OLD DANGER VALIDATION TEXT NODES AND CLASS HIGHLIGHTS
    $('#c_complaints, #c_diagnosis').removeClass('is-invalid');
    $('#complaints_feedback, #diagnosis_feedback').empty().hide();

    // Read form values and state metrics parameters
    var isTreatmentComplete = $('#chk_treatment_complete').is(':checked');
    var selectedLabsArray = $('#c_lab_test_ids').val() || [];
    var isLabOrdered = selectedLabsArray.length > 0;

    // FIXED ELEMENT SELECTORS: Point directly to new debugging table bodies rows
    var totalOralDrugsInBasket = $('#oral_debug_tbody tr.prescription-item-row').length;
    var totalInjectionsInBasket = $('#inject_debug_tbody tr.prescription-item-row').length;
    var isAnyMedicinePrescribed = (totalOralDrugsInBasket + totalInjectionsInBasket) > 0;

    var complaintsInput = $('#c_complaints');
    var diagnosisInput = $('#c_diagnosis');
    var complaintsVal = complaintsInput.val().trim();
    var diagnosisVal = diagnosisInput.val().trim();
    var admissionDispositionText = $('#c_admission_status option:selected').text();

    var hasValidationErrors = false;

    // 1. ABSOLUTE STOP GATE: BLOCK DISPATCH IF WORKSPACE IS COMPLETELY BLANK
    if (!isTreatmentComplete && !isLabOrdered && !isAnyMedicinePrescribed && complaintsVal === '' && diagnosisVal === '') {
        Swal.fire({
            title: 'Empty Checksheet Submission',
            text: 'You cannot dispatch an entirely blank evaluation chart form. Please enter evaluation notes or order treatment metrics.',
            icon: 'warning',
            confirmButtonColor: '#ff9f43'
        });
        return false;
    }

    // 2. STEP-BY-STEP CHECK VALIDATIONS FOR COMPLETED TREATMENT SIGN-OFF
    if (isTreatmentComplete) {
        if (complaintsVal === '') {
            complaintsInput.addClass('is-invalid');
            $('#complaints_feedback').text('Patient complaints description is mandatory when signing off treatment complete.').show();
            hasValidationErrors = true;
        }
        if (diagnosisVal === '') {
            diagnosisInput.addClass('is-invalid');
            $('#diagnosis_feedback').text('Primary clinical diagnosis outcome notes are required when signing off treatment complete.').show();
            hasValidationErrors = true;
        }
        if (!isAnyMedicinePrescribed) {
            Swal.fire({
                title: 'Prescription Item Required',
                text: 'You marked treatment as complete. You must add at least one medication or injection entry item into the active prescriptions basket lists before checkout.',
                icon: 'warning',
                confirmButtonColor: '#ff3e1d'
            });
            hasValidationErrors = true;
        }
    }

    // Stop execution instantly if any red flags were raised above
    if (hasValidationErrors) {
        $('.is-invalid:first').focus();
        return false;
    }

    // 3. SET RUNTIME CONFIGURATION PARAMETERS AND COLOR SCHEMA STYLINGS
    var alertTitle = 'Sign & Dispatch Case File?';
    var confirmBtnColor = '#696cff'; // Sneat Primary Indigo Accent
    var alertHtmlContent = `<p class="text-muted small">This saves clinical diagnosis chart updates and routes active requests directly to assigned clinic departments.</p>`;

    // SCENARIO B: IF UNCHECKED AND LAB DISPATCH CHANNELS ARE DETECTED
    if (!isTreatmentComplete && isLabOrdered) {
        alertTitle = 'Send Patient to Lab?';
        confirmBtnColor = '#03c3ec'; // Sneat Info Cyan Accent
        alertHtmlContent = `
            <div class="alert alert-info border-0 text-start small mb-0" style="border-radius:8px;">
                <i class="bx bx-info-circle me-1"></i> The patient will be sent to the laboratory panel and temporarily hidden from your queue view. They pop back onto your board automatically once test reports are published.
            </div>`;
    }

    // SCENARIO C: IF TREATMENT COMPLETE SWITCH IS TICKED (BUILD PREMIUM SUMMARY ACCORDIONS)
    if (isTreatmentComplete) {
        alertTitle = 'Confirm Treatment Checkout Summary';
        confirmBtnColor = '#28c76f'; // Sneat Success Emerald Accent

        // Unpack medication labels from table rows text properties parameters
        var oralDrugsListMarkup = '';
        $('#oral_debug_tbody tr.prescription-item-row').each(function() {
            var name = $(this).find('td:eq(1)').text().trim();
            var dose = $(this).find('td:eq(2)').text().trim();
            oralDrugsListMarkup += `<li><i class="bx bx-chevron-right text-warning"></i> <b>${name}</b> - <small class="text-muted">${dose}</small></li>`;
        });
        if (oralDrugsListMarkup === '') oralDrugsListMarkup = '<li class="text-muted italic small ps-2">No oral tablets prescribed.</li>';

        var injectionsListMarkup = '';
        $('#inject_debug_tbody tr.prescription-item-row').each(function() {
            var name = $(this).find('td:eq(1)').text().trim();
            var dose = $(this).find('td:eq(2)').text().trim().replace('[INJECTION] ', '');
            injectionsListMarkup += `<li><i class="bx bx-radio-circle-marked text-danger"></i> <b>${name}</b> - <small class="text-danger">${dose}</small></li>`;
        });
        if (injectionsListMarkup === '') injectionsListMarkup = '<li class="text-muted italic small ps-2">No emergency clinical injections ordered.</li>';

        var labOrdersListMarkup = '';
        if (isLabOrdered) {
            $('#c_lab_test_ids option:selected').each(function() {
                labOrdersListMarkup += `<li><i class="bx bx-check-double text-info"></i> ${$(this).text()}</li>`;
            });
        } else {
            labOrdersListMarkup = '<li class="text-muted italic small ps-2">No dynamic laboratory requests generated.</li>';
        }

        // Build premium summary accordion layout package text string
        alertHtmlContent = `
            <div class="accordion text-start shadow-none border rounded" id="swalReviewAccordion" style="font-size: 0.82rem; border-radius:8px; overflow:hidden;">
                
                <!-- ACCORDION PANEL 1: EVALUATION SHEET -->
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button py-2 px-3 fw-bold text-dark bg-light shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#swalCol1">
                            <i class="bx bx-spreadsheet text-success me-2"></i> 1. Diagnostics Summary
                        </button>
                    </h2>
                    <div id="swalCol1" class="accordion-collapse collapse show" data-bs-parent="#swalReviewAccordion">
                        <div class="accordion-body bg-white p-2.5">
                            <div class="mb-2"><b>Complaints:</b> <div class="text-muted p-1 bg-light rounded small mt-0.5">${$('<div>').text(complaintsVal).html()}</div></div>
                            <div><b>Diagnosis:</b> <div class="text-primary p-1 bg-light rounded small mt-0.5" style="font-weight:600;">${$('<div>').text(diagnosisVal).html()}</div></div>
                        </div>
                    </div>
                </div>

                <!-- ACCORDION PANEL 2: PRESCRIPTION ITEM ARRAYS -->
                <div class="accordion-item border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark bg-light shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#swalCol2">
                            <i class="bx bx-capsule text-warning me-2"></i> 2. Prescriptions Basket Items
                        </button>
                    </h2>
                    <div id="swalCol2" class="accordion-collapse collapse" data-bs-parent="#swalReviewAccordion">
                        <div class="accordion-body bg-white p-2.5">
                            <div class="mb-2">
                                <span class="text-xs fw-bold text-uppercase tracking-wider text-muted font-monospace d-block mb-1">Oral Medicines:</span>
                                <ul class="list-unstyled mb-0 ps-1">${oralDrugsListMarkup}</ul>
                            </div>
                            <div>
                                <span class="text-xs fw-bold text-uppercase tracking-wider text-muted font-monospace d-block mb-1">Clinical Injections:</span>
                                <ul class="list-unstyled mb-0 ps-1">${injectionsListMarkup}</ul>
                            </div>
                        </div>
                    </div>
                </div>
 
                                 <!-- ACCORDION CARD 3: LAB TESTING DISPATCHES AND CLINICAL DISPOSITION -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#swalCol3">
                            <i class="bx bx-bed text-info me-2"></i> 3. Dispatches & Disposition
                        </button>
                    </h2>
                    <div id="swalCol3" class="accordion-collapse collapse" data-bs-parent="#swalReviewAccordion">
                        <div class="accordion-body bg-white p-2.5">
                            <div class="mb-2">
                                <span class="text-xs fw-bold text-uppercase tracking-wider text-muted font-monospace d-block mb-1">Laboratory Orders:</span>
                                <ul class="list-unstyled mb-0 ps-1">${labOrdersListMarkup}</ul>
                            </div>
                            <div>
                                <b>Final Disposition Status:</b> 
                                <span class="badge bg-label-secondary border text-dark ms-1 py-1 font-monospace">${$('<div>').text(admissionDispositionText).html()}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <p class="text-center text-muted mt-2 mb-0" style="font-size:0.75rem;"><i class="bx bx-check-shield text-success"></i> Verify all parameters before final digital signature authorization.</p>
        `;
    }

    // 4. TRIGGER THE DYNAMIC MODAL BOX PROMPT WINDOW OVERLAY
    Swal.fire({
        title: alertTitle,
        html: alertHtmlContent,
        icon: isTreatmentComplete ? 'info' : 'question',
        showCancelButton: true,
        confirmButtonColor: confirmBtnColor,
        cancelButtonColor: '#8592a3', // Muted Secondary Slate Gray
        confirmButtonText: isTreatmentComplete ? 'Sign & Close Folder File' : 'Confirm & Dispatch Case',
        cancelButtonText: 'Review Checksheet',
        width: isTreatmentComplete ? '460px' : '400px'
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({ 
            title: 'Encrypting Case Profile Logs...', 
            html: 'Signing digital diagnostic logs, routing orders and updating dynamic queue listings channels parameters maps...',
            allowOutsideClick: false, 
            didOpen: () => { Swal.showLoading(); } 
        });

        // Add the checkbox value to serialize so the server knows whether to remove the patient or not
        var submitData = $(form).serialize() + '&treatment_complete=' + isTreatmentComplete;

        $.ajax({
            url: server_url,
            type: 'POST',
            data: submitData,
            dataType: 'json',
            success: function(response) {
                Swal.close();

                if (response.status === 'success') {
                    Swal.fire({
                        title: isTreatmentComplete ? 'Case Closed' : 'Sent to Lab',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Close fullscreen modal workspace suite panel cleanly
                        $('#consultationModal').modal('hide');
                        form.reset();
                        
                        // Wipe array basket line nodes from visual layout arrays cache matrices
                        $('.prescription-item-row').remove();
                        $('#empty_oral_placeholder, #empty_inject_placeholder').show();
                        $('#chk_treatment_complete').prop('checked', false);
                        
                        // Clear split dosage and Choices multi-select caching inputs manually on reset
                        $('#dose_qty').val(''); $('#dose_freq').val(''); $('#dose_duration').val(''); $('#compiled_dosage_instruction').val('');
                        if (typeof labChoicesInstance !== "undefined" && labChoicesInstance !== null) {
                            labChoicesInstance.removeActiveItems();
                        }
                        
                        // Reset disposition status selectors back to defaults out outpatient configurations
                        $('#c_admission_status').val('Outpatient');

                        // REPOPULATE ACTIVE QUEUE DATA CELL ROWS LIVES SAFELY WITHOUT RELOADING
                        updateQueueUI(response.data); 
                    });
                } else {
                    Swal.fire('Transaction Aborted', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error("Clinical diagnostic submit transit crashed: " + error);
                console.error("responseText: " + xhr.responseText);
                Swal.fire({
                    title: 'Network Timeout Failure',
                    text: 'An unexpected connection or database pipeline crash occurred. Please check system exception logs.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        });
    });
});




                    /**
 * Clears the old doctor queue rows via .empty() and draws the fresh list of waiting patients.
 * 
 * @param {Array} freshQueueArray - The fresh list of waiting patients sent from the server
 */
function updateQueueUI(freshQueueArray) {
    // 1. Find the table body element and clear out all old rows using .empty()
    var $tbody = $('#DataTable').find('tbody');
    $tbody.empty();
    $tbody.append(`<tr></tr>`);
    sn = 1;

    var rowsMarkup = '';

    // 3. Loop through each patient row in the fresh list
    freshQueueArray.forEach(function(patient) {
        // Safe string encoding to build the Base64 data attribute packet for the Attend button
        var stringified = JSON.stringify(patient);
        var base64Payload = btoa(unescape(encodeURIComponent(stringified)));

        // Clean text values to keep data output text-safe (Anti-XSS text protection)
        var cleanFolder = $('<div>').text(patient.folder_number).html();
        var cleanName = $('<div>').text(patient.name).html();
        var cleangender = $('<div>').text(patient.gender).html();
        var cleanBp = $('<div>').text(patient.bp).html();
        var cleanTemp = $('<div>').text(patient.temperature).html();
        var cleanAge = parseInt(patient.calculated_age) || 0;

        // 4. Assemble the row HTML matching your Sneat table columns layout perfectly
        rowsMarkup += `
            <tr>
                <td class="fw-bold text-primary">${sn++}</td>
                <td class="fw-bold text-primary">${cleanFolder}</td>
                <td>${cleanName}</td>
                <td><span class="badge bg-label-secondary">${cleangender}</span></td>
                <td>${cleanAge} yrs old</td>
                <td class="text-danger fw-semibold">${cleanBp}</td>
                <td>${cleanTemp} °C</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-attend-patient" data-payload="${base64Payload}">
                        <i class="bx bx-plus-medical me-1"></i> Attend
                    </button>
                </td>
            </tr>
        `;
    });

    // 5. Inject the full raw HTML string into the tbody container element
    $tbody.append(rowsMarkup);
}


$(document).on('click', '#btn_refresh_consulting_queue', function(e) {
    e.preventDefault();
    const $btn = $(this);
    
    // Smooth visual state processing animation loop transition
    $btn.attr('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-0.5"></i> Syncing...');

    $.ajax({
        url: server_url, // Routes directly to your unified central master backend script variable
        type: 'POST',    // Switched over to POST method execution
        data: {
            action_refresh_consulting_queue: 'true' // Explicit controller flag for server-side routing
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // POPULATE DISPATCH RECORDS LIVE WITHOUT RE-LOADING CORES
                if (typeof updateQueueUI === 'function') {
                    updateQueueUI(response.data);
                } else {
                    console.error("Queue Builder Error: updateQueueUI population method is completely missing.");
                }
            } else {
                console.error("Queue Sync Rejected: " + (response.message || 'Unknown server fault.'));
            }
            
            // Restore action button indicators back to operational default states
            $btn.attr('disabled', false).html('<i class="bx bx-refresh me-0.5"></i> Refresh Queue');
        },
        error: function(xhr, status, error) {
            $btn.attr('disabled', false).html('<i class="bx bx-refresh me-0.5"></i> Refresh Queue');
            console.group("CONSULTING ROOM TERMINAL PIPELINE FAULT");
            console.error("Connection Status State:", status);
            console.error("Thrown Exception Error Message:", error);
            console.error("Raw Backend Server Response Text:", xhr.responseText);
            console.groupEnd();
        }
    });
});


 </script>