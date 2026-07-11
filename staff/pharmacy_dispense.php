<?php require_once 'includes/header.php';?> 
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <div class="content-wrapper p-4">
            
            <div class="row g-4">
                <!-- LEFT COLUMN: ACTIVE PRESCRIPTION DISPATCH QUEUE LINES -->
                <div class="col-md-5">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0"><i class="bx bx-time-five text-warning me-1"></i> Pharmacy Waiting Queue</h5>
                            <span class="badge bg-label-warning text-warning" id="queue_count_badge">0 Patients Waiting</span>
                        </div>
                        <div class="card-body p-2" id="pharmacy_queue_container" style="max-height: 560px; overflow-y: auto;">
                            <!-- Active patient cards inject here via JavaScript loops -->
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: INTERACTIVE DISPENSATION WORKSPACE PANEL -->
                <div class="col-md-7">
                    <div class="card h-100 shadow-sm border-0" id="dispensation_workspace_card">
                        <div class="card-body text-center py-5 text-muted" id="empty_workspace_state">
                            <i class="bx bx-capsule text-secondary d-block fs-1 mb-2"></i>
                            <h5 class="fw-bold">No Patient File Opened</h5>
                            <p class="small">Select a waiting folder from the left queue panel to load active medication orders and evaluate inventory availability chart rates.</p>
                        </div>
                        
                        <!-- HIDDEN DISPENSATION WORK AREA CONTEXT (Reveals dynamically) -->
                        <form id="dispensationActionForm" method="POST" class="d-none h-100 flex-column justify-content-between">
                            <input type="hidden" name="action_dispense_meds" value="true" />
                            <input type="hidden" name="p_consultation_id" id="w_consultation_id" />
                            <input type="hidden" name="p_patient_id" id="w_patient_id" />

                            <div class="p-3 border-bottom bg-light-subtle rounded-top">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <h5 class="fw-bold text-primary mb-0" id="w_patient_name">--</h5>
                                        <small class="text-muted font-monospace" id="w_folder_number">--</small>
                                    </div>
                                    <span class="badge border text-uppercase" id="w_insurance_badge">--</span>
                                </div>
                                <div class="small text-dark mt-2"><b> Prescribing Doctor:</b> <span id="w_doctor_name">--</span></div>
                                <div class="small text-dark"><b> Primary Diagnosis Note:</b> <span id="w_diagnosis" class="text-truncate">--</span></div>
                            </div>

                            <div class="p-3 flex-grow-1" style="max-height: 380px; overflow-y: auto;">
                                <h6 class="fw-bold small text-secondary text-uppercase mb-2 font-monospace tracking-wider">Prescription Breakdown Items Checklist</h6>
                                <div id="workspace_medications_list_hook"></div>
                            </div>

                            <div class="card-footer bg-white border-top p-3 text-end rounded-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold text-secondary">Total Bill Invoice Estimate:</h6>
                                    <h4 class="mb-0 fw-bold text-success font-monospace" id="w_total_cost_label">GHS 0.00</h4>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 py-2.5 fw-bold shadow-sm" id="btn_confirm_dispense_checkout">
                                    <i class="bx bx-check-shield fs-5 me-1"></i> Fulfill Orders & Log Inventory Deductions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div> 
<?php require_once 'includes/footer.php';?>
<script>
// Global memory register variable to hold entire array data lists offline
var centralPharmacyQueuePayload = [];

function loadFreshPharmacyQueueData() {
    $.ajax({
        url: server_url, // Routes directly to your unified master backend controller variable
        type: 'POST',    // Switched over to POST method execution
        data: {
            pharmacy_dispense: true // Appended core execution controller flag
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                centralPharmacyQueuePayload = response.data;
                renderPharmacyQueueUI();
            } else {
                console.error("Queue fetch rejected: " + response.message);
            }
        },
        error: function(xhr, status, error) {

            console.error("xhr " + xhr.responseText);
            console.error("Pharmacy transmission pipeline crashed: " + error);
        }
    });
}



