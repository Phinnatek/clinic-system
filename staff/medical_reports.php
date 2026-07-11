<?php require_once 'includes/header.php';?>  
<?php require_once 'includes/header.php'; ?>

<!-- DEDICATED PRINT ENGINE FILTER STYLE MATRIX OVERRIDE -->
<style>
@media print {
    body { background: #ffffff !important; color: #000000 !important; font-size: 12px; }
    .layout-navbar, .menu-inner, .btn, .card-header, .mb-3.d-flex, form { display: none !important; }
    .content-wrapper, .container-fluid { padding: 0 !important; margin: 0 !important; }
    .card { border: none !important; shadow: none !important; }
    #print_layout_preview_vault { display: block !important; }
}
</style>

<div class="container-fluid p-2.5">
    <!-- SUB-PAGE METRIC CONTROLS HEADING -->
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bx bx-bar-chart-alt-2 text-primary me-1"></i> Audit Reports Terminal</h4>
            <small class="text-muted">Compile specific department performance registries, query financial sheets, and output high-fidelity print documents</small>
        </div>
    </div>

    <!-- FILTER MANAGER SHEET GRID CONTEXT -->
    <div class="card shadow-none border bg-white mb-3">
        <div class="card-body p-2.5">
            <form id="frmGenerateAuditReport" method="POST" class="row g-2 align-items-end">
                <input type="hidden" name="action_generate_report" value="true" />
                
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-semibold mb-0" style="font-size:0.7rem;">Target Facility Department</label>
                    <select name="report_type" id="report_type" class="form-select form-select-sm" required>
                        <option value="" selected disabled>-- Select Audit Category --</option>
                        <option value="OPD_Admissions">OPD Cases & Consultations Log</option>
                        <option value="Lab_Analytics">Laboratory Test Volumes Sheet</option>
                        <option value="Pharmacy_Sales">Pharmacy Drug Dispensation List</option>
                        <option value="Finances_Ledger">Cashier Income & Ledger Balance</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-semibold mb-0" style="font-size:0.7rem;">Start Date Range</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm font-monospace" value="<?php echo date('Y-m-01'); ?>" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-semibold mb-0" style="font-size:0.7rem;">End Date Range</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm font-monospace" value="<?php echo date('Y-m-t'); ?>" required />
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 py-1.5 fw-bold shadow-sm"><i class="bx bx-filter-alt me-0.5"></i> Fetch Metrics</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PREVIEW SHEETS OUTPUT HOLDER (Hidden by default, populates dynamically) -->
    <div class="card shadow-none border bg-white d-none" id="print_layout_preview_vault">
        <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold text-dark mb-0 font-monospace text-uppercase" id="lbl_report_title">Report Title</h6>
                <small class="text-muted font-monospace text-xs" id="lbl_report_timeline">Timeline: --</small>
            </div>
            <!-- UPDATED DESK HEADER BUTTON: INTERCEPTS THE EXISTING RENDER DATA INTO AN INDEPENDENT MODAL VIEWPORT CONTAINER -->
<button type="button" class="btn btn-success btn-xs py-1 px-3 fw-bold shadow-sm" id="btn_trigger_modal_print_preview">
    <i class="bx bx-show-alt me-0.5"></i> Launch Live Print Preview
</button>

        </div>
        
        <!-- AUDIT SUMMARY SUMMARY CARD BOARD -->
        <div class="p-2 border-bottom bg-light-subtle d-flex justify-content-between align-items-center font-monospace" style="font-size:0.8rem;">
            <span class="text-secondary fw-semibold" id="lbl_summary_metric_title">Summary Data Metric:</span>
            <h4 class="mb-0 fw-bold text-success" id="lbl_summary_metric_value">0</h4>
        </div>

        <div class="card-body p-2" id="report_table_html_hook">
            <!-- Dynamic tabular layouts serialize right here -->
        </div>
    </div>
</div>
 
<section>
    <!-- FINTECH-STYLE FULL SCREEN PRINT PREVIEW MODAL WORKBENCH -->
<div class="modal fade" id="mdlReportPrintPreviewDesk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border shadow-lg" style="border-radius:12px;">
            <div class="modal-header border-bottom py-2 bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-printer text-primary me-0.5"></i> Isolated Document Print Server Preview</h6>
                    <small class="text-muted text-xs">Verify corporate layout boundaries before initializing terminal hardware output streams</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- ISOLATED PRINT CANVAS CONTAINER (Uses plain corporate typography styles to mimic true laser printouts) -->
            <div class="modal-body p-4 bg-white" id="isolated_printable_paper_canvas" style="font-family: 'Courier New', Courier, monospace; color: #000000; line-height: 1.3;">
                <!-- Printable document header brand metrics inject here dynamically -->
                <div id="modal_print_brand_header_block" class="text-center mb-4 border-bottom border-2 pb-2">
                    <h3 class="fw-bolder text-uppercase m-0 tracking-tight" style="letter-spacing:-0.5px; color:#000000;">SEDACOE CLINIC & STUDENT HEALTH PORTAL</h3>
                    <small class="text-xs d-block text-uppercase fw-bold mt-0.5">Official Audit Performance Registry Document • Confidential</small>
                </div>
                
                <!-- Target hooks where server table data clones seamlessly -->
                <div id="modal_print_meta_content_hook" class="mb-3" style="font-size:0.8rem;"></div>
                <div id="modal_print_table_data_hook"></div>
                
                <!-- Printable document footer signature audit footprint -->
                <div class="mt-5 pt-4 border-top border-dashed d-flex justify-content-between align-items-center small" style="font-size:0.75rem; border-color:#000000 !important;">
                    <span>Document generated by: Administration Terminal Vault System</span>
                    <span class="text-end font-monospace">Authorized Signature Stamp: ___________________________</span>
                </div>
            </div>
            
           <div class="modal-footer border-top p-1.5 bg-light d-flex justify-content-between align-items-center">
    <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-3" data-bs-dismiss="modal">Close Preview</button>
    <!-- UPDATED EXECUTION FUNCTION WITH ZERO PRINT ENGINE CRASHES -->
    <button type="button" class="btn btn-primary btn-xs py-1 px-4 fw-bold shadow-sm" onclick="executeIsolatedIframeDocumentPrint();">
        <i class="bx bx-printer me-0.5"></i> Execute Hardware Printer Stream
    </button>
</div>

        </div>
    </div>
</div>

<!-- INLINE PRINT WINDOW ISOLATOR CSS ENGINE -->
<style>
@media print {
    /* Completely mask out your main system parent framework components when printing is triggered */
    body * { display: none !important; }
    /* Target ONLY your isolated modal canvas elements and force them to remain visible on the paper profile */
    #mdlReportPrintPreviewDesk, #mdlReportPrintPreviewDesk *, #isolated_printable_paper_canvas, #isolated_printable_paper_canvas * { 
        display: block !important; 
    }
    #isolated_printable_paper_canvas { 
        position: absolute; 
        left: 0; 
        top: 0; 
        width: 100% !important; 
        padding: 0 !important; 
        margin: 0 !important; 
    }
    .modal-header, .modal-footer, .btn, .btn-close { display: none !important; }
}
</style>

