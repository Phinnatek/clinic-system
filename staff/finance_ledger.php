<?php require_once 'includes/header.php';?> 

<div class="container-fluid p-3">
    <!-- REVENUE STATS SCORECARD OVERLAY ROW -->
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-success p-1 rounded"><i class="bx bx-trending-up text-success fs-4"></i></div>
                <div><h6 class="mb-0 small text-muted" style="font-size:0.75rem;">Total Inflows (Collections)</h6><h4 class="mb-0 fw-bold font-monospace text-success" id="lbl_total_inflow">GHS 0.00</h4></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-danger p-1 rounded"><i class="bx bx-trending-down text-danger fs-4"></i></div>
                <div><h6 class="mb-0 small text-muted" style="font-size:0.75rem;">Total Outflows (Expenses)</h6><h4 class="mb-0 fw-bold font-monospace text-danger" id="lbl_total_outflow">GHS 0.00</h4></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-2 shadow-none border bg-white d-flex flex-row align-items-center gap-2" id="net_balance_card_box">
                <div class="avatar bg-label-primary p-1 rounded" id="net_balance_avatar"><i class="bx bx-wallet text-primary fs-4"></i></div>
                <div><h6 class="mb-0 small text-muted" style="font-size:0.75rem;">Net Cash Runway Balance</h6><h4 class="mb-0 fw-bold font-monospace" id="lbl_net_balance">GHS 0.00</h4></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- COLUMN 1: RECORD MANUAL CASH OUTFLOW DISPATCH COMPONENT (Width 4) -->
        <div class="col-md-4">
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-minus-circle text-danger me-1"></i> Record Facility Cash Outflow</h6>
                </div>
                <div class="card-body pt-2 pb-2">
                    <form id="frmLogOutflow" method="POST">
                        <input type="hidden" name="action_log_expense" value="true" />
                        <div class="mb-2">
                            <label class="form-label text-muted small scale-xs mb-0" style="font-size:0.7rem;">Expense Category</label>
                            <select name="exp_category" id="exp_category" class="form-select form-select-sm" required>
                                <option value="" selected disabled>-- Select Expenditure Group --</option>
                                <option value="Supplier Payment">Supplier Payment (Drug Procurement)</option>
                                <option value="Facility Expenses">Facility Expenses (Utility Bills/Rent)</option>
                                <option value="Salaries">Staff Salaries & Allowances</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small scale-xs mb-0" style="font-size:0.7rem;">Outflow Volume Amount (GHS)</label>
                            <input type="number" step="0.01" name="exp_amount" id="exp_amount" class="form-control form-control-sm font-monospace" placeholder="0.00" required />
                        </div>
                        <div class="mb-2.5">
                            <label class="form-label text-muted small scale-xs mb-0" style="font-size:0.7rem;">Transaction Auditing Description Memo</label>
                            <textarea name="exp_desc" id="exp_desc" class="form-control text-xs font-monospace" rows="2" placeholder="Type detail descriptions e.g. Purchased oxygen cylinders batch..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm w-100 py-2 fw-bold"><i class="bx bx-log-out-circle me-1"></i> Log Expense Voucher</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- COLUMN 2: LIVE CASH FLOW BALANCES TRANSACTION LEDGER (Width 8) -->
        <div class="col-md-8">
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-list-ul text-primary me-1"></i> Cash Flow Audit Ledger Registry</h6>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0.5" onclick="syncFinanceLedgerDashboard()"><i class="bx bx-refresh"></i> Refresh</button>
                </div>
                <div class="card-body p-1.5" id="finance_table_wrapper" style="max-height: 480px; overflow-y: auto;">
                    <!-- Javascript generates custom transaction grids row lines here -->
                </div>
            </div>
        </div>
    </div>
</div> 

<?php require_once 'includes/footer.php';?>

<script>

function syncFinanceLedgerDashboard() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { finance_ledger_fetch: true },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                // 1. UPDATE STATS COUNTER WIDGET NODES
                $('#lbl_total_inflow').text('GHS ' + res.inflow.toFixed(2));
                $('#lbl_total_outflow').text('GHS ' + res.outflow.toFixed(2));
                $('#lbl_net_balance').text('GHS ' + res.net_balance.toFixed(2));

                // Dynamic coloring logic based on net valuation calculations
                var $box = $('#net_balance_card_box').removeClass('bg-light-danger bg-light-success');
                var $txt = $('#lbl_net_balance').removeClass('text-danger text-success');
                if (res.net_balance < 0) {
                    $box.addClass('bg-light-danger'); $txt.addClass('text-danger');
                } else {
                    $box.addClass('bg-light-success'); $txt.addClass('text-success');
                }

                // 2. RENDER THE DETAILED LEDGER GRID ROWS
                var $wrapper = $('#finance_table_wrapper').empty();
                if(!res.logs || res.logs.length === 0) {
                    $wrapper.html('<div class="text-center text-muted py-5 small italic">No financial ledger transactions logged for this fiscal year.</div>');
                    return;
                }

                var tableHtml = `
                    <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size:0.72rem; width:100%;" id="DataTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-1" style="width:15%;">Type</th>
                                <th style="width:20%;">Category</th>
                                <th style="width:45%;">Auditing Memo Description</th>
                                <th class="text-end pe-1" style="width:20%;">Amount</th>
                            </tr>
                        </thead><tbody>`;

                res.logs.forEach(function(log) {
                    var isInflow = (log.transaction_type === 'Inflow');
                    var badgeClass = isInflow ? "bg-label-success text-success" : "bg-label-danger text-danger";
                    var prefix = isInflow ? "+" : "-";

                    tableHtml += `
                        <tr class="transition-all">
                            <td class="ps-1"><span class="badge ${badgeClass} py-0 px-1 text-xs text-uppercase font-monospace">${log.transaction_type}</span></td>
                            <td class="fw-bold text-dark">${log.category}</td>
                            <td class="text-muted text-truncate" style="max-width:260px;" title="${log.description}">${$('<div>').text(log.description).html()}<small class="d-block text-xs" style="font-size:0.62rem;"><i class="bx bx-time"></i> Logs: ${log.date_logged} | Cashier: ${log.cashier_name}</small></td>
                            <td class="text-end pe-1 fw-bold ${isInflow ? 'text-success' : 'text-danger'}" style="font-size:0.8rem;">${prefix} GHS ${parseFloat(log.amount).toFixed(2)}</td>
                        </tr>`;
                });

                tableHtml += '</tbody></table>';
                $wrapper.html(tableHtml);
                initializeDataTable();
            }
        }
    });
}

// SUBMIT EXPENSE OUTFLOWS TRANSACTIONS LINES DETECTOR VIA AJAX
$('#frmLogOutflow').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    Swal.fire({
        title: 'Authorize Outflow Expense?',
        text: 'This logs the expenditure, adjusts capital counts, and records an auditing memo in the ledger.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff3e1d',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Yes, Log Outflow'
    }).then((res) => {
        if (!res.isConfirmed) return;
        $.ajax({
            url: server_url,
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({ title: 'Expense Logged', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                    form.reset();
                    
                    // Live synchronize UI metrics values immediately
                    $('#lbl_total_inflow').text('GHS ' + response.inflow.toFixed(2));
                    $('#lbl_total_outflow').text('GHS ' + response.outflow.toFixed(2));
                    $('#lbl_net_balance').text('GHS ' + response.net_balance.toFixed(2));
                    syncFinanceLedgerDashboard();
                } else {
                    Swal.fire('Declined', response.message, 'error');
                }
            }
        });
    });
});

$(document).ready(function() { syncFinanceLedgerDashboard(); });
</script>

