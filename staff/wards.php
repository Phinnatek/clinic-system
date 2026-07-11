<?php require_once 'includes/header.php';?>  
<div class="container-fluid p-3">
    <div class="row g-3">
        
        <!-- LEFT PANELS GRID ROW: INPATIENT FLOW QUEUE (Width 4) -->
        <div class="col-md-4">
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-bed text-danger me-1"></i> Active Ward Logs</h6>
                    <span class="badge bg-label-danger text-danger font-monospace" id="ward_queue_badge">0 Cases</span>
                </div>
                <div class="card-body p-2" id="ward_queue_container">
                    <!-- Javascript populates active admission profile cards here -->
                </div>
            </div>
        </div>

        <!-- RIGHT PANELS GRID ROW: ALLOCATION WORKBENCH SUITE (Width 8) -->
        <div class="col-md-8">
            <div class="card shadow-none border" id="ward_workspace_card">
                
                <div class="card-body text-center py-5 text-muted" id="ward_empty_state">
                    <i class="bx bx-hotel text-secondary d-block fs-1 mb-1"></i>
                    <h5 class="fw-bold text-dark">Ward Management Terminal</h5>
                    <p class="small">Select a requested inpatient case file folder from the left queue to assign bed locations, write nurse monitoring notes, or execute clean discharge file clearances.</p>
                </div>

                <!-- LIVE INTERACTIVE WORKING MANAGEMENT CONTEXT PANEL FORM -->
                <form id="wardActionForm" method="POST" class="d-none flex-column p-3">
                    <input type="hidden" name="admission_id" id="w_admission_id" />
                    
                    <div class="border-bottom pb-2 mb-3 bg-light-subtle rounded p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold text-danger mb-0" id="w_pt_name">--</h5>
                                <small class="text-muted font-monospace d-block" id="w_pt_folder">--</small>
                            </div>
                            <span class="badge bg-dark font-monospace text-xs" id="w_pt_demographics">--</span>
                        </div>
                        <div class="small text-dark mt-2.5"><b>Doctor Diagnosed Outcome:</b> <span id="w_pt_diagnosis" class="text-primary fw-semibold">--</span></div>
                        <div class="small text-muted mt-0.5"><i class="bx bx-time"></i> <b>Enqueued Logs Date:</b> <span id="w_pt_date">--</span></div>
                    </div>

                    <!-- STEP A PANEL: DISPLAYED ONLY WHEN PATIENT IS AWAITING BED ALLOCATION -->
                    <div id="panel_allocate_bed_suite" class="d-none">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small mb-1"><i class="bx bx-list-check"></i> Assign Available Bed Space Location</label>
                            <select name="bed_id" id="w_select_bed_id" class="form-select form-select-sm" required>
                                <option value="" selected disabled>-- Search Empty Bed Spaces --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small mb-1"><i class="bx bx-note"></i> Nurse Initial Triage & Monitoring Notes</label>
                            <textarea name="nursing_notes" id="w_nursing_notes" class="form-control text-xs" rows="3" placeholder="Type baseline nursing assessment notes, room allocation rules..."></textarea>
                        </div>
                        <button type="button" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm" id="btn_submit_bed_allocation">
                            <i class="bx bx-hotel me-1 fs-5"></i> Confirm Ward Allocation & Lock Bed Space
                        </button>
                    </div>

                    <!-- STEP B PANEL: DISPLAYED ONLY WHEN PATIENT IS ACTIVELY OCCUPYING A BED -->
                    <div id="panel_active_admission_suite" class="d-none">
                        <div class="alert alert-success d-flex align-items-center mb-3 border-0 py-2 px-3" style="border-radius:6px;">
                            <i class="bx bx-check-circle fs-4 me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-0 fw-bold text-success">Actively Admitted Inpatient Care Status</h6>
                                <small>Assigned Location: <b id="lbl_allocated_ward_location" class="font-monospace text-uppercase">--</b></small>
                            </div>
                        </div>
                        <div class="mb-4 border rounded p-2 bg-white shadow-xs">
                            <label class="form-label fw-bold text-muted small d-block border-bottom pb-1 mb-1 font-monospace text-uppercase">Active Chart Nursing Notes:</label>
                            <p class="text-dark small mb-0 font-monospace ps-1" id="lbl_active_nursing_notes" style="white-space: pre-line;">--</p>
                        </div>
                        <button type="button" class="btn btn-danger w-100 py-2.5 fw-bold shadow-sm" id="btn_trigger_patient_discharge">
                            <i class="bx bx-log-out-circle me-1 fs-5"></i> Clear Case: Authorize Patient Medical Discharge
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>
<?php require_once 'includes/footer.php';?>
 
<script>
var globalWardQueueDataset = [];
var globalBedsInventoryList = [];

function syncWardAdmissionsQueue() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { ward_admission_fetch: true },
        dataType: 'json',
        success: function(res) {
            console.log('res: ', res);
            
            if(res.status === 'success') {
                globalWardQueueDataset = res.queue;
                globalBedsInventoryList = res.beds;
                renderWardQueueUI();
                populateBedsSelectDropdown();
            }
        }
    });
}

