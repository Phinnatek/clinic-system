<?php require_once 'includes/header.php';?>   

<div class="container-fluid p-3">
    <div class="row g-3">
        
        <!-- LEFT COLUMN: ACTIVE WAITING LIST QUEUE (Width 4) -->
        <div class="col-md-4">
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2.5 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-injection text-danger me-1"></i> Injection Wait Room</h6>
                    <span class="badge bg-label-danger text-danger font-monospace" id="inj_queue_badge">0 Waiting</span>
                </div>
                <div class="card-body p-2" id="nurse_queue_cards_container">
                    <!-- Javascript appends clickable patient queue item rows here -->
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: EXPANDED INJECTION DETAILS WORKBENCH (Width 8) -->
        <div class="col-md-8">
            <div class="card shadow-none border" id="inj_workspace_card">
                
                <div class="card-body text-center py-5 text-muted" id="inj_empty_state">
                    <i class="bx bx-shield-plus text-secondary d-block fs-1 mb-1"></i>
                    <h5 class="fw-bold text-dark">Nursing Care Dashboard</h5>
                    <p class="small">Select an active waiting patient folder from the left list panel to load prescribed injectable fluid ampoules, administration routes, and dosage targets.</p>
                </div>

                <!-- LIVE INTERACTIVE WORKSPACE SUBMIT FORM -->
                <div id="inj_active_form_panel" class="d-none flex-column p-3">
                    <div class="border-bottom pb-2 mb-3 bg-light-subtle rounded p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold text-danger mb-0" id="inj_p_name">--</h5>
                                <small class="text-muted font-monospace" id="inj_p_folder">--</small>
                            </div>
                            <span class="badge bg-secondary" id="inj_p_insurance">--</span>
                        </div>
                        <div class="small text-dark mt-2"><b>Prescribing Clinician:</b> <span id="inj_p_doctor">--</span></div>
                    </div>

                    <h6 class="fw-bold small text-secondary text-uppercase mb-2 font-monospace tracking-wider">Assigned Injectables Medication Checklist</h6>
                    <div id="inj_medications_list_hook">
                        <!-- Clickable actions checkboxes list items output here -->
                    </div>
                </div>

            </div>
        </div>

    </div>
</div> 

<?php require_once 'includes/footer.php';?>
<script>
var globalNurseInjectionsDataset = [];

function syncNurseInjectionQueueList() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { nurse_injection_fetch: true },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                globalNurseInjectionsDataset = res.data;
                renderNurseQueueUI();
            }
        }
    });
}

function renderNurseQueueUI() {
    var $box = $('#nurse_queue_cards_container').empty();
    $('#inj_queue_badge').text(globalNurseInjectionsDataset.length + ' Patient(s)');

    if(globalNurseInjectionsDataset.length === 0) {
        $box.html('<div class="text-center text-muted py-5 small"><i class="bx bx-check-double d-block fs-3 mb-1 text-success"></i> All injection clear tracks are up to date! Wait room is currently empty.</div>');
        $('#inj_empty_state').removeClass('d-none');
        $('#inj_active_form_panel').addClass('d-none');
        return;
    }

    globalNurseInjectionsDataset.forEach(function(pt, idx) {
        var markup = `
            <div class="card border mb-1.5 shadow-none cursor-pointer btn-select-injection-patient transition-all" data-index="${idx}" style="border-radius:6px;">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width:75%; font-size:0.85rem;">${$('<div>').text(pt.patient_name).html()}</h6>
                        <small class="font-monospace text-muted" style="font-size:0.7rem;">${pt.folder_number}</small>
                    </div>
                    <div class="small text-danger font-monospace" style="font-size:0.75rem;"><i class="bx bx-injection text-danger"></i> ${pt.injections_list.length} Ampoule(s) Waiting</div>
                </div>
            </div>`;
        $box.append(markup);
    });
}

// INTERACTIVE QUEUE CLICK HANDLER: REVEALS ORDERS CHECKSHEET WORKSPACE
$(document).on('click', '.btn-select-injection-patient', function(e) {
    e.preventDefault();
    $('.btn-select-injection-patient').removeClass('border-danger bg-label-danger');
    $(this).addClass('border-danger bg-label-danger');

    var index = $(this).data('index');
    var pt = globalNurseInjectionsDataset[index];
    if(!pt) return;

    $('#inj_empty_state').addClass('d-none');
    $('#inj_active_form_panel').removeClass('d-none').css('display','flex');

    // Bind metrics text fields properties
    $('#inj_p_name').text(pt.patient_name);
    $('#inj_p_folder').text(pt.folder_number);
    $('#inj_p_doctor').text(pt.prescribing_doctor);
    $('#inj_p_insurance').text(pt.insurance_type);

    var $listHook = $('#inj_medications_list_hook').empty();
    
    pt.injections_list.forEach(function(inj) {
        // Clean out the [INJECTION] tag prefix before printing instructions to screen
        var cleanInstructionLabel = inj.dosage.replace('[INJECTION] ', '');

        var blockRowMarkup = `
            <div class="p-2 border rounded mb-1.5 bg-white shadow-xs d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                <div style="max-width: 75%;">
                    <h6 class="fw-bold text-dark mb-0.5" style="font-size:0.85rem;"><i class="bx bx-radio-circle-marked text-danger"></i> ${$('<div>').text(inj.drug_name).html()}</h6>
                    <small class="text-danger font-monospace ps-2.5 d-block"><b>Administration Layout:</b> ${$('<div>').text(cleanInstructionLabel).html()}</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-danger px-2.5 btn-administer-single-inj fw-bold" data-id="${inj.prescription_id}">
                        <i class="bx bx-check-circle me-1"></i> Inject Complete
                    </button>
                </div>
            </div>`;
        $listHook.append(blockRowMarkup);
    });
});

// COMMITTING AN INDIVIDUAL ACTION HANDLER DOWN TO TRANSACTIONS LOGS PIPELINE
$(document).on('click', '.btn-administer-single-inj', function(e) {
    e.preventDefault();
    var prescriptionId = $(this).attr('data-id') || $(this).data('id');
    if(!prescriptionId) return;

    Swal.fire({
        title: 'Confirm Drug Administration?',
        text: 'Confirming that this injectable fluid has been prepared and administered successfully to the patient.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff3e1d',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Sign Chart'
    }).then((res) => {
        if (!res.isConfirmed) return;

        Swal.fire({ title: 'Logging clinical chart updates...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: { action_administer_injection: true, prescription_id: prescriptionId },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if(response.status === 'success') {
                    Swal.fire({ title: 'Chart Signed', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                    
                    // Reset right panel state view cleanly
                    $('#inj_active_form_panel').addClass('d-none');
                    $('#inj_empty_state').removeClass('d-none');

                    // Synchronize dataset arrays logs values
                    globalNurseInjectionsDataset = response.data;
                    renderNurseQueueUI();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });
});

$(document).ready(function() {
    syncNurseInjectionQueueList();
});
</script>
