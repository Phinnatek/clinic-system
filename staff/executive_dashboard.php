<?php require_once 'includes/header.php';?> 

<div class="container-fluid p-3">
    <!-- REVENUE AND BUSINESS PERFORMANCE STATS ROW -->
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card p-2 border shadow-none bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-success p-1 rounded"><i class="bx bx-dollar-circle text-success fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted text-nowrap" style="font-size:0.72rem;">Total Revenue (Inflow)</h6><h4 class="mb-0 fw-bold font-monospace text-success" id="lbl_inflow">GHS 0.00</h4></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-2 border shadow-none bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-danger p-1 rounded"><i class="bx bx-trending-down text-danger fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted text-nowrap" style="font-size:0.72rem;">Total Expenditures (Outflow)</h6><h4 class="mb-0 fw-bold font-monospace text-danger" id="lbl_outflow">GHS 0.00</h4></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-2 border shadow-none bg-white d-flex flex-row align-items-center gap-2" id="box_net_runway">
                <div class="avatar bg-label-primary p-1 rounded" id="avatar_net"><i class="bx bx-wallet text-primary fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted text-nowrap" style="font-size:0.72rem;">Net Cash Balance Runway</h6><h4 class="mb-0 fw-bold font-monospace" id="lbl_net_balance">GHS 0.00</h4></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-2 border shadow-none bg-white d-flex flex-row align-items-center gap-2">
                <div class="avatar bg-label-info p-1 rounded"><i class="bx bx-user-voice text-info fs-3"></i></div>
                <div><h6 class="mb-0 small text-muted text-nowrap" style="font-size:0.72rem;">Total Patient Visits Logged</h6><h4 class="mb-0 fw-bold font-monospace text-info" id="lbl_total_cases">0</h4></div>
            </div>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN SYSTEM GRID LAYOUT -->
    <div class="row g-3">
        <!-- LEFT COLUMN: OPERATIONAL BOTTLENECK CHANNELS & DISTRIBUTION LOGS (Width 6) -->
        <div class="col-md-6">
            <!-- PANEL A: CLINIC OPERATIONAL BOTTLENECKS LIVE MONITOR -->
            <div class="card shadow-none border mb-3">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-pulse text-danger me-1"></i> Department Operational Activity Tracks</h6>
                </div>
                <div class="card-body p-2.5">
                    <div class="row g-2 text-center font-monospace">
                        <div class="col-4"><div class="p-2 border rounded bg-white"><small class="text-muted text-xs d-block mb-1">Ward Admissions</small><h4 class="mb-0 fw-bold text-dark" id="lbl_admissions">0</h4></div></div>
                        <div class="col-4"><div class="p-2 border rounded bg-white"><small class="text-muted text-xs d-block mb-1">Pending Lab Tests</small><h4 class="mb-0 fw-bold text-info" id="lbl_pending_labs">0</h4></div></div>
                        <div class="col-4"><div class="p-2 border rounded bg-white"><small class="text-muted text-xs d-block mb-1">Pending Pharmacy</small><h4 class="mb-0 fw-bold text-warning" id="lbl_pending_pharmacy">0</h4></div></div>
                    </div>
                </div>
            </div>

            <!-- PANEL B: INFLOW CASH CATEGORY BREAKDOWN METRICS CHART LIST -->
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-pie-chart-alt text-success me-1"></i> Revenue Stream Collections Breakdown</h6>
                </div>
                <div class="card-body p-2.5" id="revenue_distribution_wrapper">
                    <!-- Progress ticker metrics inject dynamically here -->
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: PRESCRIPTION FREQUENCIES AND EXPENSES HISTOGRAMS (Width 6) -->
        <div class="col-md-6">
            <!-- PANEL C: TOP PRESCRIBED MEDICATIONS MATRICES -->
            <div class="card shadow-none border mb-3">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-capsule text-warning me-1"></i> Top 5 Fast-Moving Clinic Medications</h6>
                </div>
                <div class="card-body p-1.5" id="top_drugs_wrapper">
                    <!-- Dynamic drug frequency line bars render here -->
                </div>
            </div>

            <!-- PANEL D: RECENT HIGH-VALUE SYSTEM OUTFLOWS LOGS REGISTRY -->
            <div class="card shadow-none border">
                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-receipt text-secondary me-1"></i> Recent Operational Expenditure Claims</h6>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0.5" onclick="syncExecutiveDashboard()"><i class="bx bx-refresh"></i> Sync</button>
                </div>
                <div class="card-body p-1.5" id="expenses_table_wrapper">
                    <!-- Table matrix logs items inject here safely -->
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php';?>

