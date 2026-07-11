<?php require_once 'includes/header.php';?>   

<div class="container-fluid p-3">
    <!-- DASHBOARD SECTION HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bx bx-archive text-primary me-1"></i> Warehouse Inventory Vault</h4>
            <small class="text-muted">Manage hospital bulk supply reserves, map batch lot codes, and process new supplier shipment deliveries</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary py-1" onclick="loadWarehouseInventory()"><i class="bx bx-refresh"></i> Refresh Inventory</button>
    </div>

    <div class="row g-3">
        <!-- LEFT COLUMN: BULK VAULT CATALOG TABLE (Width 7) -->
        <div class="col-md-7">
            <div class="card shadow-none border h-100">
                <div class="card-header bg-white border-bottom py-2.5">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-list-ul text-primary me-1"></i> Warehouse Master Stock Balance Sheet</h6>
                </div>
                <div class="card-body p-2" id="warehouse_table_wrapper">
                    <div class="text-center text-muted py-5 small italic">Loading master supply ledger...</div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: SHIPMENT ENTRY & STOCK UPDATES FORM (Width 5) -->
        <div class="col-md-5">
            <div class="card shadow-none border h-100">
                <div class="card-header bg-white border-bottom py-2.5">
                    <h6 class="fw-bold text-dark mb-0"><i class="bx bx-plus-circle text-success me-1"></i> Log Supplier Shipment / New Drug</h6>
                </div>
                <div class="card-body pt-3">
                    <form id="frmLogShipment" method="POST">
                        <input type="hidden" name="action_log_warehouse_shipment" value="true" />
                        
                        <div class="mb-2.5">
                            <label class="form-label text-muted small fw-semibold mb-1" for="drug_name">Medication / Asset Item Name</label>
                            <input type="text" name="drug_name" id="drug_name" class="form-control form-control-sm" placeholder="e.g., Amoxicillin 500mg" required />
                        </div>
                        
                        <div class="row g-2 mb-2.5">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-semibold mb-1" for="quantity">Received Quantity</label>
                                <input type="number" name="quantity" id="quantity" class="form-control form-control-sm font-monospace" min="1" placeholder="e.g., 1000" required />
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-semibold mb-1" for="batch_number">Batch / Lot Number</label>
                                <input type="text" name="batch_number" id="batch_number" class="form-control form-control-sm font-monospace" placeholder="e.g., BCH-2026-X" required />
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-sm w-100 py-2.5 fw-bold shadow-sm">
                            <i class="bx bx-plus me-1"></i> Receive Stock Into Warehouse Vault
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
 
<?php require_once 'includes/footer.php';?> 
 <script>

function loadWarehouseInventory() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { warehouse_inventory_fetch: true },
        dataType: 'json',
        success: function(res) {
            console.log('response: ', res);
            try {
                if(res.status === 'success') {
                    var $wrapper = $('#warehouse_table_wrapper').empty();
                    if (!res.data || res.data.length === 0) {
                        $wrapper.html('<div class="text-center text-muted py-5 small italic"><i class="bx bx-buildings d-block mb-1 fs-2 text-secondary"></i> Warehouse vault is completely empty. Please log initial shipment profiles.</div>');
                        return;
                    }

                    var tableHtml = `
                        <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size:0.75rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-2" style="width: 45%;">Managed Asset Item</th>
                                    <th style="width: 25%;">Batch Number</th>
                                    <th style="width: 30%;">Warehouse Balance</th>
                                </tr>
                            </thead>
                            <tbody>`;

                    res.data.forEach(function(item) {
                        tableHtml += `
                            <tr>
                                <td class="ps-2 fw-semibold text-dark text-truncate" style="max-width:220px;">
                                    ${$('<div>').text(item.drug_name).html()}
                                    <small class="text-muted d-block" style="font-size:0.65rem;"><i class="bx bx-time"></i> Last Updated: ${item.formatted_date}</small>
                                </td>
                                <td class="text-secondary fw-semibold">${$('<div>').text(item.batch_number || 'N/A').html()}</td>
                                <td class="fw-bold ${item.quantity_in_warehouse <= 100 ? 'text-danger' : 'text-dark'}" style="font-size:0.85rem;">
                                    ${item.quantity_in_warehouse} <small class="text-muted fw-normal">units</small>
                                </td>
                            </tr>`;
                    });

                    tableHtml += '</tbody></table>';
                    $wrapper.html(tableHtml);
                } else {
                    console.error("Server query validation failed:", res.message);
                }
            } catch (renderErr) {
                console.error("UI rendering pipeline crash exception:", renderErr.message);
            }
        },
        error: function(xhr, status, error) {
            console.group("WAREHOUSE REPOSITORY AJAX ERROR");
            console.error("Raw Backend Server Output:", xhr.responseText);
            console.groupEnd();
        }
    });
}

$('#frmLogShipment').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    
    Swal.fire({
        title: 'Receive Bulk Shipment?',
        text: 'This logs the received quantities, indexes the batch number lot, and updates your master warehouse supply count.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28c76f',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Ingest Stock Assets',
        cancelButtonText: 'Review Form'
    }).then((res) => {
        if (!res.isConfirmed) return;
        
        Swal.fire({ title: 'Ingesting bulk assets inventory...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.status === 'success') {
                    Swal.fire({ title: 'Shipment Processed', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                    form.reset();
                    loadWarehouseInventory(); // Synchronize live totals table fresh
                } else {
                    Swal.fire('Ingest Rejected', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.close();
                console.error("Shipment processing crash text response:", xhr.responseText);
                Swal.fire('Connection Error', 'The request could not clear the network pipeline.', 'error');
            }
        });
    });
});

$(document).ready(function() {
    loadWarehouseInventory();
});
</script> 