<?php
try {
    require_once 'includes/header.php'; 
    if (!$con) throw new Exception('Database connection failed');


    $labResponse = getPendingLabRequests($con);
    if ($labResponse['status'] === 'error') throw new Exception($labResponse['message']);
    $pendingLabs = $labResponse['data'];

} catch (Throwable $e) {
    error_log('Laboratory Portal Initializer Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger m-4">System Init Failure: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Medical Services /</span> Laboratory Panel</h4>
                <p class="text-muted small mb-0">Process incoming test orders and publish patient laboratory reports</p>
            </div>
        </div>

        <!-- LAB REQUESTS QUEUE TABLE CARD -->
        <div class="card">
            <h5 class="card-header fw-bold border-bottom py-3 text-info">
                <i class="bx bx-test-tube me-1"></i> Active Testing Queue
            </h5>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table id="DataTable" class="table table-striped table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Folder No.</th>
                                <th>Patient Name</th>
                                <th>Gender</th>
                                <th>Requested Test</th>
                                <th>Order Time</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pendingLabs)): ?>
                                <?php foreach ($pendingLabs as $lab): 
                                    $encodedData = base64_encode(json_encode($lab));
                                ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo htmlspecialchars($lab['folder_number']); ?></td>
                                        <td><?php echo htmlspecialchars($lab['name']); ?></td>
                                        <td><span class="badge bg-label-secondary"><?php echo htmlspecialchars($lab['gender']); ?></span></td>
                                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($lab['test_name']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($lab['created_at'])); ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-info text-white btn-enter-results" data-payload="<?php echo $encodedData; ?>">
                                                <i class="bx bx-edit-alt me-1"></i> Enter Results
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?> 
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- ==================================================================== -->
<!-- SNEAT COMPLIANT MODAL: ENTER LABORATORY RESULTS ENTRY LAYOUT          -->
<!-- ==================================================================== -->
<div class="modal fade" id="labResultsModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <!-- Modal Top branding header area -->
            <div class="modal-header border-bottom py-3">
                <div>
                    <small class="text-info fw-bold text-uppercase d-block mb-1">Results Dispatch Unit</small>
                    <h5 class="modal-title fw-bold text-dark" id="lab_modal_title">Input Laboratory Results</h5>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="labResultsForm" method="POST" autocomplete="off">
                <!-- Hidden state metrics fields to track directory records indexes safely -->
                <input type="hidden" name="request_id" id="l_request_id" />
                <input type="hidden" name="patient_id" id="l_patient_id" />
                <input type="hidden" name="manage_lab_results" value="true" />

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Patient Folder reference display card panel -->
                        <div class="col-12 bg-light p-3 rounded mb-2">
                            <div class="small text-dark mb-1"><b>Target Test Ordered:</b> <span id="l_display_test" class="text-info fw-bold"></span></div>
                            <div class="small text-dark"><b>Patient Name:</b> <span id="l_display_patient"></span></div>
                        </div>
                        
                        <!-- Analytical Findings description box -->
                        <div class="col-12 mb-2">
                            <label class="form-label fw-semibold">Final Laboratory Findings / Results</label>
                            <textarea name="lab_result" id="l_lab_result" class="form-control" rows="4" placeholder="Type specific testing values, blood markers, or finding descriptions clearly..." required></textarea>
                        </div>

                        <!-- Optional file attachment input with validation indicators placeholders -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Attach Scan / Document <small class="text-muted">(Optional)</small></label>
                            <input type="file" id="l_attachment" class="form-control" accept=".png, .jpeg, .jpg, .pdf" />
                            <div class="form-text small text-muted mt-1">Accepted formats: PNG, JPEG, JPG, PDF only. Max size: 5MB.</div>
                            <!-- Dynamic feedback error warning node label -->
                            <div class="invalid-feedback" id="file_error_feedback" style="display:none; font-weight: 600;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-info text-white fw-bold px-4" style="border-radius: 8px;">Publish & Route Back to Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php require_once 'includes/footer.php';?>
 

