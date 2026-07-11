<?php require_once 'includes/header.php';?>  
<div class="container-fluid p-3">
    <div class="row g-3">
        
        <!-- LEFT PANEL: UNPAID PATIENT FOLDERS QUEUE LAYOUT GRID (Width 4) -->
        <div class="col-md-4">
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-receipt text-primary me-1"></i> Billing Ledger Accounts</h6>
                    <span class="badge bg-label-primary text-primary font-monospace" id="bill_queue_badge">0 Accounts Due</span>
                </div>
                <div class="card-body p-2" id="cashier_queue_cards_container">
                    <!-- Javascript populates clickable pending billing cards here -->
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: INTERACTIVE ACCOUNT TRANSACTION DESK SUITE (Width 8) -->
        <div class="col-md-8">
            <div class="card shadow-none border" id="billing_workspace_card">
                
                <div class="card-body text-center py-5 text-muted" id="billing_empty_state">
                    <i class="bx bx-calculator text-secondary d-block fs-1 mb-1"></i>
                    <h5 class="fw-bold text-dark">Cashier Payment Desk</h5>
                    <p class="small">Select any outstanding billing account folder from the left list to review detailed pricing items breakdown charts, check insurance metrics exemptions, and complete payment settlement checkouts.</p>
                </div>

                <!-- LIVE INTERACTIVE SNEAT CASHIER INVOICING WORKSPACE FORM -->
                <form id="billingSettleForm" method="POST" class="d-none flex-column p-3">
                    <input type="hidden" name="action_process_payment" value="true" />
                    
                    <div class="border-bottom pb-2 mb-2 bg-light-subtle rounded p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold text-success mb-0" id="bill_p_name">--</h5>
                                <small class="text-muted font-monospace" id="bill_p_folder">--</small>
                            </div>
                            <span class="badge bg-primary text-white" id="bill_p_insurance">--</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                        <h6 class="fw-bold small text-secondary text-uppercase mb-0 font-monospace tracking-wider">Unpaid Itemized Ledger Summary</h6>
                        <!-- SELECT ALL BOX FOR EXTREME COLLECTION SPEED -->
                        <div class="form-check">
                            <input class="form-check-input cursor-pointer" type="checkbox" id="chk_select_all_bills" checked />
                            <label class="form-check-label small text-muted fw-bold cursor-pointer" for="chk_select_all_bills">Select All Invoices</label>
                        </div>
                    </div>

                    <!-- CONTAINER WHERE ITEMIZED BILL LINES ROWS LOAD UP -->
                    <div id="billing_itemized_rows_hook" class="mb-3"></div>

                    <!-- FINANCIAL SETTLEMENT TRANSACTION CONTROL BOARD BLOCK -->
                    <div class="border p-2.5 rounded bg-white mt-1">
                        <div class="d-flex justify-content-between align-items-center mb-2.5 px-1">
                            <div class="small fw-semibold text-muted">Total Cash Collection Amount Selected:</div>
                            <h3 class="mb-0 fw-bold text-success font-monospace" id="txt_total_collection_sum">GHS 0.00</h3>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2.5 fw-bold shadow-sm" id="btn_process_cashier_payment">
                            <i class="bx bx-dollar-circle fs-5 me-1"></i> Confirm Payment & Issue Clearance Receipt
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>
 

<?php require_once 'includes/footer.php';?>
<script>
var globalCashierLedgerDataset = [];

function syncCashierBillingQueue() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { cashier_billing_fetch: true },
        dataType: 'json',
        success: function(res) {
            console.log('res: ', res);
            
            if(res.status === 'success') {
                globalCashierLedgerDataset = res.data;
                renderCashierQueueUI();
            }
        }
    });
}

function renderCashierQueueUI() {
    var $box = $('#cashier_queue_cards_container').empty();
    $('#bill_queue_badge').text(globalCashierLedgerDataset.length + ' Account(s)');

    if(globalCashierLedgerDataset.length === 0) {
        $box.html('<div class="text-center text-muted py-5 small"><i class="bx bx-smile d-block fs-3 mb-1 text-success"></i> All accounts balanced cleanly! No outstanding unpaid invoices found.</div>');
        $('#billing_empty_state').removeClass('d-none');
        $('#billingSettleForm').addClass('d-none');
        return;
    }

    globalCashierLedgerDataset.forEach(function(pt, idx) {
        var cardMarkup = `
            <div class="card border mb-1.5 shadow-none cursor-pointer btn-select-billing-patient transition-all" data-index="${idx}" style="border-radius:6px;">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width:70%; font-size:0.85rem;">${$('<div>').text(pt.patient_name).html()}</h6>
                        <small class="font-monospace text-muted" style="font-size:0.7rem;">${pt.folder_number}</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2" style="font-size:0.78rem;">
                        <span class="text-muted"><i class="bx bx-label text-secondary"></i> ${pt.total_unpaid_items} Item(s) Due</span>
                        <span class="font-monospace fw-bold text-danger">GHS ${pt.total_outstanding_debt.toFixed(2)}</span>
                    </div>
                </div>
            </div>`;
        $box.append(cardMarkup);
    });
}