function renderPharmacyQueueUI() {
    var $container = $('#pharmacy_queue_container').empty();
    $('#queue_count_badge').text(centralPharmacyQueuePayload.length + ' Patients Waiting');

    if(centralPharmacyQueuePayload.length === 0) {
        $container.html('<div class="text-center text-muted py-5 small"><i class="bx bx-check-circle d-block fs-2 mb-1 text-success"></i> No pending prescriptions found. All folders cleared cleanly.</div>');
        $('#empty_workspace_state').removeClass('d-none');
        $('#dispensationActionForm').addClass('d-none');
        return;
    }

    centralPharmacyQueuePayload.forEach(function(patient, index) {
        var cardMarkup = `
            <div class="card border mb-2 shadow-none cursor-pointer btn-select-pharmacy-patient transition-all" data-index="${index}" style="border-radius:8px;">
                <div class="card-body p-2.5">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width:70%;">${$('<div>').text(patient.patient_name).html()}</h6>
                        <small class="font-monospace text-muted">${patient.folder_number}</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small">
                        <span class="text-secondary"><i class="bx bx-capsule text-warning"></i> ${patient.medications_list.length} Meds Waiting</span>
                        <span class="badge bg-label-secondary font-monospace text-xs">${patient.insurance_type}</span>
                    </div>
                </div>
            </div>`;
        $container.append(cardMarkup);
    });
}
$(document).on('click', '.btn-select-pharmacy-patient', function(e) {
    e.preventDefault();
    $('.btn-select-pharmacy-patient').removeClass('border-primary bg-label-primary');
    $(this).addClass('border-primary bg-label-primary');

    var index = $(this).data('index');
    var patient = centralPharmacyQueuePayload[index];
    if(!patient) return;

    $('#empty_workspace_state').addClass('d-none');
    $('#dispensationActionForm').removeClass('d-none').css('display', 'flex');

    // Populate core text labels
    $('#w_consultation_id').val(patient.consultation_id);
    $('#w_patient_id').val(patient.patient_id);
    $('#w_patient_name').text(patient.patient_name);
    $('#w_folder_number').text(patient.folder_number);
    $('#w_doctor_name').text(patient.prescribing_doctor);
    $('#w_diagnosis').text(patient.diagnosis || 'N/A');
    
    var insBadge = $('#w_insurance_badge').text(patient.insurance_type).removeClass('bg-success bg-primary');
    insBadge.addClass(patient.insurance_type === 'None' ? 'bg-primary text-white' : 'bg-success text-white');

    // Remove any stale alert messages first
    $('#pharmacy_action_alerts').remove();
    var alertBoxMarkup = '<div id="pharmacy_action_alerts" class="mt-1.5">'; // Compressed margin top

    // --- CHECK A: VERIFY UNPAID INVOICES AND OUTSTANDING BILLS ---
    var isBlockedByPayment = false;
    if (patient.insurance_type === 'None' && patient.unpaid_bills_count > 0) {
        isBlockedByPayment = true;
        alertBoxMarkup += `
            <div class="alert alert-danger d-flex align-items-center mb-1 py-1.5 px-2 border-0" style="border-radius:6px;">
                <i class="bx bx-error-circle fs-5 me-2"></i>
                <div>
                    <h6 class="alert-heading mb-0 fw-bold text-danger" style="font-size:0.85rem;">Payment Outstanding! Redirect to Cashier</h6>
                    <small style="font-size:0.75rem;">Patient has <b>${patient.unpaid_bills_count} unpaid bills</b>. Total due: <b class="font-monospace">GHS ${patient.total_unpaid_amount.toFixed(2)}</b>.</small>
                </div>
            </div>`;
    }

    // --- CHECK B: VERIFY IF THE NURSE HAS ADMINISTERED THE INJECTION ---
    if (patient.pending_injections_count > 0) {
        alertBoxMarkup += `
            <div class="alert alert-warning d-flex align-items-center mb-1 py-1.5 px-2 border-0" style="border-radius:6px;">
                <i class="bx bx-injection fs-5 me-2"></i>
                <div>
                    <h6 class="alert-heading mb-0 fw-bold text-warning" style="font-size:0.85rem;">Pending Clinical Injection</h6>
                    <small style="font-size:0.75rem;">Patient has <b>${patient.pending_injections_count} pending injection(s)</b>. Route to Nursing Room first.</small>
                </div>
            </div>`;
    }
    
    alertBoxMarkup += '</div>';
    // Compressed padding selection target
    $('#dispensationActionForm .p-2:first, #dispensationActionForm .p-3:first').first().append(alertBoxMarkup);

    // Iterate items list checklist
    var $listHook = $('#workspace_medications_list_hook').empty();
    var continuousBillInvoiceAccumulator = 0.00;

    patient.medications_list.forEach(function(med) {
        var rate = parseFloat(med.selling_price);
        continuousBillInvoiceAccumulator += rate;

        var currentStockLevel = parseInt(med.stock_qty);
        var isStockDepleted = currentStockLevel <= 0;

        var stockStatusBadge = isStockDepleted 
            ? `<span class="badge bg-danger text-white py-0.5 px-1.5 text-xs"><i class="bx bx-error-circle"></i> OUT OF STOCK (${currentStockLevel})</span>`
            : `<span class="badge bg-light text-success border py-0.5 px-1.5 text-xs"><i class="bx bx-package"></i> Stock OK (${currentStockLevel})</span>`;

        // Compressed padding parameters and flattened structural line spacing height indicators
        var blockMarkup = `
            <div class="p-2 border rounded mb-1.5 bg-white shadow-xs" style="font-size:0.8rem;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pe-1 text-truncate" style="max-width:82%;">
                        <h6 class="fw-bold text-dark mb-0.5 text-truncate" style="font-size:0.85rem;"><i class="bx bx-chevron-right text-warning"></i> ${$('<div>').text(med.drug_name).html()}</h6>
                        <small class="text-primary font-monospace ps-2.5 d-block text-truncate"><b>Direction:</b> ${$('<div>').text(med.dosage).html()}</small>
                    </div>
                    <span class="font-monospace fw-bold text-secondary text-nowrap">GHS ${rate.toFixed(2)}</span>
                </div>
                <div class="text-end mt-1">
                    ${stockStatusBadge}
                    <input type="hidden" name="r_prescription_ids[]" value="${med.prescription_id}" />
                    <input type="hidden" name="r_drug_ids[]" value="${med.drug_id}" />
                </div>
            </div>`;
        $listHook.append(blockMarkup);
    });

    var hasStockDeficiencies = $listHook.find('.bg-danger').length > 0;
    var checkoutButton = $('#btn_confirm_dispense_checkout');
    
    // --- CONTROL PATH: LOCK BUTTON IF EITHER UNPAID BILLS OR OUT OF STOCK OCCURS ---
    if (isBlockedByPayment) {
        checkoutButton.attr('disabled', true).removeClass('btn-warning btn-secondary').addClass('btn-danger').html('<i class="bx bx-lock-alt me-1"></i> Cannot Dispense: Pending Cashier Clearance');
    } else if (hasStockDeficiencies) {
        checkoutButton.attr('disabled', true).removeClass('btn-warning btn-danger').addClass('btn-secondary').html('<i class="bx bx-error me-1"></i> Cannot Dispense: Stock Depleted');
    } else {
        checkoutButton.attr('disabled', false).removeClass('btn-secondary btn-danger').addClass('btn-warning').html('<i class="bx bx-check-shield fs-5 me-1"></i> Fulfill Orders & Log Inventory Deductions');
    }

    // Force updates to global invoice label indicator totals
    $('#w_total_cost_label').text('GHS ' + (patient.insurance_type === 'None' ? continuousBillInvoiceAccumulator.toFixed(2) : '0.00 (Insured)'));
});