</section>
<?php require_once 'includes/footer.php';?>
<script>
// ====================================================================
// FINTECH INTERCEPTOR: DYNAMIC PRINT PREVIEW CLONER ENGINE
// ====================================================================
$(document).on('click', '#btn_trigger_modal_print_preview', function(e) {
    e.preventDefault();
    
    // 1. Read existing header texts metrics directly from active dashboard parameters
    var reportTitleString = $('#lbl_report_title').text().trim();
    var reportTimelineString = $('#lbl_report_timeline').text().trim();
    var summaryValueString = $('#lbl_summary_metric_title').text().trim() + ' ' + $('#lbl_summary_metric_value').text().trim();
    
    // 2. Clone the structural table HTML compiled from the server response pipeline
    var serverTableMarkupClone = $('#report_table_html_hook').html();

    // 3. Formulate minimal plain text audit metadata for the print paper sheet layout
    var metaHtmlPackage = `
        <div class="row g-2 font-monospace mb-2" style="border-bottom: 1px dashed #000000; padding-bottom:10px;">
            <div class="col-12"><strong>DOCUMENT TITLE :</strong> ${reportTitleString}</div>
            <div class="col-6"><strong>FILTER TIMELINE:</strong> ${reportTimelineString}</div>
            <div class="col-6 text-end"><strong>LEDGER SUMMARY :</strong> <span style="font-weight:bolder; text-decoration:underline;">${summaryValueString}</span></div>
        </div>`;

    // 4. Inject packages smoothly into the isolated modal paper nodes canvas
    $('#modal_print_meta_content_hook').html(metaHtmlPackage);
    $('#modal_print_table_data_hook').html(serverTableMarkupClone);

    // Strips out interactive action buttons or styling pills from the print preview table clone row to maintain a professional layout
    $('#modal_print_table_data_hook').find('.btn, .btn-trigger-edit-metrics, .text-center:last-child').remove();
    $('#modal_print_table_data_hook').find('table').removeClass('table-hover table-striped').addClass('table-bordered');

    // 5. Mount full screen modal interface desk onto the monitor viewpoint canvas
    $('#mdlReportPrintPreviewDesk').modal('show');
});

