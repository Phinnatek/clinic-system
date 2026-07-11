<?php require_once 'includes/header.php';?>   

<div class="container-fluid p-3">
    <!-- WAREHOUSE OVERVIEW METRIC CHIPS -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-warning p-1 rounded"><i class="bx bx-git-pull-request text-warning fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted">Pending Pharmacy Vouchers</h6><h4 class="mb-0 fw-bold font-monospace text-warning" id="stat_pending_vouchers">0</h4></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-primary p-1 rounded"><i class="bx bx-archive text-primary fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted">Warehouse Managed Items</h6><h4 class="mb-0 fw-bold font-monospace" id="stat_warehouse_items">0</h4></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- LEFT COLUMN: SUB-DEPOT VOUCHERS QUEUE (Width 4) -->
        <div class="col-md-4">
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2.5 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-time-five text-warning me-1"></i> Incoming Pharmacy Demands</h6>
                    <span class="badge bg-label-warning text-warning font-monospace text-xs" id="ms_req_badge">0 Vouchers</span>
                </div>
                <div class="card-body p-2" id="main_store_queue_cards_container">
                    <!-- Javascript populates active pharmacy request cards here -->
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: ASSET EVALUATION & EXTRUSION DESK (Width 8) -->
        <div class="col-md-8">
            <div class="card shadow-none border" id="main_store_workspace_card">
                
                <div class="card-body text-center py-5 text-muted" id="ms_empty_state">
                    <i class="bx bx-layer text-secondary d-block fs-1 mb-1"></i>
                    <h5 class="fw-bold text-dark">Main Warehouse Issuance Workbench</h5>
                    <p class="small">Select a pending stock requisition card from the left panel to evaluate request lines, check warehouse counts, and approve asset distributions.</p>
                </div>

                <!-- LIVE INTERACTIVE WORKING DISPATCH FORM PANEL -->
                <form id="mainStoreActionForm" method="POST" class="d-none flex-column p-3">
                    <input type="hidden" name="action_approve_requisition" value="true" />
                    <input type="hidden" name="requisition_id" id="w_req_id" />
                    <input type="hidden" name="warehouse_drug_id" id="w_warehouse_drug_id" />
                    <input type="hidden" name="pharmacy_drug_id" id="w_pharmacy_drug_id" />
                    <input type="hidden" name="requested_qty" id="w_requested_qty" />

                    <div class="border-bottom pb-2 mb-2 bg-light-subtle rounded p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-uppercase font-monospace text-muted d-block small mb-0.5">Origin Sub-Depot Unit: Pharmacy Shelf</small>
                                <h5 class="fw-bold text-warning mb-0" id="w_drug_title_lbl">--</h5>
                            </div>
                            <span class="badge bg-label-warning font-monospace text-xs py-1" id="w_date_lbl">--</span>
                        </div>
                        <div class="small text-dark mt-2"><b>Requested By Pharmacist:</b> <span id="w_staff_lbl">--</span></div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 border rounded bg-white text-center shadow-xs">
                                <small class="text-muted d-block font-monospace text-uppercase small" style="font-size:0.7rem;">Quantity Demanded</small>
                                <h3 class="fw-bold text-warning font-monospace mb-0" id="w_qty_demanded_lbl">0</h3>
                                <small class="text-muted text-xs">Shelf Restock Target</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded bg-white text-center shadow-xs" id="w_warehouse_stock_card_box">
                                <small class="text-muted d-block font-monospace text-uppercase small" style="font-size:0.7rem;">Warehouse Balance Available</small>
                                <h3 class="fw-bold text-dark font-monospace mb-0" id="w_qty_available_lbl">0</h3>
                                <small class="text-muted text-xs d-block" id="w_stock_comparison_status_lbl">Checking supply...</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 py-2 fw-bold shadow-sm" id="btn_confirm_warehouse_issuance">
                        <i class="bx bx-check-shield fs-5 me-1"></i> Fulfill Asset Request & Issue Stock to Pharmacy
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php';?> 
 
<script>
var globalMainStoreVouchersQueue = [];

function syncMainStoreRequisitionsQueue() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { main_store_fetch: true },
        dataType: 'json',
        success: function(res) {
            console.log('response: ', res);
            try {
                if(res.status === 'success') {
                    globalMainStoreVouchersQueue = res.data;
                    $('#stat_warehouse_items').text(res.total_warehouse_items || 0);
                    renderMainStoreQueueUI();
                } else {
                    console.error("Server error message: ", res.message);
                }
            } catch (err) {
                console.error("UI rendering exception crash: ", err.message);
            }
        },
        error: function(xhr, status, error) {
            console.group("MAIN DEPOT FETCH RESPONSE TRACE");
            console.error("Response text payload: ", xhr.responseText);
            console.groupEnd();
        }
    });
}

