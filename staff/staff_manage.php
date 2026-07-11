<?php require_once 'includes/header.php';?>  

<div class="container-fluid p-3">
    <div class="row g-3">
        <!-- COLUMN 1: NEW PROFILE REGISTRATION / EDIT BOARD (Width 4) -->
        <div class="col-md-4">
            <div class="card shadow-none border h-100">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="fw-bold text-dark mb-0" id="form_panel_title"><i class="bx bx-user-plus text-success me-1"></i> Register Clinical Professional</h6>
                </div>
                <div class="card-body pt-3 pb-2">
                    <form id="frmSaveStaff" method="POST">
                        <input type="hidden" name="action_save_staff" value="true" />
                        <input type="hidden" name="staff_id" id="staff_id" value="0" />

                        <!-- REWRITTEN HIGH-DENSITY INPUT SECTION PANEL -->
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-semibold mb-0" style="font-size:0.7rem;">Legal Full Name</label>
                            <input type="text" name="full_name" id="full_name" class="form-control form-control-sm" placeholder="e.g., Dr. Kwame Mensah" required />
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-muted small fw-semibold mb-0" style="font-size:0.7rem;">Clinical Core Assignment Role</label>
                            <select name="role" id="role" class="form-select form-select-sm" required>
                                <option value="" selected disabled>-- Pick Access Level Group --</option>
                                <option value="Doctor">Medical Doctor (Consulting Suite)</option>
                                <option value="Nurse">Triage / Ward Nurse</option>
                                <option value="Pharmacist">Sub-Depot Retail Pharmacist</option>
                                <option value="Lab Tech">Laboratory Medical Scientist</option>
                                <option value="Cashier">Financial Ledger Accountant</option>
                                <option value="Storekeeper">Main Warehouse Storekeeper</option>
                                <option value="Admin">Chief System Administrator</option>
                            </select>
                        </div>

                        <!-- DYNAMIC COMPLIANT DISPOSITION FIELD: Reveals assigned staff ID strings on edit transitions -->
                        <div class="mb-2" id="staff_id_display_row" style="display: none;">
                            <label class="form-label text-muted small fw-semibold mb-0" style="font-size:0.7rem;">Assigned Unique Staff ID</label>
                            <input type="text" id="staff_id_string_preview" class="form-control form-control-sm font-monospace text-xs bg-light" readonly style="font-weight: 700; color:#696cff;" />
                        </div>
 
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-semibold mb-0" id="lbl_pwd_rules" style="font-size:0.7rem;">Authentication Password</label>
                            <input type="password" name="password" id="password" class="form-control form-control-sm font-monospace" placeholder="••••••••" required />
                        </div>
                        <div class="mb-3" id="status_input_row" style="display: none;">
                            <label class="form-label text-muted small fw-semibold mb-0" style="font-size:0.7rem;">Access Status State</label>
                            <select name="status" id="status" class="form-select form-select-sm">
                                <option value="Active">Active (Terminal Access Granted)</option>
                                <option value="Suspended">Suspended (Instant Lockout Encrypted)</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-50 py-2 d-none" id="btn_cancel_edit">Cancel Edit</button>
                            <button type="submit" class="btn btn-success btn-sm flex-grow-1 py-2 fw-bold shadow-sm" id="btn_submit_staff">
                                <i class="bx bx-check-shield me-1"></i> Save Profile Access
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- COLUMN 2: ACTIVE CLINIC STAFF PROFILES LIVE DIRECTORY (Width 8) -->
        <div class="col-md-8">
            <div class="card shadow-none border h-100">
                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0" style="font-size:0.85rem;"><i class="bx bx-group text-primary me-1"></i> Accredited Medical Professionals Directory</h6>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0.5" onclick="syncStaffDashboard()"><i class="bx bx-refresh"></i> Sync</button>
                </div>
                <div class="card-body p-1.5" id="staff_table_wrapper">
                    <!-- JavaScript appends rows matrix lists here cleanly -->
                    <div class="text-center text-muted py-5 small italic">Loading staff database tracks...</div>
                </div>
            </div>
        </div>
    </div>
</div>
 
 

<?php require_once 'includes/footer.php';?><script>

var globalStaffDirectoryDataset = [];

