<?php require_once 'includes/header.php'; ?>

<div class="container-fluid p-2.5">
    <!-- SUB-PAGE METRIC CONTROLS HEADING -->
    <div class="mb-2.5 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bx bx-shield-quarter text-primary me-1"></i> Security Audit Trail Logs</h4>
            <small class="text-muted">Monitor facility-wide operational changes, staff system logins footprints, and ledger modification paths</small>
        </div>
        <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2.5" onclick="syncSystemActivityLogsRegistry()"><i class="bx bx-refresh"></i> Refresh Audit Trail</button>
    </div>

    <!-- MAIN SYSTEM LOGS MATRIX DIRECT GRID CARD CONTAINER -->
    <div class="card shadow-none border bg-white">
        <div class="card-header bg-white border-bottom py-2">
            <h6 class="fw-bold text-dark mb-0" style="font-size:0.82rem;"><i class="bx bx-list-ul text-primary me-0.5"></i> Chronological Security Event Log</h6>
        </div>
        <div class="card-body p-1.5" id="activity_logs_table_wrapper">
            <!-- JavaScript dynamically appends custom table arrays here -->
            <div class="text-center text-muted py-5 small italic">Loading operational audit registry traces...</div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>

<script>

function syncSystemActivityLogsRegistry() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { fetch_system_activity_logs: true },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                var $wrapper = $('#activity_logs_table_wrapper').empty();
                if (!res.data || res.data.length === 0) {
                    $wrapper.html('<div class="text-center text-muted py-5 small italic">No system activity events recorded inside this partition log framework.</div>');
                    return;
                }

                var tableHtml = `
                    <table class="table table-sm table-hover align-middle mb-0 font-monospace text-xs" style="font-size:0.72rem; width:100%;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-1" style="width:12%;">Module</th>
                                <th style="width:12%;">Action</th>
                                <th style="width:51%;">Audit Trace Narrative Note Description</th>
                                <th style="width:25%;">Initiator / Address Details</th>
                            </tr>
                        </thead>
                        <tbody>`;

                res.data.forEach(function(log) {
                    // Choose distinctive visual badge category markers
                    var badgeAccent = "bg-label-primary text-primary";
                    if(log.module === 'Auth') badgeAccent = "bg-label-danger text-danger";
                    if(log.module === 'Accounts') badgeAccent = "bg-label-success text-success";
                    if(log.module === 'Pharmacy') badgeAccent = "bg-label-warning text-warning";
                    if(log.module === 'Laboratory') badgeAccent = "bg-label-info text-info";

                    tableHtml += `
                        <tr class="transition-all">
                            <td class="ps-1"><span class="badge ${badgeAccent} py-0 px-1 text-xs text-uppercase font-monospace">${log.module}</span></td>
                            <td class="fw-bold text-dark text-xs">${log.action_type}</td>
                            <td class="text-dark font-monospace text-wrap" style="word-break: break-word;">
                                ${$('<div>').text(log.description).html()}
                                <small class="text-muted d-block mt-0.5" style="font-size:0.62rem;"><i class="bx bx-time"></i> Clock: ${log.formatted_date}</small>
                            </td>
                            <td>
                                <span class="fw-bold text-secondary d-block text-truncate" style="max-width:160px;">${$('<div>').text(log.staff_name).html()}</span>
                                <small class="text-muted d-block font-monospace" style="font-size:0.62rem;"><i class="bx bx-laptop"></i> IP: ${log.ip_address} | ${log.staff_role}</small>
                            </td>
                        </tr>`;
                });

                tableHtml += '</tbody></table>';
                $wrapper.html(tableHtml);
            }
        }
    });
}

$(document).ready(function() { syncSystemActivityLogsRegistry(); });
</script>