function renderMainStoreQueueUI() {
    var $box = $('#main_store_queue_cards_container').empty();
    $('#ms_req_badge, #stat_pending_vouchers').text(globalMainStoreVouchersQueue.length);

    if(globalMainStoreVouchersQueue.length === 0) {
        $box.html('<div class="text-center text-muted py-5 small"><i class="bx bx-check-double d-block fs-2 mb-1 text-success"></i> All sub-depot asset requests fulfilled! Depot cleared.</div>');
        $('#ms_empty_state').removeClass('d-none');
        $('#mainStoreActionForm').addClass('d-none');
        return;
    }

    globalMainStoreVouchersQueue.forEach(function(req, idx) {
        var cardMarkup = `
            <div class="card border mb-1.5 shadow-none cursor-pointer btn-select-main-store-voucher transition-all" data-index="${idx}" style="border-radius:6px;">
                <div class="card-body p-2" style="font-size:0.8rem;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width:70%; font-size:0.85rem;">${$('<div>').text(req.drug_name).html()}</h6>
                        <span class="badge bg-label-warning text-warning font-monospace text-xs py-0 px-1.5">${req.requested_qty} units</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted" style="font-size:0.7rem;">
                        <span>Req by: ${req.staff_name}</span>
                        <span><i class="bx bx-time"></i> ${req.date_requested.split(' ')[0]}</span>
                    </div>
                </div>
            </div>`;
        $box.append(cardMarkup);
    });
}
$(document).on('click', '.btn-select-main-store-voucher', function(e) {
    e.preventDefault();
    $('.btn-select-main-store-voucher').removeClass('border-warning bg-label-warning');
    $(this).addClass('border-warning bg-label-warning');

    var index = $(this).data('index');
    var req = globalMainStoreVouchersQueue[index];
    if(!req) return;

    // Reveal work deck form area
    $('#ms_empty_state').addClass('d-none');
    $('#mainStoreActionForm').removeClass('d-none').css('display', 'flex');

    // Bind hidden parameters input data matrix fields
    $('#w_req_id').val(req.requisition_id);
    $('#w_warehouse_drug_id').val(req.warehouse_drug_id);
    $('#w_pharmacy_drug_id').val(req.pharmacy_drug_id);
    $('#w_requested_qty').val(req.requested_qty);

    // Bind descriptive string text headers
    $('#w_drug_title_lbl').text(req.drug_name);
    $('#w_date_lbl').text(req.date_requested);
    $('#w_staff_lbl').text(req.staff_name);
    $('#w_qty_demanded_lbl').text(req.requested_qty);
    
    var warehouseStockQty = parseInt(req.main_warehouse_qty || 0);
    $('#w_qty_available_lbl').text(warehouseStockQty);

    var $stockCardBox = $('#w_warehouse_stock_card_box').removeClass('border-danger border-success');
    var $comparisonLabel = $('#w_stock_comparison_status_lbl').removeClass('text-danger text-success fw-bold');
    var $submitBtn = $('#btn_confirm_warehouse_issuance');

    // --- CHECK INVENTORY CAPACITY AGAINST DEMANDED QUANTITY ---
    if (warehouseStockQty < parseInt(req.requested_qty)) {
        $stockCardBox.addClass('border-danger');
        $comparisonLabel.addClass('text-danger fw-bold').html('<i class="bx bx-error-circle"></i> Stock Insufficient inside Depot');
        $submitBtn.attr('disabled', true).removeClass('btn-warning').addClass('btn-secondary').html('<i class="bx bx-lock-alt"></i> Cannot Issue: Warehouse Short');
    } else {
        $stockCardBox.addClass('border-success');
        $comparisonLabel.addClass('text-success fw-bold').html('<i class="bx bx-check-circle"></i> Supply Levels OK');
        $submitBtn.attr('disabled', false).removeClass('btn-secondary').addClass('btn-warning').html('<i class="bx bx-check-shield fs-5 me-1"></i> Fulfill Asset Request & Issue Stock');
    }
});

// SUBMIT TRANSIT INTERCEPTOR: FORWARD SETTLEMENT TRANSACTION DEDUCTION LOGS TO MAIN SERVER
$('#mainStoreActionForm').on('submit', function(e) {
    e.preventDefault();
    var form = this;

    Swal.fire({
        title: 'Approve Stock Distribution?',
        text: 'This will deduct drug volumes from the main warehouse reserves and instantly inject them into the active pharmacy shelf counts.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff9f43',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Issue Stock Assets',
        cancelButtonText: 'Review Voucher'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        Swal.fire({ 
            title: 'Transferring sub-depot asset rows...', 
            html: 'Locking warehouse rows and adjusting inventory balances...',
            allowOutsideClick: false, 
            didOpen: () => { Swal.showLoading(); } 
        });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.status === 'success') {
                    Swal.fire({ 
                        title: 'Assets Transferred', 
                        text: 'Voucher marked as approved. Pharmacy stock numbers increased successfully.', 
                        icon: 'success', 
                        timer: 1500, 
                        showConfirmButton: false 
                    });
                    
                    // Reset right panel state back to neutral default empty container screen view
                    $('#mainStoreActionForm').addClass('d-none');
                    $('#ms_empty_state').removeClass('d-none');
                    
                    // Repopulate queues with fresh backend state response data elements array
                    globalMainStoreVouchersQueue = response.data;
                    $('#stat_warehouse_items').text(response.total_warehouse_items || 0);
                    renderMainStoreQueueUI();
                } else {
                    Swal.fire('Transfer Blocked', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                console.error("Depot dispatch transmission failure text response: ", xhr.responseText);
                Swal.fire('Network Error', 'The main store asset allocation pipeline failed on transit.', 'error');
            }
        });
    });
});

// AUTO-REFRESH INITIALIZER PIPELINE ON RUNTIME BOOT
$(document).ready(function() {
    syncMainStoreRequisitionsQueue();
});
</script> 