function syncStaffDashboard() {
    $.ajax({
        url: server_url,
        type: 'POST',
        data: { fetch_staff_list: true },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                globalStaffDirectoryDataset = res.data;
                renderStaffDirectoryTableUI();
            }
        }
    });
}
function renderStaffDirectoryTableUI() {
    var $wrapper = $('#staff_table_wrapper').empty();
    if(globalStaffDirectoryDataset.length === 0) {
        $wrapper.html('<div class="text-center text-muted py-5 small italic">No accredited staff accounts found inside system indices.</div>');
        return;
    }
    sn =1;

    var tableHtml = `
        <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size:0.75rem; width:100%;" id="DataTable">
            <thead class="table-light sticky-top">
                <tr>
                    <th class="ps-1"  >S/N</th>
                    <th class="ps-1" style="width:42%;">Staff Professional Name</th>
                    <th style="width:23%;">Staff ID</th>
                    <th style="width:23%;">Clinical Role</th>
                    <th class="text-center" style="width:12%;">Modify</th>
                </tr>
            </thead><tbody>`;

    globalStaffDirectoryDataset.forEach(function(staff, idx) {
        var isSuspended = (staff.status === 'Suspended');
        var badgeAccent = isSuspended ? "bg-label-danger text-danger" : "bg-label-success text-success";
        
        tableHtml += `
            <tr class="transition-all ${isSuspended ? 'bg-light-subtle' : ''}">
                <td >${sn++}</td>
                <td class="ps-1 fw-semibold text-dark text-truncate" style="max-width:170px;">
                    ${$('<div>').text(staff.full_name).html()}
                    <small class="text-muted d-block" style="font-size:0.62rem;"><i class="bx bx-calendar"></i> Registered: ${staff.date_created}</small>
                </td>
                <!-- DISPLAYED: Safe printed value output for auto-allocated codes -->
                <td class="text-primary fw-bold font-monospace text-xs">${$('<div>').text(staff.staff_id).html()}</td>
                <td>
                    <span class="badge ${badgeAccent} py-0 px-1 text-uppercase text-nowrap me-1" style="font-size:0.65rem;">${staff.status}</span>
                    <span class="text-dark small text-nowrap" style="font-size:0.7rem;">${staff.role}</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-outline-primary py-0.5 px-1.5 btn-trigger-edit-staff" data-index="${idx}">
                        <i class="bx bx-edit-alt"></i> Load
                    </button>
                </td>
            </tr>`;
    });

    tableHtml += '</tbody></table>';
    $wrapper.html(tableHtml);
    initializeDataTable();
}

// INTERCEPT CLICK EVENT: PRE-FILL FORM VALUES SECURELY ON CHOSEN RECORD MODIFICATIONS
$(document).on('click', '.btn-trigger-edit-staff', function(e) {
    e.preventDefault();
    var idx = $(this).data('index');
    var staff = globalStaffDirectoryDataset[idx];
    if(!staff) return;

    // Reset fields values targets using explicit primary keys
    $('#staff_id').val(staff.id); // Hidden primary key integer input field
    $('#full_name').val(staff.full_name);
    $('#role').val(staff.role);
    $('#status').val(staff.status);
    
    // Bind auto-generated tracking labels to preview
    $('#staff_id_string_preview').val(staff.staff_id);
    
    $('#password').val('').attr('placeholder', 'Leave blank to retain old key').prop('required', false);
    $('#lbl_pwd_rules').text('Change Password (Optional)');
    
    $('#form_panel_title').html('<i class="bx bx-edit text-primary me-1"></i> Modify Staff Credentials');
    $('#staff_id_display_row, #status_input_row, #btn_cancel_edit').show();
    $('#btn_cancel_edit').removeClass('d-none');
});

// RESET VIEWPORT FORM BOUNDARIES BACK TO CREATION LAYOUTS
$(document).on('click', '#btn_cancel_edit', function() {
    document.getElementById('frmSaveStaff').reset();
    $('#staff_id').val('0');
    $('#password').attr('placeholder', '••••••••').prop('required', true);
    $('#lbl_pwd_rules').text('Authentication Password');
    $('#form_panel_title').html('<i class="bx bx-user-plus text-success me-1"></i> Register Clinical Professional');
    $('#staff_id_display_row, #status_input_row, #btn_cancel_edit').hide();
});

// CANCEL REQUISITE EDIT VIEWS: ROLLBACK TO NEUTRAL DEFAULT REGISTRATION VALUES
$(document).on('click', '#btn_cancel_edit', function() {
    $('#frmSaveStaff')[0].reset();
    $('#staff_id').val('0');
    $('#password').attr('placeholder', '••••••••').prop('required', true);
    $('#lbl_pwd_rules').text('Authentication Password');
    $('#form_panel_title').html('<i class="bx bx-user-plus text-success me-1"></i> Register Clinical Professional');
    $('#status_input_row, #btn_cancel_edit').hide();
});

// SUBMIT INTERCEPT GATE: POST ACCREDITATIONS RECORD ENCRYPTIONS
$('#frmSaveStaff').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    
    Swal.fire({
        title: 'Commit Profile Changes?',
        text: 'This updates user credential tokens, logs access keys parameters modifications, and affects dynamic dashboard routing channels.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28c76f',
        cancelButtonColor: '#8592a3',
        confirmButtonText: 'Confirm Signature'
    }).then((res) => {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Encrypting profile record credentials matrices...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: server_url,
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                Swal.close();
                if(response.status === 'success') {
                    Swal.fire({ title: 'Data Secured', text: response.message, icon: 'success', timer: 1200, showConfirmButton: false });
                    $('#btn_cancel_edit').trigger('click'); // Automatically resets fields layout back to neutral default register mode
                    globalStaffDirectoryDataset = response.data;
                    renderStaffDirectoryTableUI();
                } else {
                    Swal.fire('Operation Blocked', response.message, 'error');
                }
            }
        });
    });
});

$(document).ready(function() { syncStaffDashboard(); });
</script> 