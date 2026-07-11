<?php require_once 'includes/header.php';?>   
 

<div class="container-fluid p-3">
    <!-- DASHBOARD SECTION HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bx bx-git-pull-request text-warning me-1"></i> Stock Requisitions Desk</h4>
            <small class="text-muted">Generate restock demand vouchers and monitor main warehouse supply transfers</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary py-1" onclick="loadRequisitionSuiteData()"><i class="bx bx-refresh"></i> Refresh Lists</button>
    </div>

    <div class="row g-3">
        <!-- LEFT COLUMN: QUICK REQUISITION GENERATOR FORM (Width 5) -->
        <div class="col-md-5">
            <div class="card shadow-none border h-100">
                <div class="card-header bg-white border-bottom py-2.5">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-edit text-primary me-1"></i> Draft New Restock Demand</h6>
                </div>
                <div class="card-body pt-3">
                    <form id="frmRequisitionAction" method="POST">
                        <input type="hidden" name="action_submit_requisition" value="true" />
                        
                        <div class="mb-2.5">
                            <label class="form-label text-muted small fw-semibold mb-1" for="req_drug_id">Choose Medication Item</label>
                            <select name="req_drug_id" id="req_drug_id" class="form-select form-select-sm" required>
                                <option value="" selected disabled>-- Select Shelf Asset Target --</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold mb-1" for="req_qty">Demanded Restock Quantity Volume</label>
                            <input type="number" name="req_qty" id="req_qty" class="form-control form-control-sm font-monospace" min="1" placeholder="e.g., 500" required />
                            <div class="form-text text-muted text-xs mt-0.5">Enter total box or bottle counts needed for front shelf preparation.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning btn-sm w-100 py-2.5 fw-bold shadow-sm">
                            <i class="bx bx-paper-plane me-1"></i> Dispatch Requisition to Main Depot
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: CHRONOLOGICAL TIMELINE VOUCHERS TRACKER (Width 7) -->
        <div class="col-md-7">
            <div class="card shadow-none border h-100">
                <div class="card-header bg-white border-bottom py-2.5">
                    <h6 class="fw-bold text-secondary mb-0"><i class="bx bx-history me-1"></i> Recent Depot Requests Log Tracker</h6>
                </div>
                <div class="card-body p-2" id="requisitions_logs_wrapper">
                    <!-- Javascript appends request logs elements here natively -->
                    <div class="text-center text-muted py-5 small italic">Loading procurement history...</div>
                </div>
            </div>
        </div>
    </div>
</div>
 
<?php require_once 'includes/footer.php';?> 
  <script>

function loadRequisitionSuiteData() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { pharmacy_inventory_fetch: true },
        dataType: 'json',
        success: function(res) {
            console.log('response: ', res);
            try {
                if(res.status === 'success') {
                    // 1. POPULATE THE MEDICATION PICKER DROPDOWN SELECT
                    var $select = $('#req_drug_id');
                    var currentCacheValue = $select.val(); // Remembers choice if pharmacist is currently typing
                    var placeholderOption = $select.find('option:first');
                    $select.empty().append(placeholderOption);
                    
                    if (res.inventory && res.inventory.length > 0) {
                        res.inventory.forEach(function(item) {
                            var lowStockMark = (item.quantity_in_store <= item.min_threshold_qty) ? ' [LOW STOCK]' : '';
                            $select.append(`<option value="${item.id}">${$('<div>').text(item.drug_name).html()} (Current Shelf Qty: ${item.quantity_in_store})${lowStockMark}</option>`);
                        });
                    }
                    if (currentCacheValue) $select.val(currentCacheValue);

                    // 2. RENDER THE CHROMIUM PROCUREMENT HISTORY VOUCHERS GRID
                    var $box = $('#requisitions_logs_wrapper').empty();
                    if (!res.requisitions || res.requisitions.length === 0) {
                        $box.html('<div class="text-center text-muted py-5 small italic"><i class="bx bx-git-pull-request d-block mb-1 fs-2 text-secondary"></i> No historic procurement request orders found for this branch.</div>');
                        return;
                    }

                    res.requisitions.forEach(function(req) {
                        var statusBadgeClass = "bg-label-warning text-warning";
                        if (req.status === 'Approved') statusBadgeClass = "bg-label-success text-success";
                        if (req.status === 'Rejected') statusBadgeClass = "bg-label-danger text-danger";

                        var voucherMarkup = `
                            <div class="p-2 border rounded bg-white shadow-xs mb-1.5 font-monospace transition-all" style="font-size:0.75rem;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width:70%; font-size:0.78rem;">
                                        <i class="bx bx-right-arrow-alt text-warning"></i> ${$('<div>').text(req.drug_name).html()}
                                    </h6>
                                    <span class="badge ${statusBadgeClass} py-0 px-1.5 text-xs text-uppercase">${req.status}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1.5 text-muted" style="font-size:0.7rem;">
                                    <span>Demanded Restock Volume: <b class="text-dark">${req.requested_qty} units</b></span>
                                    <span>Initiator Staff: ${req.staff_name || 'Pharmacist'}</span>
                                </div>
                                <small class="text-muted d-block border-top pt-1 mt-1" style="font-size:0.65rem;">
                                    <i class="bx bx-time"></i> Voucher Dispatched: ${req.date_sent}
                                </small>
                            </div>`;
                        $box.append(voucherMarkup);
                    });
                } else {
                    console.error("Server query validation failed:", res.message);
                }
            } catch (renderErr) {
                console.error("UI rendering pipeline crash exception:", renderErr.message);
            }
        },
        error: function(xhr, status, error) {
            console.group("PHARMACY REQUISITION TRANSMISSION PIPELINE ERROR");
            console.error("Status State:", status);
            console.error("Raw Backend Server Output:", xhr.responseText);
            console.groupEnd();
        }
    });
}

// 3. SUBMIT EVENT HANDLER: DISPATCH DEMAND PACKS SECURELY DOWN THE PIPELINE
$('#frmRequisitionAction').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    
    Swal.fire({
        title: 'Dispatch Stock Requisition?',
        text: 'This generates a formal supply demand request and pushes it onto the main warehouse depot queue.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ff9f43',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Send Request',
        cancelButtonText: 'Review Form'
    }).then((res) => {
        if (!res.isConfirmed) return;
        
        Swal.fire({ title: 'Transmitting requisition voucher...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.status === 'success') {
                    Swal.fire({ title: 'Voucher Dispatched', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                    form.reset();
                    loadRequisitionSuiteData(); // Triggers live updates to sync logs instantly
                } else {
                    Swal.fire('Request Blocked', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                console.error("Requisition commit crash response text:", xhr.responseText);
                Swal.fire('Connection Error', 'The request could not clear the network pipeline.', 'error');
            }
        });
    });
});

// Auto-run core sync routine immediately upon page load initialization
$(document).ready(function() {
    loadRequisitionSuiteData();
});
</script> 