// SUBMIT ENTRANCE EVENT INTERCEPTOR: COMMITS RECORD CHANGES TO SYSTEM TRANSACTIONS LOGS
$('#dispensationActionForm').on('submit', function(e) {
    e.preventDefault();
    var form = this;

    Swal.fire({
        title: 'Confirm Dispensation Checkout?',
        text: 'This will log items as issued out cleanly, deduct stock balancing indices, and route details to history dashboards.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff9f43',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Issue Medications'
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({ title: 'Processing ledger adjustments...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url, // Separate dedicated file backend we will write next
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.status === 'success') {
                    Swal.fire({ title: 'Dispensed Successfully', text: response.message, icon: 'success', timer: 1500, showConfirmButton: false });
                    $('#dispensationActionForm').addClass('d-none');
                    $('#empty_workspace_state').removeClass('d-none');
                    
                    // Re-trigger global database fetch to draw fresh queue list rows
                    loadFreshPharmacyQueueData();
                } else {
                    Swal.fire('Operation Declined', response.message, 'error');
                }
            },
            error: function() {
                Swal.close();
                Swal.fire('Connection Error', 'The pharmacy transaction pipeline encountered an error.', 'error');
            }
        });
    });
});

$(document).ready(function() {
    loadFreshPharmacyQueueData();
});
</script>
</body>
</html>