// ====================================================================
// ISOLATED IFRAME HARDWARE PRINTER DOCUMENT ENGINE (ZERO LAYOUT BUG)
// ====================================================================
function executeIsolatedIframeDocumentPrint() {
    // 1. Extract the raw compiled HTML layout content package directly out of the modal canvas wrapper
    var pristinePrintContentHtml = document.getElementById('isolated_printable_paper_canvas').innerHTML;

    // 2. Locate or dynamically append an invisible printing iframe node onto the background DOM structure
    var $hiddenPrintIframe = $('#hiddenSystemPrintIframe');
    if ($hiddenPrintIframe.length === 0) {
        $hiddenPrintIframe = $('<iframe id="hiddenSystemPrintIframe" style="position:absolute; width:0px; height:0px; left:-9999px; top:-9999px; border:none;"></iframe>');
        $('body').append($hiddenPrintIframe);
    }

    // 3. Extract the clean document context window handle from the invisible background frame object
    var iframeTargetWindow = $hiddenPrintIframe[0].contentWindow || $hiddenPrintIframe[0].contentDocument;
    var iframeTargetDocument = $hiddenPrintIframe[0].contentDocument || $hiddenPrintIframe[0].contentWindow.document;

    // 4. Open and write a sterile, distraction-free HTML document into the background frame cache pipeline
    iframeTargetDocument.open();
    iframeTargetDocument.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Official Clinic Audit Summary Report</title>
            <!-- Inject clean corporate text typography constraints metrics directly into the frame headers -->
            <style>
                body { 
                    font-family: 'Courier New', Courier, monospace; 
                    color: #000000; 
                    background: #ffffff; 
                    padding: 20px; 
                    margin: 0; 
                    font-size: 13px; 
                    line-height: 1.4;
                }
                .text-center { text-align: center !important; }
                .mb-4 { margin-bottom: 1.5rem !important; }
                .mb-3 { margin-bottom: 1rem !important; }
                .border-bottom { border-bottom: 1px solid #000000 !important; }
                .border-2 { border-bottom-width: 2px !important; }
                .pb-2 { padding-bottom: 0.5rem !important; }
                .fw-bolder { font-weight: 800 !important; }
                .text-uppercase { text-transform: uppercase !important; }
                .d-block { display: block !important; }
                .mt-0.5 { margin-top: 0.2rem !important; }
                .row { display: flex; flex-wrap: wrap; width: 100%; }
                .col-6 { width: 50%; }
                .col-12 { width: 100%; }
                .text-end { text-align: right !important; }
                .mt-5 { margin-top: 3rem !important; }
                .pt-4 { padding-top: 1.5rem !important; }
                
                /* High fidelity data table grid line configurations formatting mapping */
                table { 
                    width: 100% !important; 
                    border-collapse: collapse !important; 
                    margin-top: 15px !important; 
                    font-size: 12px !important;
                }
                th, td { 
                    border: 1px solid #000000 !important; 
                    padding: 6px 8px !important; 
                    text-align: left; 
                }
                th { 
                    background-color: #f2f2f2 !important; 
                    font-weight: bold !important; 
                    -webkit-print-color-adjust: exact; 
                    print-color-adjust: exact;
                }
                .text-end { text-align: right !important; }
                
                /* Strip standard table utility badges colors if they exist to keep data looking neat on paper records */
                .badge { 
                    border: 1px solid #000000 !important; 
                    padding: 1px 4px !important; 
                    font-size: 10px !important; 
                    font-weight: bold !important;
                    text-transform: uppercase;
                }
                
                @page { 
                    size: auto; 
                    margin: 15mm 15mm 15mm 15mm; 
                }
            </style>
        </head>
        <body>
            ${pristinePrintContentHtml}
        </body>
        </html>
    `);
    iframeTargetDocument.close();

    // 5. SECURE LATENCY DISPATCH TRIGGER: Give the background window doc cache 250ms to finish processing its inner text variables
    setTimeout(function() {
        iframeTargetWindow.focus();
        iframeTargetWindow.print();
    }, 250);
}


$('#frmGenerateAuditReport').on('submit', function(e) {
    e.preventDefault();
    var form = this;

    Swal.fire({ title: 'Compiling database analytics records...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

    $.ajax({
        url: server_url,
        type: 'POST',
        data: $(form).serialize(),
        dataType: 'json',
        success: function(res) {
            Swal.close();
            if (res.status === 'success') {
                // Reveal the print canvas preview block element container
                var $previewVault = $('#print_layout_preview_vault').removeClass('d-none');
                
                // Bind layout texts headers indicators
                $('#lbl_report_title').text(res.meta.type_lbl + ' Audit Summary Registry');
                $('#lbl_report_timeline').text('Fiscal Timeline Window Filter: ' + res.meta.timeline);
                $('#lbl_summary_metric_title').text(res.summary.metric_lbl + ':');
                $('#lbl_summary_metric_value').text(res.summary.metric_val);

                var $tableHook = $('#report_table_html_hook').empty();
                if (!res.records || res.records.length === 0) {
                    $tableHook.html('<div class="text-center text-muted py-5 small italic"><i class="bx bx-folder-open d-block fs-2"></i> No transaction rows recorded inside this time window.</div>');
                    return;
                }

                // CHOOSE SPECIFIC COLUMN HEADERS SCHEMAS DYNAMICALLY BASED ON CATEGORY CHOICE
                var isFinReport = res.meta.type_lbl.toLowerCase().includes('finance');
                var header3Title = isFinReport ? "Transaction Flow Memo" : "Prescription/Diagnostic Assignment Details";
                var header4Title = isFinReport ? "Accounting Value" : "Tracking Date";

                var tableHtml = `
                    <table class="table table-sm table-striped align-middle mb-0 font-monospace text-xs" style="font-size:0.75rem; width:100%;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-1" style="width:12%;">Ref ID</th>
                                <th style="width:25%;">Patient Name / Account Target</th>
                                <th style="width:43%;">${header3Title}</th>
                                <th class="text-end pe-1" style="width:20%;">${header4Title}</th>
                            </tr>
                        </thead>
                        <tbody>`;

                res.records.forEach(function(row) {
                    if (isFinReport) {
                        var isInflow = (row.transaction_type === 'Inflow');
                        var amountVal = parseFloat(row.amount);
                        var prefixSymbol = isInflow ? "+" : "-";
                        
                        tableHtml += `
                            <tr>
                                <td class="ps-1">#${row.ref_id}</td>
                                <td class="fw-bold text-dark"><span class="badge ${isInflow?'bg-label-success':'bg-label-danger'} py-0 px-1 text-xs text-uppercase me-1">${row.transaction_type}</span> ${row.description}</td>
                                <td class="text-muted small">General Inflow/Outflow ledger ledger balancing lines record</td>
                                <td class="text-end pe-1 fw-bold ${isInflow?'text-success':'text-danger'}">${prefixSymbol} GHS ${amountVal.toFixed(2)}</td>
                            </tr>`;
                    } else {
                        // Standard Clinical Department Layout Render Pipeline
                        var patientLabel = `<b>${$('<div>').text(row.patient_name).html()}</b> <small class="text-muted">(${row.folder_number})</small>`;
                        var descLabel = row.description ? $('<div>').text(row.description).html() : (row.disposition ? "Visit Status: " + row.disposition : "Routine Consultation Care");
                        
                        tableHtml += `
                            <tr>
                                <td class="ps-1">#${row.ref_id}</td>
                                <td>${patientLabel}</td>
                                <td class="text-secondary fw-semibold">${descLabel}</td>
                                <td class="text-end pe-1 text-muted">${row.date_logged}</td>
                            </tr>`;
                    }
                });

                tableHtml += '</tbody></table>';
                $tableHook.html(tableHtml);
                
                // Smoothly focus the screen right down to reveal the report tables footprint preview element
                $('html, body').animate({ scrollTop: $previewVault.offset().top - 20 }, 400);

            } else {
                Swal.fire('Fetch Error', res.message, 'error');
            }
        },
        error: function(xhr) {
            Swal.close();
            console.error("Report extraction transit crash raw text response: ", xhr.responseText);
            Swal.fire('Transmission Failure', 'The analytical engine failed to extract database records.', 'error');
        }
    });
});
</script>