<script>
$(document).ready(function() {
    
    // 1. OPEN RESULTS MODAL & BIND DATA VALUES
    $('#DataTable').on('click', '.btn-enter-results', function(e) {
        e.preventDefault();
        var rawPayload = $(this).attr('data-payload');
        if (!rawPayload) return;

        try {
            var decoded = atob(rawPayload);
            var lab = JSON.parse(decoded);

            // Clean previous input fields text strings parameters
            $('#labResultsForm')[0].reset();

            // Bind hidden tracking indexes codes parameters
            $('#l_request_id').val(lab.request_id);
            $('#l_patient_id').val(lab.patient_id);

            // Set visual information strings labels
            $('#l_display_test').text(lab.test_name);
            $('#l_display_patient').text(lab.name + ' (' + lab.folder_number + ')');

            // Open input layout smoothly
            $('#labResultsModal').modal('show');

        } catch (failure) {
            console.error(failure);
            Swal.fire('Payload Error', 'Could not open laboratory file parameters mapping data arrays.', 'error');
        }
    });

     // 2. SUBMIT RESULTS FORM VIA AJAX AND RE-DRAW THE FRESH LAB LIST ROWS
$('#labResultsForm').on('submit', function(e) {
    e.preventDefault();
    var form = this;

    // Clear any previous red alert borders and messages
    $('#l_attachment').removeClass('is-invalid');
    $('#file_error_feedback').hide().empty();

    var fileInput = $('#l_attachment')[0];
    var hasFile = fileInput.files && fileInput.files.length > 0;
    var targetFile = hasFile ? fileInput.files[0] : null;

    // A. RUN FILE BOUNDARY RUNTIME VALIDATIONS IF A USER SELECTED A FILE
    if (hasFile) {
        var fileName = targetFile.name;
        var fileExtension = fileName.split('.').pop().toLowerCase();
        var allowedExtensions = ['png', 'jpeg', 'jpg', 'pdf'];

        // Extension check
        if ($.inArray(fileExtension, allowedExtensions) == -1) {
            $('#l_attachment').addClass('is-invalid');
            $('#file_error_feedback').text('Invalid type! Only PNG, JPEG, JPG, and PDF are allowed.').show();
            return false;
        }

        // Size check (5MB limit)
        if (targetFile.size > 5 * 1024 * 1024) {
            $('#l_attachment').addClass('is-invalid');
            $('#file_error_feedback').text('File is too large! Maximum limit size is 5MB.').show();
            return false;
        }
    }

    // B. SETUP DYNAMIC SWEETALERT CONFIRMATION PANEL TEXTS
    var alertText = hasFile 
        ? `This will save results permanently and upload the attached report scan: <b class="text-info">${$('<div>').text(targetFile.name).html()}</b>.` 
        : 'This will save the results permanently as an entries-only text log file chart.';

    // C. DEFINE EXECUTION PIPELINE FUNCTION TO POST SERIALIZED STRINGS TO SERVER
    function executeAjaxSubmission(base64PayloadString, filenameString) {
        Swal.fire({
            title: 'Publish Lab Report?',
            html: alertText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#03c3ec', // Sneat Info Blue
            cancelButtonColor: '#8592a3',  // Sneat Muted Gray
            confirmButtonText: 'Yes, Publish Findings',
            cancelButtonText: 'Review Report'
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({ 
                title: 'Publishing diagnostic data...', 
                html: 'Signing records, encrypting payload metrics strings and updating queues parameters...',
                allowOutsideClick: false, 
                didOpen: () => { Swal.showLoading(); } 
            });

            // Recompile serialized fields parameter layout string
            var compiledPostData = $(form).serialize();
            
            // Append optional Base64 file strings directly to post parameters if present
            if (base64PayloadString && filenameString) {
                compiledPostData += '&lab_base64_file=' + encodeURIComponent(base64PayloadString) + 
                                    '&lab_file_name=' + encodeURIComponent(filenameString);
            }

            $.ajax({
                url: server_url, // Points to your unified master endpoint handler variable
                type: 'POST',
                data: compiledPostData,
                dataType: 'json',
                success: function(response) {
                    Swal.close();

                    if (response.status === 'success') {
                        Swal.fire({ 
                            title: 'Published Successfully', 
                            text: response.message, 
                            icon: 'success', 
                            timer: 1500, 
                            showConfirmButton: false 
                        });
                            $('#labResultsModal').modal('hide');
                            form.reset();
                            updateLabTableUI(response.data);
                    } else {
                        Swal.fire('Rejected', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Connection Error', 'The laboratory data transmission pipeline failed on server.', 'error');
                }
            });
        });
    }

    // D. FLOW ENGINE ROUTER: READ AND ENCODE AS BASE64 IN BROWSERS CACHE MATRIX
    if (hasFile) {
        var reader = new FileReader();
        reader.onload = function() {
            var fullDataUrl = reader.result;
            
            // Isolate the base64 raw binary string characters from metadata prefix header lines
            var splitContent = fullDataUrl.split(',');
            var base64RawString = (splitContent.length > 1) ? splitContent[1] : '';

            // Fire submission pipeline forwarding file strings parameters cache entries
            executeAjaxSubmission(base64RawString, targetFile.name);
        };
        reader.onerror = function() {
            Swal.fire('File Read Error', 'Unable to extract file data binaries locally on browser memory.', 'error');
        };
        reader.readAsDataURL(targetFile);
    } else {
        // No file is present? Jump direct to execution pipeline passing null data parameters variables
        executeAjaxSubmission(null, null);
    }
});

});

// 3. CLEAN REALTIME RE-POPULATER USING EASY JQUERY .empty() FLUSH ENGINE
function updateLabTableUI(freshLabArray) {
    var $tbody = $('#DataTable').find('tbody');
    $tbody.empty();
    $tbody.append(`<tr></tr>`);

    freshLabArray.forEach(function(lab) {
        // Encode payload cleanly to a transport string matrix hook
        var base64Payload = btoa(unescape(encodeURIComponent(JSON.stringify(lab))));
        var cleanDate = new Date(lab.created_at).toLocaleTimeString('en-GH', { hour: '2-digit', minute: '2-digit' });

        $tbody.append(`
            <tr>
                <td class="fw-bold text-primary">${$('<div>').text(lab.folder_number).html()}</td>
                <td>${$('<div>').text(lab.name).html()}</td>
                <td><span class="badge bg-label-secondary">${$('<div>').text(lab.gender).html()}</span></td>
                <td class="fw-semibold text-dark">${$('<div>').text(lab.test_name).html()}</td>
                <td>${cleanDate}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-info text-white btn-enter-results" data-payload="${base64Payload}">
                        <i class="bx bx-edit-alt me-1"></i> Enter Results
                    </button>
                </td>
            </tr>
        `);
    });
}
</script>