<script> 
 function syncExecutiveDashboard() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { fetch_executive_metrics: true },
        dataType: 'json',
        success: function(res) {
            console.log('Executive response: ', res);
            try {
                if(res.status === 'success') {
                    // 1. UPDATE OVERVIEW SCORECARD CARD LABELS
                    $('#lbl_inflow').text('GHS ' + res.financials.inflow.toFixed(2));
                    $('#lbl_outflow').text('GHS ' + res.financials.outflow.toFixed(2));
                    $('#lbl_net_balance').text('GHS ' + res.financials.net_balance.toFixed(2));
                    $('#lbl_total_cases').text(res.caseload.total_cases);
                    
                    // Track Net balance performance safety background shading colors
                    var $box = $('#box_net_runway').removeClass('bg-light-danger bg-light-success');
                    var $txt = $('#lbl_net_balance').removeClass('text-danger text-success');
                    if(res.financials.net_balance < 0) { 
                        $box.addClass('bg-light-danger'); 
                        $txt.addClass('text-danger'); 
                    } else { 
                        $box.addClass('bg-light-success'); 
                        $txt.addClass('text-success'); 
                    }

                    // 2. MOUNT DEPARTMENT DYNAMIC FLOWS QUEUES TRACKERS
                    $('#lbl_admissions').text(res.caseload.admissions);
                    $('#lbl_pending_labs').text(res.caseload.pending_labs);
                    $('#lbl_pending_pharmacy').text(res.caseload.pending_pharmacy);

                    // 3. RENDER REVENUE COLLECTIONS PROGRESS TRACK TICKERS
                    var $revHook = $('#revenue_distribution_wrapper').empty();
                    if(!res.revenue_distribution || res.revenue_distribution.length === 0) {
                        $revHook.html('<small class="text-muted italic text-center d-block py-3">No cash collections accounted yet for this period.</small>');
                    } else {
                        res.revenue_distribution.forEach(function(item) {
                            var percentMark = res.financials.inflow > 0 ? ((parseFloat(item.value) / res.financials.inflow) * 100).toFixed(0) : 0;
                            var rowMarkup = `
                                <div class="mb-2 font-monospace" style="font-size:0.75rem;">
                                    <div class="d-flex justify-content-between mb-0.5 text-dark fw-semibold">
                                        <span>${item.label} Collections</span>
                                        <span>GHS ${parseFloat(item.value).toFixed(2)} (${percentMark}%)</span>
                                    </div>
                                    <div class="progress" style="height:6px; border-radius:4px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: ${percentMark}%"></div>
                                    </div>
                                </div>`;
                            $revHook.append(rowMarkup);
                        });
                    }

                    // 4. RENDER TOP 5 FAST-MOVING DRUGS FREQUENCY LIST
                    var $drugHook = $('#top_drugs_wrapper').empty();
                    if(!res.top_medications || res.top_medications.length === 0) {
                        $drugHook.html('<div class="text-center text-muted py-3 text-xs italic">No clinic prescriptions dispatched yet.</div>');
                    } else {
                        var tableDrugs = `
                            <table class="table table-sm table-hover align-middle mb-0 font-monospace text-xs" style="width:100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-1" style="width:70%;">Medication Name Description</th>
                                        <th class="text-end pe-1" style="width:30%;">Total Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        
                        res.top_medications.forEach(function(med) {
                            tableDrugs += `
                                <tr>
                                    <td class="ps-1 fw-semibold text-dark text-truncate" style="max-width:180px;"><i class="bx bx-capsule text-warning me-1"></i> ${$('<div>').text(med.drug).html()}</td>
                                    <td class="text-end pe-1 fw-bold text-primary">${med.frequency} times</td>
                                </tr>`;
                        });
                        tableDrugs += '</tbody></table>';
                        $drugHook.html(tableDrugs);
                    }

                    // 5. RENDER RECENT EXPENSES REGISTRY HISTORY SHEET
                    var $expHook = $('#expenses_table_wrapper').empty();
                    if(!res.recent_expenses || res.recent_expenses.length === 0) {
                        $expHook.html('<div class="text-center text-muted py-4 small italic">No cash outflows logged for this session.</div>');
                    } else {
                        var tableExp = `
                            <table class="table table-sm table-hover align-middle mb-0 font-monospace text-xs" style="width:100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-1" style="width:75%;">Expense Memo Details</th>
                                        <th class="text-end pe-1" style="width:25%;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        
                        res.recent_expenses.forEach(function(exp) {
                            tableExp += `
                                <tr class="transition-all">
                                    <td class="ps-1 text-truncate" style="max-width:200px;">
                                        ${$('<div>').text(exp.description).html()}
                                        <small class="text-muted d-block" style="font-size:0.62rem;"><i class="bx bx-calendar"></i> Date: ${exp.date_logged} | Group: ${exp.category}</small>
                                    </td>
                                    <td class="text-end pe-1 fw-bold text-danger">- GHS ${parseFloat(exp.amount).toFixed(2)}</td>
                                </tr>`;
                        });
                        tableExp += '</tbody></table>';
                        $expHook.html(tableExp);
                    }
                } else {
                    console.error("Dashboard calculation failed on server:", res.message);
                }
            } catch (renderErr) {
                console.error("Dashboard compilation rendering exception crash:", renderErr.message);
            }
        },
        error: function(xhr, status, error) {
            console.group("EXECUTIVE REPORT TRANSMISSION PIPELINE ERROR");
            console.error("Status Tracker:", status);
            console.error("Raw Backend Response Text:", xhr.responseText);
            console.groupEnd();
        }
    });
}

// Auto-run core sync routine immediately upon page load initialization
$(document).ready(function() {
    syncExecutiveDashboard();
});
</script> 