function renderWardQueueUI() {
    var $box = $('#ward_queue_container').empty();
    $('#ward_queue_badge').text(globalWardQueueDataset.length + ' Case(s)');

    if(globalWardQueueDataset.length === 0) {
        $box.html('<div class="text-center text-muted py-5 small"><i class="bx bx-check-shield d-block fs-3 mb-1 text-success"></i> No active inpatient files waiting. All beds balanced cleanly.</div>');
        $('#ward_empty_state').removeClass('d-none');
        $('#wardActionForm').addClass('d-none');
        return;
    }

    globalWardQueueDataset.forEach(function(pt, idx) {
        var isAwaiting = (pt.admission_status === 'Awaiting Bed');
        var badgeAccent = isAwaiting ? 'bg-label-warning text-warning' : 'bg-label-success text-success';
        var subtitleLabel = isAwaiting ? '<i class="bx bx-time"></i> Awaiting Bed space' : `<i class="bx bx-hotel"></i> ${pt.ward_name} • ${pt.bed_number}`;

        var markup = `
            <div class="card border mb-1.5 shadow-none cursor-pointer btn-select-ward-patient transition-all" data-index="${idx}" style="border-radius:6px;">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width:70%; font-size:0.82rem;">${$('<div>').text(pt.patient_name).html()}</h6>
                        <small class="font-monospace text-muted" style="font-size:0.68rem;">${pt.folder_number}</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small" style="font-size:0.75rem;">
                        <span class="${isAwaiting ? 'text-warning' : 'text-success'} fw-semibold">${subtitleLabel}</span>
                        <span class="badge ${badgeAccent} font-monospace text-xs py-0.5 px-1">${pt.admission_status.toUpperCase()}</span>
                    </div>
                </div>
            </div>`;
        $box.append(markup);
    });
}

function populateBedsSelectDropdown() {
    var $select = $('#w_select_bed_id');
    var placeholder = $select.find('option:first');
    $select.empty().append(placeholder);

    if (globalBedsInventoryList.length > 0) {
        globalBedsInventoryList.forEach(function(bed) {
            $select.append(`<option value="${bed.bed_id}">${bed.ward_name} [${bed.ward_type}] — Space: ${bed.bed_number}</option>`);
        });
    }
}

// INTERACTIVE SELECTION EVENT HANDLER: SWITCH WORKSPACE ACCORDING TO PATIENT ACTIVE STATE
$(document).on('click', '.btn-select-ward-patient', function(e) {
    e.preventDefault();
    $('.btn-select-ward-patient').removeClass('border-danger bg-label-danger');
    $(this).addClass('border-danger bg-label-danger');

    var index = $(this).data('index');
    var pt = globalWardQueueDataset[index];
    if(!pt) return;

    $('#ward_empty_state').addClass('d-none');
    $('#wardActionForm').removeClass('d-none').css('display','flex');

    // Populate core patient fields
    $('#w_admission_id').val(pt.admission_id);
    $('#w_pt_name').text(pt.patient_name);
    $('#w_pt_folder').text(pt.folder_number + ' • ' + pt.insurance_type + ' Cover Profile');
    $('#w_pt_demographics').text(`${pt.gender} (${pt.calculated_age} YRS OLD)`);
    $('#w_pt_diagnosis').text(pt.diagnosis || 'N/A');
    $('#w_pt_date').text(pt.date_logged);

    // DYNAMIC SWITCH LOGIC: Show different form steps based on status
    if (pt.admission_status === 'Awaiting Bed') {
        $('#panel_active_admission_suite').addClass('d-none');
        $('#panel_allocate_bed_suite').removeClass('d-none');
        $('#w_select_bed_id').val('');
        $('#w_nursing_notes').val('');
    } else {
        $('#panel_allocate_bed_suite').addClass('d-none');
        $('#panel_active_admission_suite').removeClass('d-none');
        $('#lbl_allocated_ward_location').text(`${pt.ward_name} — Space ID: ${pt.bed_number}`);
        $('#lbl_active_nursing_notes').text(pt.nursing_notes || 'No baseline assessment notes recorded.');
    }
});

// SUBMIT EVENT A: SAVE ALLOCATION COMMIT
$(document).on('click', '#btn_submit_bed_allocation', function(e) {
    e.preventDefault();
    var bedId = $('#w_select_bed_id').val();
    if (!bedId) return $('#w_select_bed_id').addClass('is-invalid');
    $('#w_select_bed_id').removeClass('is-invalid');

    Swal.fire({
        title: 'Confirm Bed Allocation?',
        text: 'This secures the physical bed space and transitions the patient folder into actively admitted inpatient care charts.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#696cff',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Admit Patient'
    }).then((res) => {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Admitting patient to ward...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        var postData = {
            action_allocate_bed: true,
            admission_id: $('#w_admission_id').val(),
            bed_id: bedId,
            nursing_notes: $('#w_nursing_notes').val()
        };

        $.ajax({
            url: server_url,
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if(response.status === 'success') {
                    Swal.fire('Admitted!', response.message, 'success');
                    $('#wardActionForm').addClass('d-none');
                    $('#ward_empty_state').removeClass('d-none');
                    globalWardQueueDataset = response.queue;
                    globalBedsInventoryList = response.beds;
                    renderWardQueueUI();
                    populateBedsSelectDropdown();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });
});

// SUBMIT EVENT B: COMPLETE TREATMENT DISCHARGE
$(document).on('click', '#btn_trigger_patient_discharge', function(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Authorize Clinical Discharge?',
        text: 'This signs off inpatient charts, frees up the physical bed space, and completely closes out this ward admission log.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff3e1d',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Discharge Patient'
    }).then((res) => {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Processing clinical discharge...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: { action_discharge_patient: true, admission_id: $('#w_admission_id').val() },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if(response.status === 'success') {
                    Swal.fire('Discharged!', response.message, 'success');
                    $('#wardActionForm').addClass('d-none');
                    $('#ward_empty_state').removeClass('d-none');
                    globalWardQueueDataset = response.queue;
                    globalBedsInventoryList = response.beds;
                    renderWardQueueUI();
                    populateBedsSelectDropdown();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });
});

$(document).ready(function() {
    syncWardAdmissionsQueue();
});
</script>