// INTERACTIVE SELECTION EVENT LISTENER: MOUNT INVOICES INTO SELECTION PANEL
$(document).on('click', '.btn-select-billing-patient', function(e) {
    e.preventDefault();
    $('.btn-select-billing-patient').removeClass('border-success bg-label-success');
    $(this).addClass('border-success bg-label-success');

    var index = $(this).data('index');
    var pt = globalCashierLedgerDataset[index];
    if(!pt) return;

    $('#billing_empty_state').addClass('d-none');
    $('#billingSettleForm').removeClass('d-none').css('display','flex');

    // Bind tracking headings text
    $('#bill_p_name').text(pt.patient_name);
    $('#bill_p_folder').text(pt.folder_number);
    $('#bill_p_insurance').text(pt.insurance_type);

    $('#chk_select_all_bills').prop('checked', true);

    var $rowsHook = $('#billing_itemized_rows_hook').empty();

    pt.itemized_bill_list.forEach(function(invoice) {
        var itemAmount = parseFloat(invoice.amount);
        
        // Define clean visual sub-badge accents based on category groups
        var labelBadgeClass = "bg-label-primary text-primary";
        if(invoice.item_type === 'Lab Fee') labelBadgeClass = "bg-label-info text-info";
        if(invoice.item_type === 'Drugs') labelBadgeClass = "bg-label-warning text-warning";

        var blockRowMarkup = `
            <div class="p-2 border rounded mb-1 bg-white shadow-xs d-flex align-items-center justify-content-between font-monospace" style="font-size: 0.78rem;">
                <div class="d-flex align-items-center gap-2" style="max-width: 80%;">
                    <div class="form-check mb-0">
                        <input class="form-check-input cursor-pointer bill-item-checkbox" type="checkbox" name="bill_ids[]" value="${invoice.bill_id}" data-amount="${itemAmount}" checked />
                    </div>
                    <div class="text-truncate">
                        <span class="badge ${labelBadgeClass} py-0 px-1 me-1 text-xs text-uppercase font-monospace">${invoice.item_type}</span>
                        <span class="text-dark fw-semibold text-truncate">${$('<div>').text(invoice.item_description).html()}</span>
                        <small class="text-muted d-block" style="font-size:0.68rem;"><i class="bx bx-time"></i> Logs: ${invoice.created_at}</small>
                    </div>
                </div>
                <div class="fw-bold text-dark text-nowrap">GHS ${itemAmount.toFixed(2)}</div>
            </div>`;
        $rowsHook.append(blockRowMarkup);
    });

    calculateSelectedInvoiceTotals();
});

// ITEM CHECKBOX INTERACTION LISTENER: LIVE TOTAL CALCULATOR ENGINE
function calculateSelectedInvoiceTotals() {
    var accumulatedCollectionSum = 0.00;
    $('.bill-item-checkbox:checked').each(function() {
        accumulatedCollectionSum += parseFloat($(this).attr('data-amount') || 0);
    });
    
    $('#txt_total_collection_sum').text('GHS ' + accumulatedCollectionSum.toFixed(2));
    $('#btn_process_cashier_payment').attr('disabled', accumulatedCollectionSum <= 0);
}

$(document).on('change', '.bill-item-checkbox', function() {
    calculateSelectedInvoiceTotals();
    // Sync Select All checkbox indicator state logic matching
    var allCheckedCount = $('.bill-item-checkbox:checked').length;
    var maxTotalCount = $('.bill-item-checkbox').length;
    $('#chk_select_all_bills').prop('checked', allCheckedCount === maxTotalCount);
});

// TOGGLE MASTER CHECKBOX: SELECT / UN-SELECT ENTIRE LIST ROWS
$(document).on('change', '#chk_select_all_bills', function() {
    var isChecked = $(this).is(':checked');
    $('.bill-item-checkbox').prop('checked', isChecked);
    calculateSelectedInvoiceTotals();
});

// SUBMIT ENTRANCE GATEWAY: COMPLETE COLLECTION TRANSACTION SETTLEMENT
$('#billingSettleForm').on('submit', function(e) {
    e.preventDefault();
    var form = this;

    Swal.fire({
        title: 'Confirm Cash Payment Collection?',
        text: 'This actions logs selected item accounts as settled, logs transactional receipts ledger records and grants medical dispatch authorization files clearance.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28c76f',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Confirm Settlement Receipt'
    }).then((res) => {
        if (!res.isConfirmed) return;

        Swal.fire({ title: 'Processing clearance invoices entries...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if(response.status === 'success') {
                    Swal.fire({ title: 'Payment Settled', text: response.message, icon: 'success', timer: 1500, showConfirmButton: false });
                    $('#billingSettleForm').addClass('d-none');
                    $('#billing_empty_state').removeClass('d-none');

                    // Synchronize and re-populate fresh ledger rows list instantly
                    globalCashierLedgerDataset = response.data;
                    renderCashierQueueUI();
                } else {
                    Swal.fire('Action Rejected', response.message, 'error');
                }
            }
        });
    });
});

$(document).ready(function() {
    syncCashierBillingQueue();
});
</script>
