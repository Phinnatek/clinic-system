<?php require_once 'includes/header.php';?>  

<div class="container-fluid p-3">
    <!-- TOP STATUS COUNTERS -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-success p-1 rounded"><i class="bx bx-check-shield text-success fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted">Healthy Stock Items</h6><h4 class="mb-0 fw-bold font-monospace" id="stat_healthy_count">0</h4></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-warning p-1 rounded"><i class="bx bx-trending-down text-warning fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted">Low Stock Warnings</h6><h4 class="mb-0 fw-bold font-monospace text-warning" id="stat_low_count">0</h4></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-danger p-1 rounded"><i class="bx bx-error text-danger fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted">Expired Batch Rows</h6><h4 class="mb-0 fw-bold font-monospace text-danger" id="stat_expired_count">0</h4></div>
            </div>
        </div>
    </div>

    <!-- MAIN SHELF CATALOG LIST -->
    <div class="card shadow-none border">
        <div class="card-header bg-white border-bottom py-2.5">
            <h5 class="fw-bold text-dark mb-0"><i class="bx bx-package text-primary me-1"></i> Active Store Shelf Inventory</h5>
        </div>
        <div class="card-body p-2" id="inventory_table_wrapper">
            <!-- JavaScript generates table here safely -->
        </div>
    </div>
</div>

<!-- INVENTORY ADJUSTMENT MINI MODAL BOX -->
<div class="modal fade" id="mdlEditDrugMetrics" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form id="frmEditMetrics" class="modal-content">
            <input type="hidden" name="action_update_drug_metrics" value="true" />
            <input type="hidden" name="edit_drug_id" id="edit_drug_id" />
            <div class="modal-header border-bottom py-2 bg-light">
                <h6 class="modal-title fw-bold text-dark" id="lbl_edit_title">Adjust Metrics</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2.5">
                <div class="mb-2">
                    <label class="form-label small text-muted mb-0">Selling Price Rate (GHS)</label>
                    <input type="number" step="0.01" name="edit_price" id="edit_price" class="form-control form-control-sm font-monospace" required />
                </div>
                <div>
                    <label class="form-label small text-muted mb-0">Minimum Safety Threshold Limit</label>
                    <input type="number" name="edit_threshold" id="edit_threshold" class="form-control form-control-sm font-monospace" required />
                </div>
            </div>
            <div class="modal-footer border-top p-2 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-xs py-1" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-xs py-1">Save Changes</button>
            </div>
        </form>
    </div>
</div>
 
<?php require_once 'includes/footer.php';?> 
 
<script>

function loadInventoryOnly() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { pharmacy_inventory_fetch: true },
        dataType: 'json',
        success: function(res) {
            console.log('response: ', res);
            try {
                if(res.status === 'success') {
                    var $wrapper = $('#inventory_table_wrapper').empty();
                    if (!res.inventory || !res.inventory.length) {
                        $wrapper.html('<div class="text-center text-muted py-5 small">Stock ledger is completely blank.</div>');
                        return;
                    }

                    var healthy = 0, low = 0, exp = 0;
                    var tableHtml = `<table class="table table-sm table-hover align-middle mb-0 font-monospace" id="DataTable" style="font-size:0.75rem;">
                        <thead class="table-light sticky-top">
                            <tr><th class="ps-2">Drug Name Item</th><th>Price</th><th>Qty Left</th><th>Status</th><th class="text-center">Action</th></tr>
                        </thead><tbody>`;

                    res.inventory.forEach(function(item) {
                        var badge = "bg-label-success text-success";
                        if (item.stock_health_status === 'Low Stock') { badge = "bg-label-warning text-warning"; low++; }
                        else if (item.stock_health_status === 'Expired') { badge = "bg-label-danger text-danger"; exp++; }
                        else { healthy++; }

                        tableHtml += `
                            <tr data-id="${item.id}" data-price="${item.selling_price}" data-threshold="${item.min_threshold_qty}" data-name="${item.drug_name}">
                                <td class="ps-2 fw-semibold text-dark text-truncate" style="max-width:200px;">${$('<div>').text(item.drug_name).html()}<small class="text-muted d-block" style="font-size:0.65rem;">Exp: ${item.formatted_expiry || 'N/A'}</small></td>
                                <td class="fw-bold text-secondary">GHS ${parseFloat(item.selling_price).toFixed(2)}</td>
                                <td class="fw-bold ${item.quantity_in_store <= item.min_threshold_qty ? 'text-danger' : 'text-dark'}">${item.quantity_in_store} <small class="text-muted fw-normal">(Min: ${item.min_threshold_qty})</small></td>
                                <td><span class="badge ${badge} py-0 px-1 text-uppercase">${item.stock_health_status}</span></td>
                                <td class="text-center"><button type="button" class="btn btn-xs btn-outline-primary py-0.5 px-1.5 btn-trigger-edit-metrics"><i class="bx bx-edit-alt"></i> Adjust</button></td>
                            </tr>`;
                    });

                    tableHtml += '</tbody></table>';
                    $wrapper.html(tableHtml);
                    $('#stat_healthy_count').text(healthy);
                    $('#stat_low_count').text(low);
                    $('#stat_expired_count').text(exp);
                    initializeDataTable();
                } else {
                    console.error("Server returned an error status:", res.message || res);
                }
            } catch (renderError) {
                console.error("CRITICAL UI RENDERING CRASH INSIDE RE-DRAW LOOP:", renderError.message);
            }
        },
        error: function(xhr, status, error) {
            console.group("PHARMACY STORE FETCH FAULT");
            console.error("Connection Status State:", status);
            console.error("Thrown Exception Error Message:", error);
            console.error("Raw Backend Response Text:", xhr.responseText);
            console.groupEnd();
        }
    });
}

$(document).on('click', '.btn-trigger-edit-metrics', function(e) {
    e.preventDefault();
    var $tr = $(this).closest('tr');
    $('#edit_drug_id').val($tr.attr('data-id'));
    $('#lbl_edit_title').text('Update: ' + $tr.attr('data-name'));
    $('#edit_price').val($tr.attr('data-price'));
    $('#edit_threshold').val($tr.attr('data-threshold'));
    $('#mdlEditDrugMetrics').modal('show');
});

$('#frmEditMetrics').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: server_url,
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#mdlEditDrugMetrics').modal('hide');
                Swal.fire({ title: 'Metrics Updated', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                loadInventoryOnly();
            }
        }
    });
});

$(document).ready(function() { loadInventoryOnly(); });
</script>
</body>
</html>

 